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
 * Covers the user-management security gaps documented in
 * SYSTEM_FLOW_REVIEW.md §6: GET /api/users had no role guard at all, and
 * the Approve User modal offered a role ("Admin"/super_admin) that the
 * backend validator rejects. Verifies the fix reuses the existing
 * EnsureRole middleware (super_admin-only, per BUSINESS_RULES.md §1
 * "Manage users") rather than introducing a new permission system.
 */
class UserManagementAuthorizationTest extends TestCase
{
    use BuildsSharedTestSchema;
    use ConfiguresIsolatedSqliteConnection;
    use InteractsWithLegacySession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useInMemoryDatabase('user_management_authorization_testing');
        $this->createTestSchema();

        $this->forceLocalTestUrl();
    }

    public function test_super_admin_can_list_users(): void
    {
        $adminId = $this->seedUser(['role' => 'super_admin']);
        $this->seedUser(['role' => 'maintenance_staff', 'status' => 'pending']);

        $response = $this
            ->actingAsSessionUser($adminId, 'super_admin')
            ->getJson('/api/users');

        $response->assertOk();
    }

    public function test_maintenance_admin_cannot_list_users(): void
    {
        $userId = $this->seedUser(['role' => 'maintenance_admin']);

        $response = $this
            ->actingAsSessionUser($userId, 'maintenance_admin')
            ->getJson('/api/users');

        $response->assertForbidden();
    }

    public function test_maintenance_staff_cannot_list_users(): void
    {
        $userId = $this->seedUser(['role' => 'maintenance_staff']);

        $response = $this
            ->actingAsSessionUser($userId, 'maintenance_staff')
            ->getJson('/api/users');

        $response->assertForbidden();
    }

    public function test_unauthenticated_request_cannot_list_users(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertUnauthorized();
    }

    public function test_super_admin_can_approve_a_pending_user(): void
    {
        $adminId = $this->seedUser(['role' => 'super_admin']);
        $pendingId = $this->seedUser(['role' => 'maintenance_staff', 'status' => 'pending']);

        $response = $this
            ->actingAsSessionUser($adminId, 'super_admin')
            ->patchJson("/api/users/{$pendingId}/approve", ['role' => 'maintenance_admin']);

        $response->assertOk();
        $this->assertSame('active', DB::table('users')->where('user_id', $pendingId)->value('status'));
    }

    public function test_maintenance_admin_cannot_approve_a_pending_user(): void
    {
        $requesterId = $this->seedUser(['role' => 'maintenance_admin']);
        $pendingId = $this->seedUser(['role' => 'maintenance_staff', 'status' => 'pending']);

        $response = $this
            ->actingAsSessionUser($requesterId, 'maintenance_admin')
            ->patchJson("/api/users/{$pendingId}/approve", ['role' => 'maintenance_staff']);

        $response->assertForbidden();
    }

    public function test_maintenance_staff_cannot_delete_a_pending_user(): void
    {
        $requesterId = $this->seedUser(['role' => 'maintenance_staff']);
        $pendingId = $this->seedUser(['role' => 'maintenance_staff', 'status' => 'pending']);

        $response = $this
            ->actingAsSessionUser($requesterId, 'maintenance_staff')
            ->deleteJson("/api/users/{$pendingId}/reject");

        $response->assertForbidden();
        $this->assertNotNull(DB::table('users')->where('user_id', $pendingId)->first());
    }

    public function test_maintenance_admin_cannot_edit_a_privileged_user(): void
    {
        $requesterId = $this->seedUser(['role' => 'maintenance_admin']);
        $superAdminId = $this->seedUser(['role' => 'super_admin']);

        $response = $this
            ->actingAsSessionUser($requesterId, 'maintenance_admin')
            ->patchJson("/api/users/{$superAdminId}/deactivate");

        $response->assertForbidden();
    }

    /**
     * The Approve User modal must only ever submit roles the backend
     * validator accepts (maintenance_admin / maintenance_staff). This locks
     * in the backend contract so the frontend fix (removing the "Admin"
     * option) cannot silently drift out of sync again.
     */
    public function test_approve_endpoint_rejects_a_super_admin_role_assignment(): void
    {
        $adminId = $this->seedUser(['role' => 'super_admin']);
        $pendingId = $this->seedUser(['role' => 'maintenance_staff', 'status' => 'pending']);

        $response = $this
            ->actingAsSessionUser($adminId, 'super_admin')
            ->patchJson("/api/users/{$pendingId}/approve", ['role' => 'super_admin']);

        $response->assertStatus(422);
        $this->assertSame('pending', DB::table('users')->where('user_id', $pendingId)->value('status'));
    }

    public function test_frontend_approve_modal_only_offers_backend_accepted_roles(): void
    {
        $markup = file_get_contents(base_path('public/frontend/pages/users.php'));
        $this->assertNotFalse($markup);

        $modalStart = strpos($markup, 'id="approve-user-role"');
        $this->assertNotFalse($modalStart, 'Approve User role select not found.');

        $modalEnd = strpos($markup, '</select>', $modalStart);
        $modalMarkup = substr($markup, $modalStart, $modalEnd - $modalStart);

        $this->assertStringContainsString('maintenance_staff', $modalMarkup);
        $this->assertStringContainsString('maintenance_admin', $modalMarkup);
        $this->assertStringNotContainsString('super_admin', $modalMarkup, 'Approve modal must not offer a role the backend rejects.');
    }

    private function createTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('users');

        $this->createUsersTable();
        $this->createDepartmentsTable();
        $this->createActivityLogsTable();

        // UserController's index()/store() reference columns (username,
        // force_profile_update) that exist on the real (legacy-originated)
        // users table but aren't part of BuildsSharedTestSchema's minimal
        // users table, since no other Feature test needs them.
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 50)->nullable()->unique();
            $table->boolean('force_profile_update')->default(0);
        });

        Schema::enableForeignKeyConstraints();
    }
}
