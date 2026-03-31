<?php
/**
 * Users API
 * Handles user-related endpoints
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$action = $_GET['action'] ?? null;
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
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
        case 'deactivate':
            RoleMiddleware::requireRole([ROLE_SUPER_ADMIN]);
            handleDeactivateUser($input);
            break;

        case 'activate':
            RoleMiddleware::requireRole([ROLE_SUPER_ADMIN]);
            handleActivateUser($input);
            break;

        case 'approve':
            RoleMiddleware::requireRole([ROLE_SUPER_ADMIN]);
            handleApproveUser($input);
            break;

        case 'reject':
            RoleMiddleware::requireRole([ROLE_SUPER_ADMIN]);
            handleRejectUser($input);
            break;

        default:
            Response::error('Invalid action', [], Response::HTTP_BAD_REQUEST);
    }
} catch (Exception $e) {
    Logger::error('Users API error', ['action' => $action, 'error' => $e->getMessage()]);
    Response::error($e->getMessage(), [], Response::HTTP_INTERNAL_ERROR);
}

function handleGetUsers() {
    global $pdo;

    try {
        $query = "SELECT user_id, full_name, email, role, status, created_at FROM users WHERE role <> ? ORDER BY created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([ROLE_SUPER_ADMIN]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success('Users retrieved', ['users' => $users]);
    } catch (Exception $e) {
        Response::error('Failed to retrieve users', [], Response::HTTP_INTERNAL_ERROR);
    }
}

function handleDeactivateUser($input) {
    global $pdo;

    $userId = $input['user_id'] ?? null;

    if (!$userId) {
        Response::error('User ID is required', [], Response::HTTP_BAD_REQUEST);
        return;
    }

    if ($userId == ($_SESSION['user_id'] ?? null)) {
        Response::error('Cannot set your own account to inactive', [], Response::HTTP_BAD_REQUEST);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id, role, status FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$targetUser) {
            Response::error('User not found', [], Response::HTTP_NOT_FOUND);
            return;
        }

        if (strtolower(trim((string)($targetUser['role'] ?? ''))) === ROLE_SUPER_ADMIN) {
            Response::error('Super Admin accounts cannot be changed here', [], Response::HTTP_BAD_REQUEST);
            return;
        }

        if (strtolower(trim((string)($targetUser['status'] ?? ''))) === STATUS_INACTIVE) {
            Response::success('User is already inactive');
            return;
        }

        $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?");
        $result = $stmt->execute([STATUS_INACTIVE, $userId]);

        if ($result) {
            try {
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
                $logStmt->execute([
                    $_SESSION['user_id'],
                    'INACTIVATE_USER',
                    'user',
                    $userId,
                    "Set user #$userId to inactive",
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                ]);
            } catch (Exception $e) {
                Logger::error('Activity log insert failed', ['error' => $e->getMessage()]);
            }

            Response::success('User set to inactive successfully');
        } else {
            Response::error('Failed to set user inactive', [], Response::HTTP_INTERNAL_ERROR);
        }
    } catch (Exception $e) {
        Logger::error('Deactivate user error', ['user_id' => $userId, 'error' => $e->getMessage()]);
        Response::error('Failed to set user inactive', [], Response::HTTP_INTERNAL_ERROR);
    }
}

function handleApproveUser($input) {
    global $pdo;

    $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
    $role = strtolower(trim((string)($input['role'] ?? '')));

    if ($userId <= 0 || $role === '') {
        Response::error('User ID and role are required', [], Response::HTTP_BAD_REQUEST);
        return;
    }

    $allowedRoles = [ROLE_MAINTENANCE_ADMIN, ROLE_MAINTENANCE_STAFF, ROLE_USER];
    if (!in_array($role, $allowedRoles, true)) {
        Response::error('Invalid role selected for approval', [], Response::HTTP_BAD_REQUEST);
        return;
    }

    try {
        ensurePendingStatusSupported($pdo);

        $stmt = $pdo->prepare("SELECT user_id, status FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            Response::error('User not found', [], Response::HTTP_NOT_FOUND);
            return;
        }

        if (($targetUser['status'] ?? '') === STATUS_ACTIVE) {
            Response::error('User is already approved', [], Response::HTTP_BAD_REQUEST);
            return;
        }

        $updateStmt = $pdo->prepare("UPDATE users SET role = ?, status = ?, updated_at = NOW() WHERE user_id = ?");
        $updated = $updateStmt->execute([$role, STATUS_ACTIVE, $userId]);

        if (!$updated) {
            Response::error('Failed to approve user', [], Response::HTTP_INTERNAL_ERROR);
            return;
        }

        try {
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $logStmt->execute([
                $_SESSION['user_id'],
                'APPROVE_USER',
                'user',
                $userId,
                "Approved user #{$userId} and assigned role {$role}",
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (Exception $e) {
            Logger::error('Activity log insert failed', ['error' => $e->getMessage()]);
        }

        Response::success('User approved successfully', [
            'user_id' => $userId,
            'role' => $role,
            'status' => STATUS_ACTIVE
        ]);
    } catch (Exception $e) {
        Logger::error('Approve user error', ['user_id' => $userId, 'error' => $e->getMessage()]);
        Response::error('Failed to approve user', [], Response::HTTP_INTERNAL_ERROR);
    }
}

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

        $stmt = $pdo->prepare("SELECT user_id, full_name, email, password, role, department_id, status, avatar FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentUser) {
            Response::error('User not found', [], Response::HTTP_NOT_FOUND);
            return;
        }

        $fullName = trim((string)($_POST['full_name'] ?? $currentUser['full_name']));
        $email = trim((string)($_POST['email_address'] ?? $currentUser['email']));
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = trim((string)($_POST['new_password'] ?? ''));

        if ($fullName === '') {
            Response::error('Full name is required', [], Response::HTTP_BAD_REQUEST);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Valid email is required', [], Response::HTTP_BAD_REQUEST);
            return;
        }

        $passwordHash = null;
        if ($newPassword !== '') {
            if ($currentPassword === '') {
                Response::error('Current password is required to change your password', [], Response::HTTP_BAD_REQUEST);
                return;
            }

            if (!password_verify($currentPassword, $currentUser['password'] ?? '')) {
                Response::error('Current password is incorrect', [], Response::HTTP_BAD_REQUEST);
                return;
            }

            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                Response::error('New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters', [], Response::HTTP_BAD_REQUEST);
                return;
            }

            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
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

            if (!empty($avatarPath) && strpos($avatarPath, '/frontend/assets/uploads/avatars/') !== false) {
                $oldPath = dirname(__DIR__, 2) . str_replace('/School_Facility_Maintenance_System', '', $avatarPath);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $avatarPath = '/School_Facility_Maintenance_System/frontend/assets/uploads/avatars/' . $fileName;
        }

        $updateFields = "full_name = ?, email = ?, avatar = ?, updated_at = NOW()";
        $updateParams = [$fullName, $email, $avatarPath];
        if ($passwordHash) {
            $updateFields .= ", password = ?";
            $updateParams[] = $passwordHash;
        }
        $updateParams[] = $userId;

        $updateStmt = $pdo->prepare("UPDATE users SET {$updateFields} WHERE user_id = ?");
        $updateStmt->execute($updateParams);

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
        Logger::error('Pending status schema check warning', ['error' => $e->getMessage()]);
    }
}

function ensureAvatarColumn(PDO $pdo) {
    $checkStmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'avatar'");
    $checkStmt->execute();
    $exists = (int)($checkStmt->fetchColumn() ?: 0) > 0;

    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(500) NULL AFTER status");
    }
}

function handleActivateUser($input) {
    global $pdo;

    $userId = $input['user_id'] ?? null;

    if (!$userId) {
        Response::error('User ID is required', [], Response::HTTP_BAD_REQUEST);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id, role, status FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$targetUser) {
            Response::error('User not found', [], Response::HTTP_NOT_FOUND);
            return;
        }

        if (strtolower(trim((string)($targetUser['role'] ?? ''))) === ROLE_SUPER_ADMIN) {
            Response::error('Super Admin accounts cannot be changed here', [], Response::HTTP_BAD_REQUEST);
            return;
        }

        if (strtolower(trim((string)($targetUser['status'] ?? ''))) === STATUS_ACTIVE) {
            Response::success('User is already active');
            return;
        }

        $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?");
        $result = $stmt->execute([STATUS_ACTIVE, $userId]);

        if ($result) {
            try {
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
                $logStmt->execute([
                    $_SESSION['user_id'],
                    'ACTIVATE_USER',
                    'user',
                    $userId,
                    "Set user #$userId to active",
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                ]);
            } catch (Exception $e) {
                Logger::error('Activity log insert failed', ['error' => $e->getMessage()]);
            }

            Response::success('User set to active successfully');
        } else {
            Response::error('Failed to set user active', [], Response::HTTP_INTERNAL_ERROR);
        }
    } catch (Exception $e) {
        Logger::error('Activate user error', ['user_id' => $userId, 'error' => $e->getMessage()]);
        Response::error('Failed to set user active', [], Response::HTTP_INTERNAL_ERROR);
    }
}

function handleRejectUser($input) {
    global $pdo;

    $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

    if ($userId <= 0) {
        Response::error('User ID is required', [], Response::HTTP_BAD_REQUEST);
        return;
    }

    if ($userId === (int)($_SESSION['user_id'] ?? 0)) {
        Response::error('Cannot reject your own account', [], Response::HTTP_BAD_REQUEST);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id, role, status FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            Response::error('User not found', [], Response::HTTP_NOT_FOUND);
            return;
        }

        if (strtolower(trim((string)($targetUser['role'] ?? ''))) === ROLE_SUPER_ADMIN) {
            Response::error('Super Admin accounts cannot be rejected here', [], Response::HTTP_BAD_REQUEST);
            return;
        }

        if (strtolower(trim((string)($targetUser['status'] ?? ''))) !== STATUS_PENDING) {
            Response::error('Only pending users can be rejected', [], Response::HTTP_BAD_REQUEST);
            return;
        }

        $pdo->beginTransaction();

        $deleteLogsStmt = $pdo->prepare("DELETE FROM activity_logs WHERE user_id = ?");
        $deleteLogsStmt->execute([$userId]);

        $deleteUserStmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $deleted = $deleteUserStmt->execute([$userId]);

        if (!$deleted || $deleteUserStmt->rowCount() < 1) {
            $pdo->rollBack();
            Response::error('Failed to reject user', [], Response::HTTP_INTERNAL_ERROR);
            return;
        }

        try {
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $logStmt->execute([
                $_SESSION['user_id'],
                'REJECT_USER',
                'user',
                $userId,
                "Rejected pending user #{$userId} and deleted account",
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (Exception $e) {
            Logger::error('Activity log insert failed', ['error' => $e->getMessage()]);
        }

        $pdo->commit();
        Response::success('Pending user rejected and account deleted successfully');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        Logger::error('Reject user error', ['user_id' => $userId, 'error' => $e->getMessage()]);
        Response::error('Failed to reject user', [], Response::HTTP_INTERNAL_ERROR);
    }
}