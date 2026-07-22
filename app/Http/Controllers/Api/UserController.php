<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponder;

    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name'     => ['required', 'string', 'max:255'],
            'username'      => ['required', 'string', 'min:3', 'max:50', 'unique:users,username'],
            'email'         => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'string', 'min:8'],
            'role'          => ['required', 'string'],
            'department_id' => ['nullable', 'integer', 'exists:departments,department_id'],
            'designation'   => ['nullable', 'string', 'max:255'],
        ]);

        $userId = DB::table('users')->insertGetId([
            'full_name'            => $validated['full_name'],
            'username'             => strtolower($validated['username']),
            'email'                => ($validated['email'] ?? '') !== '' ? $validated['email'] : null,
            'password'             => Hash::make($validated['password']),
            'role'                 => $validated['role'],
            'status'               => 'active',
            'department_id'        => $validated['department_id'] ?? null,
            'designation'          => $validated['designation'] ?? null,
            'force_profile_update' => 1,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return $this->ok('User created successfully', ['user_id' => $userId], 201);
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        $user->update([
            'password'             => Hash::make($validated['new_password']),
            'force_profile_update' => 1,
        ]);

        return $this->ok('Password reset successfully');
    }

    public function index()
    {
        $users = DB::table('users as u')
            ->select([
                'u.user_id', 'u.full_name', 'u.username', 'u.email', 'u.role',
                'u.status', 'u.avatar', 'u.created_at',
                'd.name as department_name',
            ])
            ->leftJoin('departments as d', 'd.department_id', '=', 'u.department_id')
            ->whereRaw('LOWER(u.role) <> ?', ['super_admin'])
            ->orderByDesc('u.created_at')
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
            return $this->fail('Administrator accounts cannot be changed here', 400);
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
            return $this->fail('Administrator accounts cannot be changed here', 400);
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
            return $this->fail('Administrator accounts cannot be rejected here', 400);
        }

        if (strtolower((string)$user->status) !== 'pending') {
            return $this->fail('Only pending users can be rejected', 400);
        }

        $targetUserId = (int)$user->user_id;

        DB::transaction(function () use ($targetUserId): void {
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

    public function updateProfile(Request $request)
    {
        $authUser = $request->session()->get('auth_user') ?? $request->session()->get('user', []);
        $userId   = (int)(is_array($authUser) ? ($authUser['user_id'] ?? 0) : 0);
        if ($userId <= 0) {
            return $this->fail('Unauthorized', 401);
        }

        $currentUser = DB::table('users')->where('user_id', $userId)->first();
        if (!$currentUser) {
            return $this->fail('User not found', 404);
        }

        $fullName        = trim((string)$request->input('full_name', $currentUser->full_name ?? ''));
        $username        = strtolower(trim((string)$request->input('username', '')));
        $email           = trim((string)$request->input('email_address', $currentUser->email ?? ''));
        $currentPassword = (string)$request->input('current_password', '');
        $newPassword     = trim((string)$request->input('new_password', ''));
        $isForcedSetup   = !empty($currentUser->force_profile_update);

        if ($fullName === '') {
            return $this->fail('Full name is required', 422);
        }

        if ($username !== '' && !preg_match('/^[a-z0-9_]{3,50}$/', $username)) {
            return $this->fail('Username must be 3-50 characters: lowercase letters, numbers, underscores only', 422);
        }

        if ($username !== '' && $username !== ($currentUser->username ?? '')) {
            $taken = DB::table('users')
                ->where('username', $username)
                ->where('user_id', '!=', $userId)
                ->exists();
            if ($taken) {
                return $this->fail('Username is already taken', 422);
            }
        }

        if ($isForcedSetup && $newPassword === '') {
            return $this->fail('For first-time account setup, setting a new password is required', 422);
        }

        $passwordHash = null;
        if ($newPassword !== '') {
            if ($currentPassword === '') {
                return $this->fail('Current password is required to change your password', 422);
            }
            if (!Hash::check($currentPassword, $currentUser->password ?? '')) {
                return $this->fail('Current password is incorrect', 422);
            }
            if (strlen($newPassword) < 8) {
                return $this->fail('New password must be at least 8 characters', 422);
            }
            $passwordHash = Hash::make($newPassword);
        }

        $avatarPath = $currentUser->avatar ?? null;
        if ($request->hasFile('profile_picture')) {
            $file         = $request->file('profile_picture');
            $allowedMimes = [
                'image/jpeg' => 'jpg', 'image/jpg' => 'jpg',
                'image/png'  => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
            ];
            $mime = $file->getMimeType();
            if (!isset($allowedMimes[$mime])) {
                return $this->fail('Only JPG, PNG, WEBP, or GIF images are allowed', 422);
            }
            if ($file->getSize() > 3 * 1024 * 1024) {
                return $this->fail('Profile picture must be 3MB or less', 422);
            }
            $uploadDir = public_path('frontend/assets/uploads/avatars');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $ext      = $allowedMimes[$mime];
            $fileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $file->move($uploadDir, $fileName);

            if (!empty($avatarPath) && str_contains((string)$avatarPath, '/frontend/assets/uploads/avatars/')) {
                $oldPath = public_path(str_replace('/School_Facility_Maintenance_System/', '', (string)$avatarPath));
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $avatarPath = '/School_Facility_Maintenance_System/frontend/assets/uploads/avatars/' . $fileName;
        }

        $updates = [
            'full_name'            => $fullName,
            'username'             => $username !== '' ? $username : ($currentUser->username ?? null),
            'email'                => $email !== '' ? $email : null,
            'avatar'               => $avatarPath,
            'force_profile_update' => 0,
            'updated_at'           => now(),
        ];
        if ($passwordHash) {
            $updates['password'] = $passwordHash;
        }

        try {
            DB::table('users')->where('user_id', $userId)->update($updates);
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int)$e->getCode() === 23000) {
                return $this->fail('Email address is already in use', 422);
            }
            throw $e;
        }

        $updatedAuthUser = array_merge((array)$authUser, [
            'full_name' => $fullName,
            'avatar'    => $avatarPath,
        ]);
        $request->session()->put('auth_user', $updatedAuthUser);
        $request->session()->put('user', $updatedAuthUser);

        return $this->ok('Profile updated successfully', [
            'user' => [
                'user_id'              => $userId,
                'full_name'            => $fullName,
                'avatar'               => $avatarPath,
                'force_profile_update' => 0,
            ],
        ]);
    }

    private function logStatusChange(Request $request, string $action, int $targetUserId, string $details): void
    {
        $authUser = $request->session()->get('auth_user', []);

        $this->activityLogService->log([
            'user_id' => (int)($authUser['user_id'] ?? 0),
            'user_role' => (string)($authUser['role'] ?? $request->session()->get('role', '')),
            'action' => $action,
            'module' => 'user_management',
            'entity_type' => 'user',
            'entity_id' => $targetUserId,
            'details' => $details,
        ], $request);
    }
}
