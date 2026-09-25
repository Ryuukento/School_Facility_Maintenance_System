<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsSharedTestSchema;
use Tests\Support\ConfiguresIsolatedSqliteConnection;
use Tests\Support\InteractsWithLegacySession;
use Tests\TestCase;

/**
 * TASK 13 — Dispatch Release Assignment Workflow: AUTHORIZATION.
 *
 * Every test here drives a real HTTP request, because the requirement is that
 * these rules hold SERVER-SIDE — "client-side filtering alone is NOT
 * sufficient". Each one therefore sends exactly the request a malicious or
 * out-of-date client would send, bypassing whatever the UI would have allowed,
 * and asserts the server refuses it and that no state changed.
 *
 * Two properties are under test:
 *
 *   1. DEPARTMENT AUTHORIZATION (carried forward from TASK 9). A Head
 *      Maintenance may only assign Maintenance Staff from their OWN
 *      department. A Computer Department Head assigning Electrical staff must
 *      fail regardless of what the request contains.
 *
 *   2. ASSIGNEE-ONLY RELEASE. "Server must validate: Authenticated user ==
 *      release_assigned_to. Role alone is NOT enough." Holding the
 *      maintenance_staff role must not be sufficient to release somebody
 *      else's dispatch.
 */
class DispatchReleaseAssignmentAuthorizationTest extends TestCase
{
    use BuildsSharedTestSchema;
    use ConfiguresIsolatedSqliteConnection;
    use InteractsWithLegacySession;

