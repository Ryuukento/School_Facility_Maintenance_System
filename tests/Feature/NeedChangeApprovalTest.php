<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsSharedTestSchema;
use Tests\Support\ConfiguresIsolatedSqliteConnection;
use Tests\Support\InteractsWithLegacySession;
use Tests\TestCase;

class NeedChangeApprovalTest extends TestCase
{
    use BuildsSharedTestSchema;
    use ConfiguresIsolatedSqliteConnection;
    use InteractsWithLegacySession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useInMemoryDatabase('need_change_approval_testing');
        $this->createTestSchema();

        // See ConfiguresIsolatedSqliteConnection::forceLocalTestUrl() for why
        // this is needed for HTTP test requests in this environment.
        $this->forceLocalTestUrl();
    }

    public function test_approving_valid_need_change_request_deducts_inventory_through_ledger(): void
    {
        $approverId = $this->seedUser(['role' => 'super_admin']);
        $itemId = $this->seedItem(['name' => 'Projector Bulb', 'quantity' => 20]);
        $reportId = $this->seedReport([
            'need_change_item_id' => $itemId,
            'need_change_quantity' => 5,
            'need_change_status' => 'pending',
        ]);

        $response = $this
            ->actingAsSessionUser($approverId, 'super_admin')
            ->patchJson("/api/reports/{$reportId}", ['approve_need_change' => true]);

        $response->assertOk();

        $this->assertSame(1, DB::table('inventory_transactions')->where('report_id', $reportId)->count());

        $tx = DB::table('inventory_transactions')->where('report_id', $reportId)->first();
        $this->assertSame('deploy', $tx->transaction_type);
        $this->assertSame(5, (int) $tx->quantity);
        $this->assertSame($itemId, (int) $tx->item_id);

        $this->assertSame(15, (int) DB::table('items')->where('id', $itemId)->value('quantity'));

        $report = DB::table('maintenance_reports')->where('report_id', $reportId)->first();
        $this->assertSame('deducted', $report->need_change_status);
        $this->assertSame($approverId, (int) $report->need_change_approved_by);
        $this->assertNotNull($report->need_change_approved_at);
        $this->assertNotNull($report->need_change_deducted_at);
    }

    public function test_approving_same_request_twice_deducts_inventory_only_once(): void
    {
        $approverId = $this->seedUser(['role' => 'super_admin']);
        $itemId = $this->seedItem(['name' => 'Ceiling Fan Blade', 'quantity' => 12]);
        $reportId = $this->seedReport([
            'need_change_item_id' => $itemId,
            'need_change_quantity' => 3,
            'need_change_status' => 'pending',
        ]);

        $first = $this
            ->actingAsSessionUser($approverId, 'super_admin')
            ->patchJson("/api/reports/{$reportId}", ['approve_need_change' => true]);
        $first->assertOk();

        $afterFirst = DB::table('maintenance_reports')->where('report_id', $reportId)->first();
        $qtyAfterFirst = (int) DB::table('items')->where('id', $itemId)->value('quantity');

        $second = $this
            ->actingAsSessionUser($approverId, 'super_admin')
            ->patchJson("/api/reports/{$reportId}", ['approve_need_change' => true]);
        $second->assertOk();

        $afterSecond = DB::table('maintenance_reports')->where('report_id', $reportId)->first();
        $qtyAfterSecond = (int) DB::table('items')->where('id', $itemId)->value('quantity');

        // Only one InventoryTransaction ever created — the second approval is a no-op.
        $this->assertSame(1, DB::table('inventory_transactions')->where('report_id', $reportId)->count());

        // Stock deducted exactly once (12 - 3 = 9), unchanged by the second call.
        $this->assertSame(9, $qtyAfterFirst);
        $this->assertSame($qtyAfterFirst, $qtyAfterSecond);

        // Timestamps stamped by the first approval are not touched by the second.
        $this->assertSame($afterFirst->need_change_approved_at, $afterSecond->need_change_approved_at);
        $this->assertSame($afterFirst->need_change_deducted_at, $afterSecond->need_change_deducted_at);
        $this->assertSame('deducted', $afterSecond->need_change_status);
    }

    public function test_approving_request_with_no_need_change_item_fails_validation(): void
    {
        $approverId = $this->seedUser(['role' => 'super_admin']);
        $reportId = $this->seedReport([
            'need_change_item_id' => null,
            'need_change_status' => 'pending',
        ]);

        $response = $this
            ->actingAsSessionUser($approverId, 'super_admin')
            ->patchJson("/api/reports/{$reportId}", ['approve_need_change' => true]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'This report has no Need Change request to process.',
        ]);

        $this->assertSame(0, DB::table('inventory_transactions')->where('report_id', $reportId)->count());

        $report = DB::table('maintenance_reports')->where('report_id', $reportId)->first();
        $this->assertSame('pending', $report->need_change_status);
        $this->assertNull($report->need_change_approved_at);
        $this->assertNull($report->need_change_approved_by);
        $this->assertNull($report->need_change_deducted_at);
    }

    public function test_approving_request_with_insufficient_stock_fails_cleanly(): void
    {
        $approverId = $this->seedUser(['role' => 'super_admin']);
        $itemId = $this->seedItem(['name' => 'Rare Sensor', 'quantity' => 2]);
        $reportId = $this->seedReport([
            'need_change_item_id' => $itemId,
            'need_change_quantity' => 5,
            'need_change_status' => 'pending',
        ]);

        $response = $this
            ->actingAsSessionUser($approverId, 'super_admin')
            ->patchJson("/api/reports/{$reportId}", ['approve_need_change' => true]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Insufficient stock for approval. Available: 2, required: 5',
        ]);

        $this->assertSame(0, DB::table('inventory_transactions')->where('report_id', $reportId)->count());
        $this->assertSame(2, (int) DB::table('items')->where('id', $itemId)->value('quantity'));

        $report = DB::table('maintenance_reports')->where('report_id', $reportId)->first();
        $this->assertSame('pending', $report->need_change_status);
        $this->assertNull($report->need_change_approved_at);
        $this->assertNull($report->need_change_deducted_at);
    }

    private function createTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('maintenance_reports');
        Schema::dropIfExists('items');
        Schema::dropIfExists('users');

        $this->createUsersTable();
        $this->createItemsTable();
        $this->createMaintenanceReportsTable();
        $this->createInventoryTransactionsTable();
        $this->createActivityLogsTable();

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Not moved into the shared BuildsSharedTestSchema trait: this file's
     * default 'need_change_status' ('pending') differs from
     * ReportsApiNeedChangeTest's default (null), and several call sites in
     * both files rely on their own file's default rather than always
     * overriding it. Consolidating the two would silently change one file's
     * seeded default, which risks changing test-observable behavior.
     */
    private function seedReport(array $overrides = []): int
    {
        return DB::table('maintenance_reports')->insertGetId(array_merge([
            'title' => 'Broken Chair',
            'description' => 'Chair leg is broken.',
            'location' => 'Room 101',
            'priority' => 'medium',
            'status' => 'submitted',
            'created_by' => $this->seedUser(),
            'assigned_to' => null,
            'department_id' => null,
            'due_date' => null,
            'completed_date' => null,
            'need_change_item_id' => null,
            'need_change_quantity' => 1,
            'need_change_status' => 'pending',
            'need_change_approved_by' => null,
            'need_change_approved_at' => null,
            'need_change_deducted_at' => null,
            'completion_proof_image' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides), 'report_id');
    }
}
