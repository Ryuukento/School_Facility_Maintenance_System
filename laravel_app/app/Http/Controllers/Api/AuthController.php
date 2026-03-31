<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponder;

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->fail('Invalid email or password', 401);
        }

        if (strtolower((string)$user->status) === 'pending') {
            return $this->fail('Your account is pending approval. Please wait for Super Admin approval.', 403);
        }

        if (strtolower((string)$user->status) !== 'active') {
            return $this->fail('Account is inactive', 403);
        }

        $request->session()->put('auth_user', [
            'user_id' => $user->user_id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => $this->normalizeRoleAlias($user->role),
            'status' => $user->status,
            'department_id' => $user->department_id,
            'avatar' => $user->avatar,
        ]);

        // Also set 'user' for backward compatibility with old PHP code
        $request->session()->put('user', [
            'user_id' => $user->user_id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => $this->normalizeRoleAlias($user->role),
            'status' => $user->status,
            'department_id' => $user->department_id,
            'avatar' => $user->avatar,
        ]);

        // Legacy PHP APIs depend on top-level session keys.
        $request->session()->put('user_id', $user->user_id);
        $request->session()->put('role', $this->normalizeRoleAlias($user->role));
        $request->session()->put('last_activity', time());

        ActivityLog::query()->create([
            'user_id' => $user->user_id,
            'action' => 'LOGIN',
            'entity_type' => 'user',
            'entity_id' => $user->user_id,
            'details' => 'User logged in',
            'ip_address' => (string)$request->ip(),
        ]);

        return $this->ok('Login successful', ['user' => $request->session()->get('auth_user')]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $departmentId = Department::query()
            ->where('status', 'active')
            ->orderBy('department_id')
            ->value('department_id');

        $user = User::query()->create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'department_id' => $departmentId,
            'status' => 'pending',
        ]);

        ActivityLog::query()->create([
            'user_id' => $user->user_id,
            'action' => 'REGISTER',
            'entity_type' => 'user',
            'entity_id' => $user->user_id,
            'details' => 'Self-registered account (pending approval)',
            'ip_address' => (string)$request->ip(),
        ]);

        return $this->ok('Registration submitted. Your account is pending Super Admin approval.', [
            'user_id' => $user->user_id,
            'email' => $user->email,
            'status' => $user->status,
        ], 201);
    }

    public function logout(Request $request)
    {
        $authUser = $request->session()->get('auth_user') ?? $request->session()->get('user');

        if (is_array($authUser) && isset($authUser['user_id'])) {
            ActivityLog::query()->create([
                'user_id' => $authUser['user_id'],
                'action' => 'LOGOUT',
                'entity_type' => 'user',
                'entity_id' => $authUser['user_id'],
                'details' => 'User logged out',
                'ip_address' => (string)$request->ip(),
            ]);
        }

        $request->session()->forget('auth_user');
        $request->session()->forget('user');
        $request->session()->forget('user_id');
        $request->session()->forget('role');
        $request->session()->forget('last_activity');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->ok('Logout successful');
    }

    public function check(Request $request)
    {
        $user = $request->session()->get('auth_user') ?? $request->session()->get('user');
        return $this->ok('Session active', ['user' => $user]);
    }

    private function normalizeRoleAlias(?string $role): string
    {
        $normalized = strtolower(trim((string)$role));

        if ($normalized === 'admin_maintenance') {
            return 'maintenance_admin';
        }

        if ($normalized === 'eelab_staff' || $normalized === 'maintenance_personnel' || $normalized === '') {
            return 'maintenance_staff';
        }

        return $normalized;
    }
}
