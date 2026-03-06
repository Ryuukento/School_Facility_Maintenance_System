<?php
/**
 * Authentication API
 * Handles login, logout, and session management
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

header('Content-Type: application/json');

// Establish database connection
$pdo = getDBConnection();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'login':
        handleLogin();
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

/**
 * Handle user login
 */
function handleLogin() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
        return;
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        sendResponse(false, 'Email and password are required');
        return;
    }
    
    try {
        // Get user from database
        $stmt = $pdo->prepare("
            SELECT u.*, d.name as department_name 
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.department_id
            WHERE u.email = ? AND u.status = 'active'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendResponse(false, 'Invalid email or password');
            return;
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            sendResponse(false, 'Invalid email or password');
            return;
        }
        
        // Remove password from user data
        unset($user['password']);
        
        // Store user in session
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        
        // Log activity
        logActivity($pdo, $user['user_id'], 'LOGIN', 'user', $user['user_id'], 'User logged in');
        
        sendResponse(true, 'Login successful', ['user' => $user]);
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        sendResponse(false, 'An error occurred during login');
    }
}

/**
 * Handle user logout
 */
function handleLogout() {
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        logActivity($pdo, $_SESSION['user_id'], 'LOGOUT', 'user', $_SESSION['user_id'], 'User logged out');
    }
    
    session_destroy();
    sendResponse(true, 'Logout successful');
}

/**
 * Check session status
 */
function checkSession() {
    if (isset($_SESSION['user'])) {
        sendResponse(true, 'Session active', ['user' => $_SESSION['user']]);
    } else {
        sendResponse(false, 'No active session');
    }
}

/**
 * Log user activity
 */
function logActivity($pdo, $userId, $action, $entityType = null, $entityId = null, $details = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}
