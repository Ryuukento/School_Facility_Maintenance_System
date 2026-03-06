<?php
/**
 * Users API
 * Handles user-related endpoints
 */

require_once dirname(__DIR__) . '/bootstrap.php';

// Get action from query string
$action = $_GET['action'] ?? null;

// Get request body
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    // Initialize session to check authentication
    SessionMiddleware::initialize();
    AuthMiddleware::protect();
    
    switch ($action) {
        case 'list':
            handleGetUsers();
            break;
        
        case 'delete':
            RoleMiddleware::requireRole([ROLE_SUPER_ADMIN, ROLE_DEPARTMENT_ADMIN]);
            handleDeleteUser($input);
            break;
        
        default:
            Response::error('Invalid action', [], Response::HTTP_BAD_REQUEST);
    }
} catch (Exception $e) {
    Logger::error('Users API error', ['action' => $action, 'error' => $e->getMessage()]);
    Response::error($e->getMessage(), [], Response::HTTP_INTERNAL_ERROR);
}

/**
 * Get all users
 */
function handleGetUsers() {
    global $pdo;
    
    try {
        $query = "SELECT user_id, full_name, email, role, status, created_at 
                  FROM users 
                  ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        Response::success('Users retrieved', ['users' => $users]);
    } catch (Exception $e) {
        Response::error('Failed to retrieve users', [], Response::HTTP_INTERNAL_ERROR);
    }
}

/**
 * Delete a user
 */
function handleDeleteUser($input) {
    global $pdo;
    
    $userId = $input['user_id'] ?? null;
    
    if (!$userId) {
        Response::error('User ID is required', [], Response::HTTP_BAD_REQUEST);
        return;
    }
    
    // Prevent deleting yourself
    if ($userId == $_SESSION['user_id']) {
        Response::error('Cannot delete your own account', [], Response::HTTP_BAD_REQUEST);
        return;
    }
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            Response::error('User not found', [], Response::HTTP_NOT_FOUND);
            return;
        }
        
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $result = $stmt->execute([$userId]);
        
        if ($result) {
            // Insert audit record into activity_logs
            try {
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
                $logStmt->execute([
                    $_SESSION['user_id'],
                    'DELETE_USER',
                    'user',
                    $userId,
                    "Deleted user #$userId",
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                ]);
            } catch (Exception $e) {
                Logger::error('Activity log insert failed', ['error' => $e->getMessage()]);
            }

            Response::success('User deleted successfully');
        } else {
            Response::error('Failed to delete user', [], Response::HTTP_INTERNAL_ERROR);
        }
    } catch (Exception $e) {
        Logger::error('Delete user error', ['user_id' => $userId, 'error' => $e->getMessage()]);
        Response::error('Failed to delete user', [], Response::HTTP_INTERNAL_ERROR);
    }
}
?>
