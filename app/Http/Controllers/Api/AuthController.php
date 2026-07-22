<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\RoleNormalizerService;
use App\Support\ApiResponder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

require_once __DIR__ . '/../../../../public/backend/services/EmailService.php';

class AuthController extends Controller
{
    use ApiResponder;

    private const MAX_LOGIN_ATTEMPTS = 3;
    private const LOGIN_LOCKOUT_SECONDS = 300;
    private const RESET_CODE_EXPIRY_MINUTES = 15;
    private const RESET_REQUEST_LOCKOUT_SECONDS = 60;

    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'string', 'min:3', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $identifier  = $validated['email'];
        $throttleKey = $this->throttleKey($identifier, (string) $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $retryAfterSeconds = RateLimiter::availableIn($throttleKey);

            return $this->fail('Too many login attempts. Please try again later.', 429, [
                'max_attempts' => self::MAX_LOGIN_ATTEMPTS,
                'retry_after_seconds' => $retryAfterSeconds,
            ]);
        }

        // Accept username OR email — try email first, then username
        $user = User::query()->where('email', $identifier)->first()
             ?? User::query()->where('username', $identifier)->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->failedLoginResponse($throttleKey, 'Invalid username/email or password', 401);
        }

        if (strtolower((string)$user->status) === 'pending') {
            return $this->failedLoginResponse(
                $throttleKey,
                'Your account is pending approval. Please wait for Administrator approval.',
                403
            );
        }

        if (strtolower((string)$user->status) !== 'active') {
            return $this->failedLoginResponse($throttleKey, 'Account is inactive', 403);
        }

        RateLimiter::clear($throttleKey);

        $sessionUser = [
            'user_id'       => $user->user_id,
            'full_name'     => $user->full_name,
            'email'         => $user->email,
            'role'          => $this->normalizeRoleAlias($user->role),
            'status'        => $user->status,
            'department_id' => $user->department_id,
            'avatar'        => $user->avatar,
        ];

        $request->session()->put('auth_user', $sessionUser);

        // Also set 'user' for backward compatibility with old PHP code
        $request->session()->put('user', $sessionUser);

        // Legacy PHP APIs depend on top-level session keys.
        $request->session()->put('user_id', $user->user_id);
        $request->session()->put('role', $this->normalizeRoleAlias($user->role));
        $request->session()->put('last_activity', time());

        // Sync to native PHP $_SESSION so plain-PHP frontend pages can read the
        // user without going through Laravel's session store.
        // SyncLegacyPhpSession middleware has already called session_start() before
        // this controller runs, so $_SESSION is writable here.
        $_SESSION['auth_user']     = $sessionUser;
        $_SESSION['user']          = $sessionUser;
        $_SESSION['user_id']       = $user->user_id;
        $_SESSION['role']          = $this->normalizeRoleAlias($user->role);
        $_SESSION['last_activity'] = time();

        $this->activityLogService->log([
            'user_id' => $user->user_id,
            'user_role' => $this->normalizeRoleAlias($user->role),
            'action' => 'LOGIN',
            'module' => 'auth',
            'entity_type' => 'user',
            'entity_id' => $user->user_id,
            'details' => 'User logged in successfully.',
        ], $request);

        return $this->ok('Login successful', ['user' => $request->session()->get('auth_user')]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'max:255', "regex:/^(?=.*\\p{L})[\\p{L} .'-]+$/u"],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'full_name.regex' => 'Name must contain only letters, spaces, apostrophes, and hyphens (no numbers).',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation error', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $validated = $validator->validated();

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

        $this->activityLogService->log([
            'user_id' => $user->user_id,
            'user_role' => 'user',
            'action' => 'REGISTER',
            'module' => 'auth',
            'entity_type' => 'user',
            'entity_id' => $user->user_id,
            'details' => 'Self-registered account (pending approval).',
        ], $request);

        return $this->ok('Registration submitted. Your account is pending Administrator approval.', [
            'user_id' => $user->user_id,
            'email' => $user->email,
            'status' => $user->status,
        ], 201);
    }

    public function forgotPasswordRequest(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($validated['email']));
        $throttleKey = 'forgot-password-request|' . $email . '|' . (string)$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            return $this->fail('Please wait before sending another reset request.', 429, [
                'retry_after_seconds' => RateLimiter::availableIn($throttleKey),
            ]);
        }

        RateLimiter::hit($throttleKey, self::RESET_REQUEST_LOCKOUT_SECONDS);

        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            return $this->ok('If this email is registered, Administrator has been notified.');
        }

        try {
            $notificationColumns = collect(DB::select('SHOW COLUMNS FROM notifications'))
                ->map(fn ($column) => strtolower((string)($column->Field ?? '')))
                ->all();
            $hasReportIdColumn = in_array('report_id', $notificationColumns, true);

            $superAdmins = DB::table('users')
                ->select('user_id', 'full_name', 'email')
                ->whereIn(DB::raw('LOWER(TRIM(role))'), ['super_admin', 'super admin'])
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhereRaw('LOWER(TRIM(status)) = ?', ['active']);
                })
                ->get();

            foreach ($superAdmins as $admin) {
                $payload = [
                    'user_id' => $admin->user_id,
                    'title' => 'Password Reset Request',
                    'message' => ($user->full_name ?: $user->email) . ' requested a password reset. Please update the password in User Management.',
                    'is_read' => 0,
                    'created_at' => now(),
                ];

                if ($hasReportIdColumn) {
                    $payload['report_id'] = null;
                }

                DB::table('notifications')->insert($payload);
            }

            $this->activityLogService->log([
                'user_id' => $user->user_id,
                'user_role' => $this->normalizeRoleAlias((string) $user->role),
                'action' => 'PASSWORD_RESET_REQUEST',
                'module' => 'auth',
                'entity_type' => 'user',
                'entity_id' => $user->user_id,
                'details' => 'Requested Administrator password reset assistance.',
            ], $request);

            return $this->ok('Administrator has been notified to reset your password.');
        } catch (\Throwable $e) {
            \Log::warning('Password reset request notification exception', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return $this->fail('Unable to notify Administrator right now. Please try again later.', 500);
        }
    }

    public function forgotPasswordReset(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'reset_code' => ['required', 'regex:/^\d{6}$/'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $email = strtolower(trim($validated['email']));
        $resetCode = trim((string)$validated['reset_code']);

        $tokenRow = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$tokenRow) {
            return $this->fail('Invalid or expired reset code.', 422);
        }

        $createdAt = Carbon::parse((string)$tokenRow->created_at);
        if ($createdAt->lt(Carbon::now()->subMinutes(self::RESET_CODE_EXPIRY_MINUTES))) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return $this->fail('Reset code has expired. Please request a new one.', 422);
        }

        $expectedHash = (string)$tokenRow->token;
        $providedHash = $this->hashResetCode($email, $resetCode);

        if (!hash_equals($expectedHash, $providedHash)) {
            return $this->fail('Invalid or expired reset code.', 422);
        }

        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            return $this->fail('User account not found.', 404);
        }

        $user->password = Hash::make((string)$validated['password']);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $this->activityLogService->log([
            'user_id' => $user->user_id,
            'user_role' => $this->normalizeRoleAlias((string) $user->role),
            'action' => 'PASSWORD_RESET',
            'module' => 'auth',
            'entity_type' => 'user',
            'entity_id' => $user->user_id,
            'details' => 'Password reset completed through forgot password flow.',
        ], $request);

        return $this->ok('Password reset successful. You can now sign in with your new password.');
    }

    public function logout(Request $request)
    {
        $authUser = $request->session()->get('auth_user') ?? $request->session()->get('user');

        if (is_array($authUser) && isset($authUser['user_id'])) {
            $this->activityLogService->log([
                'user_id' => (int) $authUser['user_id'],
                'user_role' => (string) ($authUser['role'] ?? ''),
                'action' => 'LOGOUT',
                'module' => 'auth',
                'entity_type' => 'user',
                'entity_id' => (int) $authUser['user_id'],
                'details' => 'User logged out.',
            ], $request);
        }

        // Clear native PHP $_SESSION so legacy frontend pages lose access too
        $_SESSION = [];

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
        return RoleNormalizerService::normalizeWithStaffDefault($role);
    }

    private function throttleKey(string $email, string $ipAddress): string
    {
        return strtolower($email) . '|' . $ipAddress;
    }

    private function hashResetCode(string $email, string $resetCode): string
    {
        return hash('sha256', strtolower(trim($email)) . '|' . trim($resetCode) . '|sfms-reset-code');
    }

    private function failedLoginResponse(string $throttleKey, string $message, int $status)
    {
        RateLimiter::hit($throttleKey, self::LOGIN_LOCKOUT_SECONDS);

        $attemptsUsed = RateLimiter::attempts($throttleKey);
        $attemptsRemaining = max(0, self::MAX_LOGIN_ATTEMPTS - $attemptsUsed);

        if ($attemptsUsed >= self::MAX_LOGIN_ATTEMPTS) {
            return $this->fail('Too many login attempts. Please try again later.', 429, [
                'max_attempts' => self::MAX_LOGIN_ATTEMPTS,
                'retry_after_seconds' => RateLimiter::availableIn($throttleKey),
            ]);
        }

        return $this->fail($message, $status, [
            'max_attempts' => self::MAX_LOGIN_ATTEMPTS,
            'attempts_remaining' => $attemptsRemaining,
        ]);
    }
}