    private const DEPT_COMPUTER = 1;
    private const DEPT_ELECTRICAL = 2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useInMemoryDatabase('dispatch_release_assignment_authz_testing');
        $this->createTestSchema();
        $this->forceLocalTestUrl();
    }

    // -----------------------------------------------------------------
    // 1. Department authorization — assignment
    // -----------------------------------------------------------------

    public function test_head_can_assign_staff_from_their_own_department_at_creation(): void
    {
        $headId = $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_COMPUTER]);
        $staffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);

        $response = $this
            ->actingAsSessionUserWithFlatKeys($headId, 'maintenance_admin')
            ->postJson('/api/dispatches', [
                'items' => [['item_id' => $itemId, 'quantity' => 1]],
                'release_assigned_to' => $staffId,
            ]);

        $response->assertStatus(201);
        $this->assertSame($staffId, (int) DB::table('dispatches')->where('id', $response->json('data.dispatch_id'))->value('release_assigned_to'));
    }

    /**
     * The headline rule: Computer Department Head -> Electrical staff MUST
     * fail, even though the request is otherwise perfectly well-formed and the
     * target user is a genuinely active Maintenance Staff member.
     */
    public function test_head_cannot_assign_staff_from_another_department_at_creation(): void
    {
        $headId = $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_COMPUTER]);
        $foreignStaffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_ELECTRICAL]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);

        $response = $this
            ->actingAsSessionUserWithFlatKeys($headId, 'maintenance_admin')
            ->postJson('/api/dispatches', [
                'items' => [['item_id' => $itemId, 'quantity' => 1]],
                'release_assigned_to' => $foreignStaffId,
            ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('own department', (string) $response->json('message'));
        $this->assertSame(0, DB::table('dispatches')->count(), 'No dispatch may be created by a rejected assignment.');
    }

    public function test_head_cannot_assign_staff_from_another_department_when_reassigning(): void
    {
        $headId = $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_COMPUTER]);
        $ownStaffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $foreignStaffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_ELECTRICAL]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);
        $dispatchId = $this->seedDispatch($itemId, 1, [
            'department_id' => self::DEPT_COMPUTER,
            'requested_by' => $headId,
            'release_assigned_to' => $ownStaffId,
            'release_assigned_by' => $headId,
        ]);

        $response = $this
            ->actingAsSessionUserWithFlatKeys($headId, 'maintenance_admin')
            ->postJson("/api/dispatches/{$dispatchId}/assign-personnel", [
                'release_assigned_to' => $foreignStaffId,
            ]);

        $response->assertStatus(400);
        $this->assertSame($ownStaffId, (int) DB::table('dispatches')->where('id', $dispatchId)->value('release_assigned_to'));
    }

    /**
     * A Head of one department must not be able to touch another
     * department's dispatch at all, even to assign one of their own staff.
     */
    public function test_head_cannot_reassign_a_dispatch_belonging_to_another_department(): void
    {
        $foreignHeadId = $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_ELECTRICAL]);
        $foreignStaffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_ELECTRICAL]);
        $computerStaffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);
        $dispatchId = $this->seedDispatch($itemId, 1, [
            'department_id' => self::DEPT_COMPUTER,
            'release_assigned_to' => $computerStaffId,
        ]);

        $response = $this
            ->actingAsSessionUserWithFlatKeys($foreignHeadId, 'maintenance_admin')
            ->postJson("/api/dispatches/{$dispatchId}/assign-personnel", [
                'release_assigned_to' => $foreignStaffId,
            ]);

        $response->assertStatus(403);
        $this->assertSame($computerStaffId, (int) DB::table('dispatches')->where('id', $dispatchId)->value('release_assigned_to'));
    }

    public function test_release_personnel_cannot_be_an_administrator_or_another_head(): void
    {
        $headId = $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_COMPUTER]);
        $otherHeadId = $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_COMPUTER]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);

        $response = $this
            ->actingAsSessionUserWithFlatKeys($headId, 'maintenance_admin')
            ->postJson('/api/dispatches', [
                'items' => [['item_id' => $itemId, 'quantity' => 1]],
                // Same department, active — but not a Maintenance Staff member.
                'release_assigned_to' => $otherHeadId,
            ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('Maintenance Staff', (string) $response->json('message'));
    }

    public function test_release_personnel_cannot_be_an_inactive_user(): void
    {
        $headId = $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_COMPUTER]);
        $inactiveStaffId = $this->seedUser([
            'role' => 'maintenance_staff',
            'department_id' => self::DEPT_COMPUTER,
            'status' => 'inactive',
        ]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);

        $response = $this
            ->actingAsSessionUserWithFlatKeys($headId, 'maintenance_admin')
            ->postJson('/api/dispatches', [
                'items' => [['item_id' => $itemId, 'quantity' => 1]],
                'release_assigned_to' => $inactiveStaffId,
            ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('inactive', (string) $response->json('message'));
    }

    /**
     * The personnel picker's candidate list must be scoped from the SESSION,
     * not from anything the client can supply — otherwise the department rule
     * would only be a UI convenience.
     */
    public function test_release_personnel_endpoint_only_returns_same_department_staff(): void
    {
        $headId = $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_COMPUTER]);
        $ownStaffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER, 'full_name' => 'Computer Staff']);
        $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_ELECTRICAL, 'full_name' => 'Electrical Staff']);
        $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER, 'full_name' => 'Retired Staff', 'status' => 'inactive']);
        $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_COMPUTER, 'full_name' => 'Another Head']);

        $response = $this
            ->actingAsSessionUserWithFlatKeys($headId, 'maintenance_admin')
            ->getJson('/api/dispatches/support/release-personnel');

        $response->assertOk();
        $ids = collect($response->json('data.users'))->pluck('user_id')->all();

        $this->assertSame([$ownStaffId], $ids, 'Only active, same-department Maintenance Staff may be offered.');
    }

    /**
     * Even if a client fabricated a department_id query parameter, the server
     * must ignore it and keep using the session's department.
     */
    public function test_release_personnel_endpoint_ignores_a_client_supplied_department(): void
    {
        $headId = $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_COMPUTER]);
        $ownStaffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_ELECTRICAL]);

        $response = $this
            ->actingAsSessionUserWithFlatKeys($headId, 'maintenance_admin')
            ->getJson('/api/dispatches/support/release-personnel?department_id=' . self::DEPT_ELECTRICAL);

        $response->assertOk();
        $ids = collect($response->json('data.users'))->pluck('user_id')->all();

        $this->assertSame([$ownStaffId], $ids);
    }

    // -----------------------------------------------------------------
    // 2. Assignee-only release
    // -----------------------------------------------------------------

    public function test_the_assigned_staff_member_can_release(): void
    {
        [$adminId, $staffId, $dispatchId, $itemId] = $this->seedApprovedDispatch();

        $this->actingAsSessionUserWithFlatKeys($staffId, 'maintenance_staff')
            ->postJson("/api/dispatches/{$dispatchId}/release", [])
            ->assertOk();

        $this->assertSame('released', DB::table('dispatches')->where('id', $dispatchId)->value('status'));
        $this->assertSame(8, (int) DB::table('items')->where('id', $itemId)->value('quantity'));
    }

    /**
     * THE core security rule of TASK 13. This user has the correct role, is
     * active, and is in the same department — and must still be refused,
     * because they are not the assignee.
     */
    public function test_a_different_staff_member_cannot_release_someone_elses_dispatch(): void
    {
        [$adminId, $staffId, $dispatchId, $itemId] = $this->seedApprovedDispatch();
        $otherStaffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);

        $response = $this
            ->actingAsSessionUserWithFlatKeys($otherStaffId, 'maintenance_staff')
            ->postJson("/api/dispatches/{$dispatchId}/release", []);

        $response->assertStatus(403);
        $this->assertSame('approved', DB::table('dispatches')->where('id', $dispatchId)->value('status'));
        $this->assertSame(10, (int) DB::table('items')->where('id', $itemId)->value('quantity'), 'A refused release must move no stock.');
        $this->assertSame(0, DB::table('inventory_transactions')->count());
    }

    public function test_a_maintenance_staff_member_cannot_release_an_unassigned_dispatch(): void
    {
        $adminId = $this->seedUser(['role' => 'super_admin']);
        $staffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);
        // A legacy row: approved under the old workflow, never assigned.
        $dispatchId = $this->seedDispatch($itemId, 2, ['status' => 'approved', 'approved_by' => $adminId]);

        $response = $this
            ->actingAsSessionUserWithFlatKeys($staffId, 'maintenance_staff')
            ->postJson("/api/dispatches/{$dispatchId}/release", []);

        $response->assertStatus(403);
        $this->assertSame(0, DB::table('inventory_transactions')->count());
    }

    // -----------------------------------------------------------------
    // 3. Role separation
    // -----------------------------------------------------------------

    public function test_head_maintenance_cannot_approve_a_dispatch(): void
    {
        $headId = $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_COMPUTER]);
        $staffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);
        $dispatchId = $this->seedDispatch($itemId, 2, ['release_assigned_to' => $staffId]);

        $this->actingAsSessionUserWithFlatKeys($headId, 'maintenance_admin')
            ->postJson("/api/dispatches/{$dispatchId}/approve", ['approved_by' => $headId])
            ->assertStatus(403);

        $this->assertSame('pending', DB::table('dispatches')->where('id', $dispatchId)->value('status'));
    }

    public function test_administrator_cannot_assign_release_personnel(): void
    {
        $adminId = $this->seedUser(['role' => 'super_admin']);
        $staffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $otherStaffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);
        $dispatchId = $this->seedDispatch($itemId, 2, ['release_assigned_to' => $staffId]);

        $this->actingAsSessionUserWithFlatKeys($adminId, 'super_admin')
            ->postJson("/api/dispatches/{$dispatchId}/assign-personnel", ['release_assigned_to' => $otherStaffId])
            ->assertStatus(403);

        $this->assertSame($staffId, (int) DB::table('dispatches')->where('id', $dispatchId)->value('release_assigned_to'));
    }

    /**
     * TASK 41 — REPLACES `test_administrator_cannot_create_a_dispatch`.
     *
     * This test previously asserted that an Administrator receives 403 from
     * POST /api/dispatches and that no dispatch row appears. That was a correct
     * statement of the TASK 13 business rule, under which only Head Maintenance
     * could create a dispatch. The business rule has since been changed by the
     * system owner: an Administrator may now create dispatches, and because
     * the Administrator IS the approval authority, their own dispatch has no
     * approval step to wait for.
     *
     * The test is not deleted — it is inverted, so the file still pins down
     * exactly what an Administrator's create request does. The assertions below
     * are the mirror image of the ones they replace: same request, opposite
     * expected outcome, plus the two facts that make the new behaviour safe
     * (it is 'approved' WITHOUT a forged approver, and nothing was deducted).
     */
    public function test_administrator_can_create_a_dispatch_that_needs_no_approval(): void
    {
        $adminId = $this->seedUser(['role' => 'super_admin']);
        $staffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);

        $response = $this
            ->actingAsSessionUserWithFlatKeys($adminId, 'super_admin')
            ->postJson('/api/dispatches', [
                'items' => [['item_id' => $itemId, 'quantity' => 1]],
                'release_assigned_to' => $staffId,
            ]);

        $response->assertStatus(201);
        $this->assertFalse($response->json('data.approval_required'));

        $dispatch = DB::table('dispatches')->where('id', $response->json('data.dispatch_id'))->first();

        // It skips 'pending' entirely and is immediately releasable.
        $this->assertSame('approved', $dispatch->status);
        // ...but NO approval actually happened, so no approver is invented.
        $this->assertNull($dispatch->approved_by);
        $this->assertNull($dispatch->approved_at);
        // The named release personnel is still recorded, exactly as for a
        // Head-created dispatch.
        $this->assertSame($staffId, (int) $dispatch->release_assigned_to);

        // Creating still does not touch stock — that remains release-only.
        $this->assertSame(10, (int) DB::table('items')->where('id', $itemId)->value('quantity'));
    }

    // -----------------------------------------------------------------
    // 4. Reassignment window
    // -----------------------------------------------------------------

    public function test_head_may_reassign_while_pending_and_while_approved(): void
    {
        $headId = $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_COMPUTER]);
        $staffA = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $staffB = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $adminId = $this->seedUser(['role' => 'super_admin']);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);
        $dispatchId = $this->seedDispatch($itemId, 2, [
            'department_id' => self::DEPT_COMPUTER,
            'release_assigned_to' => $staffA,
            'release_assigned_by' => $headId,
        ]);

        // While pending.
        $this->actingAsSessionUserWithFlatKeys($headId, 'maintenance_admin')
            ->postJson("/api/dispatches/{$dispatchId}/assign-personnel", ['release_assigned_to' => $staffB])
            ->assertOk();
        $this->assertSame($staffB, (int) DB::table('dispatches')->where('id', $dispatchId)->value('release_assigned_to'));

        $this->actingAsSessionUserWithFlatKeys($adminId, 'super_admin')
            ->postJson("/api/dispatches/{$dispatchId}/approve", ['approved_by' => $adminId])
            ->assertOk();

        // While approved but not released.
        $this->actingAsSessionUserWithFlatKeys($headId, 'maintenance_admin')
            ->postJson("/api/dispatches/{$dispatchId}/assign-personnel", ['release_assigned_to' => $staffA])
            ->assertOk();
        $this->assertSame($staffA, (int) DB::table('dispatches')->where('id', $dispatchId)->value('release_assigned_to'));
    }

    public function test_assignment_becomes_read_only_once_released(): void
    {
        $headId = $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_COMPUTER]);
        $staffA = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $staffB = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $adminId = $this->seedUser(['role' => 'super_admin']);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);
        $dispatchId = $this->seedDispatch($itemId, 2, [
            'department_id' => self::DEPT_COMPUTER,
            'release_assigned_to' => $staffA,
            'release_assigned_by' => $headId,
        ]);

        $this->actingAsSessionUserWithFlatKeys($adminId, 'super_admin')
            ->postJson("/api/dispatches/{$dispatchId}/approve", ['approved_by' => $adminId])
            ->assertOk();
        $this->actingAsSessionUserWithFlatKeys($staffA, 'maintenance_staff')
            ->postJson("/api/dispatches/{$dispatchId}/release", [])
            ->assertOk();

        $this->actingAsSessionUserWithFlatKeys($headId, 'maintenance_admin')
            ->postJson("/api/dispatches/{$dispatchId}/assign-personnel", ['release_assigned_to' => $staffB])
            ->assertStatus(403);

        $this->assertSame($staffA, (int) DB::table('dispatches')->where('id', $dispatchId)->value('release_assigned_to'));
    }

    // -----------------------------------------------------------------
    // 5. Staff visibility scoping
    // -----------------------------------------------------------------

    public function test_maintenance_staff_only_see_dispatches_assigned_to_them(): void
    {
        $staffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $otherStaffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 30, 'reserved_quantity' => 0]);
        $mine = $this->seedDispatch($itemId, 1, ['release_assigned_to' => $staffId]);
        $this->seedDispatch($itemId, 1, ['release_assigned_to' => $otherStaffId]);
        $this->seedDispatch($itemId, 1);

        $response = $this
            ->actingAsSessionUserWithFlatKeys($staffId, 'maintenance_staff')
            ->getJson('/api/dispatches');

        $response->assertOk();
        // index() returns the paginator itself under `data`, so the rows live
        // at data.data.
        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertSame([$mine], $ids);
    }

    public function test_maintenance_staff_cannot_read_a_dispatch_not_assigned_to_them(): void
    {
        $staffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $otherStaffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);
        $dispatchId = $this->seedDispatch($itemId, 1, ['release_assigned_to' => $otherStaffId]);

        $this->actingAsSessionUserWithFlatKeys($staffId, 'maintenance_staff')
            ->getJson("/api/dispatches/{$dispatchId}")
            ->assertStatus(403);
    }

    public function test_head_and_administrator_still_see_every_dispatch(): void
    {
        $headId = $this->seedUser(['role' => 'maintenance_admin', 'department_id' => self::DEPT_COMPUTER]);
        $adminId = $this->seedUser(['role' => 'super_admin']);
        $staffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $otherStaffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_ELECTRICAL]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 30, 'reserved_quantity' => 0]);
        $this->seedDispatch($itemId, 1, ['release_assigned_to' => $staffId]);
        $this->seedDispatch($itemId, 1, ['release_assigned_to' => $otherStaffId]);

        foreach ([[$headId, 'maintenance_admin'], [$adminId, 'super_admin']] as [$userId, $role]) {
            $response = $this
                ->actingAsSessionUserWithFlatKeys($userId, $role)
                ->getJson('/api/dispatches');

            $response->assertOk();
            $this->assertCount(2, $response->json('data.data'), "Monitoring must not be narrowed for {$role}.");
        }
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * @return array{0:int,1:int,2:int,3:int} [adminId, assignedStaffId, dispatchId, itemId]
     */
    private function seedApprovedDispatch(): array
    {
        $adminId = $this->seedUser(['role' => 'super_admin']);
        $staffId = $this->seedUser(['role' => 'maintenance_staff', 'department_id' => self::DEPT_COMPUTER]);
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 10, 'reserved_quantity' => 0]);
        $dispatchId = $this->seedDispatch($itemId, 2, ['release_assigned_to' => $staffId]);

        $this->actingAsSessionUserWithFlatKeys($adminId, 'super_admin')
            ->postJson("/api/dispatches/{$dispatchId}/approve", ['approved_by' => $adminId])
            ->assertOk();

        return [$adminId, $staffId, $dispatchId, $itemId];
    }

    private function seedDispatch(int $itemId, int $quantity, array $overrides = []): int
    {
        $dispatchId = DB::table('dispatches')->insertGetId(array_merge([
            'dispatch_code' => 'DSP-' . uniqid('', true),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        DB::table('dispatch_items')->insert([
            'dispatch_id' => $dispatchId,
            'item_id' => $itemId,
            'quantity' => $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $dispatchId;
    }

    private function createTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('dispatch_items');
        Schema::dropIfExists('dispatches');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('maintenance_reports');
        Schema::dropIfExists('items');
        Schema::dropIfExists('users');

        $this->createUsersTable();
        $this->createItemsTable();
        $this->createInventoryTransactionsTable();
        $this->createActivityLogsTable();
        $this->createMaintenanceReportsTable();

        Schema::table('inventory_transactions', function (Blueprint $table): void {
            $table->unsignedBigInteger('dispatch_id')->nullable()->after('report_id');
        });

        $this->createDispatchesTable();
        $this->createDispatchItemsTable();

        Schema::enableForeignKeyConstraints();
    }
}
