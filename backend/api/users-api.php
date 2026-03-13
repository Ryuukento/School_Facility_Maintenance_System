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

        case 'update_profile':
            handleUpdateProfile();
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

/**
 * Update own profile details and optional avatar image.
 */
function handleUpdateProfile() {
    global $pdo;

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        Response::error('Unauthorized', [], Response::HTTP_UNAUTHORIZED);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Invalid request method', [], Response::HTTP_BAD_REQUEST);
        return;
    }

    try {
        ensureAvatarColumn($pdo);

        $stmt = $pdo->prepare("SELECT user_id, full_name, email, role, department_id, status, avatar FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentUser) {
            Response::error('User not found', [], Response::HTTP_NOT_FOUND);
            return;
        }

        $fullName = trim((string)($_POST['full_name'] ?? $currentUser['full_name']));
        $email = trim((string)($_POST['email_address'] ?? $currentUser['email']));

        if ($fullName === '') {
            Response::error('Full name is required', [], Response::HTTP_BAD_REQUEST);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Valid email is required', [], Response::HTTP_BAD_REQUEST);
            return;
        }

        $avatarPath = $currentUser['avatar'] ?? null;

        if (isset($_FILES['profile_picture']) && ($_FILES['profile_picture']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['profile_picture'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                Response::error('Failed to upload profile picture', [], Response::HTTP_BAD_REQUEST);
                return;
            }

            if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
                Response::error('Profile picture must be 3MB or less', [], Response::HTTP_BAD_REQUEST);
                return;
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
            ];

            if (!isset($allowed[$mime])) {
                Response::error('Only JPG, PNG, WEBP, or GIF images are allowed', [], Response::HTTP_BAD_REQUEST);
                return;
            }

            $uploadDir = dirname(__DIR__, 2) . '/frontend/assets/uploads/avatars';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                Response::error('Unable to create upload directory', [], Response::HTTP_INTERNAL_ERROR);
                return;
            }

            $extension = $allowed[$mime];
            $fileName = 'avatar_' . $userId . '_' . time() . '.' . $extension;
            $targetPath = $uploadDir . '/' . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                Response::error('Failed to save profile picture', [], Response::HTTP_INTERNAL_ERROR);
                return;
            }

            // Delete previous uploaded avatar when possible.
            if (!empty($avatarPath) && strpos($avatarPath, '/frontend/assets/uploads/avatars/') !== false) {
                $oldPath = dirname(__DIR__, 2) . str_replace('/School_Facility_Maintenance_System', '', $avatarPath);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $avatarPath = '/School_Facility_Maintenance_System/frontend/assets/uploads/avatars/' . $fileName;
        }

        $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, avatar = ?, updated_at = NOW() WHERE user_id = ?");
        $updateStmt->execute([$fullName, $email, $avatarPath, $userId]);

        $refetchStmt = $pdo->prepare("SELECT u.*, d.name as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.department_id WHERE u.user_id = ? LIMIT 1");
        $refetchStmt->execute([$userId]);
        $updatedUser = $refetchStmt->fetch(PDO::FETCH_ASSOC);

        if ($updatedUser) {
            unset($updatedUser['password']);
            $_SESSION['user'] = $updatedUser;
            $_SESSION['full_name'] = $updatedUser['full_name'];
        }

        Response::success('Profile updated successfully', [
            'user' => [
                'user_id' => (int)$userId,
                'full_name' => $fullName,
                'email' => $email,
                'avatar' => $avatarPath,
            ]
        ]);
    } catch (PDOException $e) {
        if ((int)$e->getCode() === 23000) {
            Response::error('Email address is already in use', [], Response::HTTP_BAD_REQUEST);
            return;
        }

        Logger::error('Update profile DB error', ['error' => $e->getMessage()]);
        Response::error('Failed to update profile', [], Response::HTTP_INTERNAL_ERROR);
    } catch (Exception $e) {
        Logger::error('Update profile error', ['error' => $e->getMessage()]);
        Response::error('Failed to update profile', [], Response::HTTP_INTERNAL_ERROR);
    }
}

/**
 * Ensure the users table has an avatar column.
 */
function ensureAvatarColumn(PDO $pdo) {
    $checkStmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'avatar'");
    $checkStmt->execute();
    $exists = (int)($checkStmt->fetchColumn() ?: 0) > 0;

    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(500) NULL AFTER status");
    }
}
?>
