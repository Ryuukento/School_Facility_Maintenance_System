<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use ApiResponder;

    public function index()
    {
        $users = User::query()
            ->select(['user_id', 'full_name', 'email', 'role', 'status', 'avatar', 'created_at'])
            ->whereRaw('LOWER(role) <> ?', ['super_admin'])
            ->orderByDesc('created_at')
            ->get();

        return $this->ok('Users retrieved', ['users' => $users]);
    }

    public function deactivate(Request $request, User $user)
    {
        $authUser = $request->session()->get('auth_user', []);
        if ((int)($authUser['user_id'] ?? 0) === (int)$user->user_id) {
            return $this->fail('Cannot set your own account to inactive', 400);
        }

        if (strtolower((string)$user->role) === 'super_admin') {
            return $this->fail('Super Admin accounts cannot be changed here', 400);
        }

        if (strtolower((string)$user->status) === 'inactive') {
            return $this->ok('User is already inactive');
        }

        $user->update(['status' => 'inactive']);
        $this->logStatusChange($request, 'INACTIVATE_USER', (int)$user->user_id, 'Set user #' . $user->user_id . ' to inactive');

        return $this->ok('User set to inactive successfully');
    }

    public function activate(Request $request, User $user)
    {
        if (strtolower((string)$user->role) === 'super_admin') {
            return $this->fail('Super Admin accounts cannot be changed here', 400);
        }

        if (strtolower((string)$user->status) === 'active') {
            return $this->ok('User is already active');
        }

        $user->update(['status' => 'active']);
        $this->logStatusChange($request, 'ACTIVATE_USER', (int)$user->user_id, 'Set user #' . $user->user_id . ' to active');

        return $this->ok('User set to active successfully');
    }

    public function approve(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:maintenance_admin,maintenance_staff'],
        ]);

        if (strtolower((string)$user->status) === 'active') {
            return $this->fail('User is already approved', 400);
        }

        $user->update([
            'role' => $validated['role'],
            'status' => 'active',
        ]);

        $this->logStatusChange(
            $request,
            'APPROVE_USER',
            (int)$user->user_id,
            'Approved user #' . $user->user_id . ' and assigned role ' . $validated['role']
        );

        return $this->ok('User approved successfully', [
            'user_id' => (int)$user->user_id,
            'role' => $validated['role'],
            'status' => 'active',
        ]);
    }

    public function reject(Request $request, User $user)
    {
        $authUser = $request->session()->get('auth_user', []);
        if ((int)($authUser['user_id'] ?? 0) === (int)$user->user_id) {
            return $this->fail('Cannot reject your own account', 400);
        }

        if (strtolower((string)$user->role) === 'super_admin') {
            return $this->fail('Super Admin accounts cannot be rejected here', 400);
        }

        if (strtolower((string)$user->status) !== 'pending') {
            return $this->fail('Only pending users can be rejected', 400);
        }

        $targetUserId = (int)$user->user_id;

        DB::transaction(function () use ($targetUserId): void {
            ActivityLog::query()->where('user_id', $targetUserId)->delete();
            User::query()->where('user_id', $targetUserId)->delete();
        });

        $this->logStatusChange(
            $request,
            'REJECT_USER',
            $targetUserId,
            'Rejected pending user #' . $targetUserId . ' and deleted account'
        );

        return $this->ok('Pending user rejected and account deleted successfully');
    }

    private function logStatusChange(Request $request, string $action, int $targetUserId, string $details): void
    {
        $authUser = $request->session()->get('auth_user', []);

        ActivityLog::query()->create([
            'user_id' => (int)($authUser['user_id'] ?? 0),
            'action' => $action,
            'entity_type' => 'user',
            'entity_id' => $targetUserId,
            'details' => $details,
            'ip_address' => (string)$request->ip(),
        ]);
    }
}
