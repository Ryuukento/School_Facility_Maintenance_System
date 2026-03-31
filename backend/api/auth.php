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

header('Content-Type: application/json; charset=utf-8');

$pdo = getDBConnection();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
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

    try {
        $stmt = $pdo->prepare(
            "SELECT u.*, d.name as department_name
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.department_id
             WHERE u.email = ?"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            sendResponse(false, 'Invalid email or password');
        }

        $status = strtolower(trim((string)($user['status'] ?? '')));
        if ($status === STATUS_PENDING) {
            sendResponse(false, 'Your account is pending approval. Please wait for Super Admin approval.');
        }

        if ($status !== STATUS_ACTIVE) {
            sendResponse(false, 'Account is inactive');
        }

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

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Invalid email format');
    }

    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        sendResponse(false, 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters');
    }

    try {
        ensurePendingStatusSupported($pdo);

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

function sendResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message,
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
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
