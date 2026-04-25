<?php
/**
 * Authentication API
 * Handles login, registration, logout, and session checks.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EmailService.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDBConnection();
$action = $_GET['action'] ?? '';

const LOGIN_MAX_ATTEMPTS = 3;
const LOGIN_LOCKOUT_SECONDS = 300;

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
        break;
    case 'forgot_password_request':
        handleForgotPasswordRequest();
        break;
    case 'forgot_password_reset':
        handleForgotPasswordReset();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        checkSession();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

function handleLogin() {
    global $pdo;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = trim((string)($input['email'] ?? ''));
    $password = (string)($input['password'] ?? '');

    if ($email === '' || $password === '') {
        sendResponse(false, 'Email and password are required');
    }

    $throttleKey = getLoginThrottleKey($email, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    $activeLockout = getActiveLoginLockout($throttleKey);
    if ($activeLockout > 0) {
        sendResponse(false, 'Too many login attempts. Please try again later.', [
            'max_attempts' => LOGIN_MAX_ATTEMPTS,
            'retry_after_seconds' => $activeLockout,
        ], 429);
    }

    try {
        ensureUserOnboardingColumns($pdo);

        $stmt = $pdo->prepare(
            "SELECT u.*, d.name as department_name
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.department_id
             WHERE u.email = ?"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            sendFailedLoginResponse($throttleKey, 'Invalid email or password', 401);
        }

        $status = strtolower(trim((string)($user['status'] ?? '')));
        if ($status === STATUS_PENDING) {
            sendFailedLoginResponse($throttleKey, 'Your account is pending approval. Please wait for Super Admin approval.', 403);
        }

        if ($status !== STATUS_ACTIVE) {
            sendFailedLoginResponse($throttleKey, 'Account is inactive', 403);
        }

        clearLoginAttempts($throttleKey);

        $user['role'] = normalizeRoleAlias($user['role'] ?? '');
        unset($user['password']);

        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];

        logActivity($pdo, $user['user_id'], 'LOGIN', 'user', $user['user_id'], 'User logged in');

        sendResponse(true, 'Login successful', ['user' => $user]);
    } catch (Throwable $e) {
        error_log('Login error: ' . $e->getMessage());
        sendResponse(false, 'An error occurred during login');
    }
}

function handleRegister() {
    global $pdo;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $fullName = trim((string)($input['full_name'] ?? ''));
    $email = trim((string)($input['email'] ?? ''));
    $password = (string)($input['password'] ?? '');

    if ($fullName === '' || $email === '' || $password === '') {
        sendResponse(false, 'Full name, email, and password are required');
    }

    if (!isValidFullName($fullName)) {
        sendResponse(false, 'Name must contain only letters, spaces, hyphens, and apostrophes (no numbers)');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Invalid email format');
    }

    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        sendResponse(false, 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters');
    }

    try {
        ensurePendingStatusSupported($pdo);
        ensureUserOnboardingColumns($pdo);

        $existingStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $existingStmt->execute([$email]);

        if ($existingStmt->fetch()) {
            sendResponse(false, 'Email is already registered');
        }

        $departmentId = null;
        $departmentStmt = $pdo->query("SELECT department_id FROM departments WHERE status = 'active' ORDER BY department_id ASC LIMIT 1");
        if ($departmentStmt) {
            $departmentRow = $departmentStmt->fetch(PDO::FETCH_ASSOC);
            if ($departmentRow && isset($departmentRow['department_id'])) {
                $departmentId = (int)$departmentRow['department_id'];
            }
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

        // Role assignment is deferred to Super Admin approval.
        $insertStmt = $pdo->prepare(
            "INSERT INTO users (full_name, email, password, role, department_id, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $insertStmt->execute([$fullName, $email, $passwordHash, ROLE_USER, $departmentId, STATUS_PENDING]);

        $newUserId = (int)$pdo->lastInsertId();
        logActivity($pdo, $newUserId, 'REGISTER', 'user', $newUserId, 'Self-registered account (pending approval)');

        sendResponse(true, 'Registration submitted. Your account is pending Super Admin approval.', [
            'user_id' => $newUserId,
            'email' => $email,
            'status' => STATUS_PENDING,
        ]);
    } catch (Throwable $e) {
        error_log('Register error: ' . $e->getMessage());
        sendResponse(false, 'Failed to create account');
    }
}

function handleForgotPasswordRequest() {
    global $pdo;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method', null, 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = strtolower(trim((string)($input['email'] ?? '')));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Please enter a valid email address', null, 422);
    }

    try {
        ensurePasswordResetTokensTable($pdo);

        $stmt = $pdo->prepare('SELECT user_id, full_name, email, status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return a generic success response to avoid account enumeration.
        if (!$user) {
            sendResponse(true, 'If this email is registered, a reset code has been sent.');
        }

        $status = strtolower(trim((string)($user['status'] ?? '')));
        if ($status !== STATUS_ACTIVE && $status !== STATUS_PENDING) {
            sendResponse(true, 'If this email is registered, a reset code has been sent.');
        }

        $resetCode = (string) random_int(100000, 999999);
        $tokenHash = hashResetCode((string)$user['email'], $resetCode);

        $upsert = $pdo->prepare(
            'INSERT INTO password_reset_tokens (email, token, created_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE token = VALUES(token), created_at = VALUES(created_at)'
        );
        $upsert->execute([(string)$user['email'], $tokenHash]);

        if (class_exists('EmailService') && method_exists('EmailService', 'sendPasswordResetCode')) {
            EmailService::sendPasswordResetCode((string)$user['email'], (string)($user['full_name'] ?? 'User'), $resetCode);
        }

        $responseData = null;
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $responseData = ['reset_code' => $resetCode];
        }

        sendResponse(true, 'If this email is registered, a reset code has been sent.', $responseData);
    } catch (Throwable $e) {
        error_log('Forgot password request error: ' . $e->getMessage());
        sendResponse(false, 'Failed to process forgot password request', null, 500);
    }
}

function handleForgotPasswordReset() {
    global $pdo;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method', null, 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $resetCode = trim((string)($input['reset_code'] ?? ''));
    $newPassword = (string)($input['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Please enter a valid email address', null, 422);
    }

    if (!preg_match('/^\d{6}$/', $resetCode)) {
        sendResponse(false, 'Reset code must be a 6-digit number', null, 422);
    }

    if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        sendResponse(false, 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters', null, 422);
    }

    try {
        ensurePasswordResetTokensTable($pdo);

        $tokenStmt = $pdo->prepare('SELECT email, token, created_at FROM password_reset_tokens WHERE email = ? LIMIT 1');
        $tokenStmt->execute([$email]);
        $tokenRow = $tokenStmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenRow) {
            sendResponse(false, 'Invalid or expired reset code', null, 422);
        }

        $createdAt = strtotime((string)($tokenRow['created_at'] ?? ''));
        if (!$createdAt || (time() - $createdAt) > 900) {
            $deleteStmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE email = ?');
            $deleteStmt->execute([$email]);
            sendResponse(false, 'Reset code has expired. Please request a new one.', null, 422);
        }

        $expectedHash = (string)($tokenRow['token'] ?? '');
        $providedHash = hashResetCode($email, $resetCode);
        if (!hash_equals($expectedHash, $providedHash)) {
            sendResponse(false, 'Invalid or expired reset code', null, 422);
        }

        $userStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $userStmt->execute([$email]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            sendResponse(false, 'User account not found', null, 404);
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $updateStmt = $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?');
        $updateStmt->execute([$passwordHash, $email]);

        $deleteStmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE email = ?');
        $deleteStmt->execute([$email]);

        logActivity($pdo, (int)$user['user_id'], 'PASSWORD_RESET', 'user', (int)$user['user_id'], 'Password reset via forgot password flow');

        sendResponse(true, 'Password reset successful. You can now sign in with your new password.');
    } catch (Throwable $e) {
        error_log('Forgot password reset error: ' . $e->getMessage());
        sendResponse(false, 'Failed to reset password', null, 500);
    }
}

function handleLogout() {
    global $pdo;

    if (isset($_SESSION['user_id'])) {
        logActivity($pdo, $_SESSION['user_id'], 'LOGOUT', 'user', $_SESSION['user_id'], 'User logged out');
    }

    $_SESSION = [];
    session_destroy();

    sendResponse(true, 'Logout successful');
}

function checkSession() {
    if (isset($_SESSION['user'])) {
        sendResponse(true, 'Session active', ['user' => $_SESSION['user']]);
    }

    sendResponse(false, 'No active session');
}

function normalizeRoleAlias($role) {
    $role = strtolower(trim((string)$role));

    if ($role === 'admin_maintenance') {
        return 'maintenance_admin';
    }

    if ($role === 'eelab_staff' || $role === 'maintenance_personnel' || $role === '') {
        return 'maintenance_staff';
    }

    return $role;
}

function isValidFullName($fullName) {
    // Allow letters (including accents), spaces, apostrophes, periods, and hyphens.
    if (!preg_match("/^[\p{L} .'-]+$/u", $fullName)) {
        return false;
    }

    // Must contain at least one letter and cannot be digits-only.
    if (!preg_match('/\p{L}/u', $fullName)) {
        return false;
    }

    if (preg_match('/^\d+$/', $fullName)) {
        return false;
    }

    return true;
}

function ensurePendingStatusSupported(PDO $pdo) {
    try {
        $columnStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
        $column = $columnStmt ? $columnStmt->fetch(PDO::FETCH_ASSOC) : null;
        $type = strtolower((string)($column['Type'] ?? ''));

        if (strpos($type, "'pending'") !== false) {
            return;
        }

        $pdo->exec("ALTER TABLE users MODIFY COLUMN status ENUM('active','inactive','suspended','pending') DEFAULT 'active'");
    } catch (Throwable $e) {
        error_log('Pending status schema check warning: ' . $e->getMessage());
    }
}

function ensureUserOnboardingColumns(PDO $pdo) {
    $createdByStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'created_by'");
    $createdByStmt->execute();
    $hasCreatedBy = (int)($createdByStmt->fetchColumn() ?: 0) > 0;

    if (!$hasCreatedBy) {
        $pdo->exec("ALTER TABLE users ADD COLUMN created_by INT UNSIGNED NULL");
    }

    $forceStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'force_profile_update'");
    $forceStmt->execute();
    $hasForceProfileUpdate = (int)($forceStmt->fetchColumn() ?: 0) > 0;

    if (!$hasForceProfileUpdate) {
        $pdo->exec("ALTER TABLE users ADD COLUMN force_profile_update TINYINT(1) NOT NULL DEFAULT 0");
    }
}

function ensurePasswordResetTokensTable(PDO $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'password_reset_tokens'");
        $stmt->execute();
        $exists = (int)($stmt->fetchColumn() ?: 0) > 0;

        if ($exists) {
            return;
        }

        $pdo->exec("CREATE TABLE password_reset_tokens (
            email VARCHAR(255) NOT NULL PRIMARY KEY,
            token VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NULL DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Throwable $e) {
        error_log('Password reset token table check warning: ' . $e->getMessage());
    }
}

function hashResetCode($email, $resetCode) {
    return hash('sha256', strtolower(trim((string)$email)) . '|' . trim((string)$resetCode) . '|sfms-reset-code');
}

function logActivity($pdo, $userId, $action, $entityType = null, $entityId = null, $details = null) {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    } catch (Throwable $e) {
        error_log('Failed to log activity: ' . $e->getMessage());
    }
}

function getLoginThrottleKey($email, $ipAddress) {
    return strtolower(trim((string)$email)) . '|' . trim((string)$ipAddress);
}

function getLoginAttemptsStorePath() {
    $storageDir = dirname(__DIR__, 3) . '/storage/framework/cache';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0775, true);
    }

    return $storageDir . '/legacy_login_attempts.json';
}

function loadLoginAttemptsStore() {
    $storePath = getLoginAttemptsStorePath();
    if (!is_file($storePath)) {
        return [];
    }

    $content = @file_get_contents($storePath);
    if ($content === false || trim($content) === '') {
        return [];
    }

    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : [];
}

function saveLoginAttemptsStore(array $store) {
    $storePath = getLoginAttemptsStorePath();
    @file_put_contents($storePath, json_encode($store, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE), LOCK_EX);
}

function cleanupLoginAttemptsStore(array $store) {
    $now = time();
    foreach ($store as $key => $entry) {
        $lockoutUntil = (int)($entry['lockout_until'] ?? 0);
        $lastFailedAt = (int)($entry['last_failed_at'] ?? 0);

        if ($lockoutUntil > $now) {
            continue;
        }

        if ($lastFailedAt > 0 && ($now - $lastFailedAt) <= 86400) {
            continue;
        }

        unset($store[$key]);
    }

    return $store;
}

function getActiveLoginLockout($throttleKey) {
    $store = cleanupLoginAttemptsStore(loadLoginAttemptsStore());
    saveLoginAttemptsStore($store);

    $entry = $store[$throttleKey] ?? null;
    if (!is_array($entry)) {
        return 0;
    }

    $lockoutUntil = (int)($entry['lockout_until'] ?? 0);
    $remaining = $lockoutUntil - time();
    return max(0, $remaining);
}

function registerFailedLoginAttempt($throttleKey) {
    $store = cleanupLoginAttemptsStore(loadLoginAttemptsStore());
    $entry = $store[$throttleKey] ?? [
        'attempts' => 0,
        'lockout_until' => 0,
        'last_failed_at' => 0,
    ];

    $now = time();
    $lockoutUntil = (int)($entry['lockout_until'] ?? 0);

    if ($lockoutUntil > $now) {
        $retryAfter = $lockoutUntil - $now;
        return [
            'attempts_remaining' => 0,
            'retry_after_seconds' => $retryAfter,
            'locked' => true,
        ];
    }

    $attempts = max(0, (int)($entry['attempts'] ?? 0)) + 1;
    $entry['attempts'] = $attempts;
    $entry['last_failed_at'] = $now;

    $locked = false;
    $retryAfterSeconds = 0;

    if ($attempts >= LOGIN_MAX_ATTEMPTS) {
        $entry['attempts'] = LOGIN_MAX_ATTEMPTS;
        $entry['lockout_until'] = $now + LOGIN_LOCKOUT_SECONDS;
        $locked = true;
        $retryAfterSeconds = LOGIN_LOCKOUT_SECONDS;
    } else {
        $entry['lockout_until'] = 0;
    }

    $store[$throttleKey] = $entry;
    saveLoginAttemptsStore($store);

    return [
        'attempts_remaining' => max(0, LOGIN_MAX_ATTEMPTS - (int)$entry['attempts']),
        'retry_after_seconds' => $retryAfterSeconds,
        'locked' => $locked,
    ];
}

function clearLoginAttempts($throttleKey) {
    $store = loadLoginAttemptsStore();
    if (isset($store[$throttleKey])) {
        unset($store[$throttleKey]);
        saveLoginAttemptsStore($store);
    }
}

function sendFailedLoginResponse($throttleKey, $message, $statusCode = 401) {
    $lockState = registerFailedLoginAttempt($throttleKey);

    if (!empty($lockState['locked'])) {
        sendResponse(false, 'Too many login attempts. Please try again later.', [
            'max_attempts' => LOGIN_MAX_ATTEMPTS,
            'retry_after_seconds' => (int)($lockState['retry_after_seconds'] ?? LOGIN_LOCKOUT_SECONDS),
        ], 429);
    }

    sendResponse(false, $message, [
        'max_attempts' => LOGIN_MAX_ATTEMPTS,
        'attempts_remaining' => (int)($lockState['attempts_remaining'] ?? 0),
    ], $statusCode);
}

function sendResponse($success, $message, $data = null, $statusCode = null) {
    $response = [
        'success' => $success,
        'message' => $message,
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        if (is_int($statusCode)) {
            http_response_code($statusCode);
        }
    }

    $json = json_encode(
        $response,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    if ($json === false) {
        http_response_code(500);
        echo '{"success":false,"message":"Response encoding failed"}';
        exit;
    }

    echo $json;
    exit;
}
