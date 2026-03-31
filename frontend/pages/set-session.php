<?php
/**
 * Set Session Helper
 * Used by login page to set PHP session variables
 */
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $_SESSION['user'] = $data;
    $_SESSION['auth_user'] = $data;
    $_SESSION['role'] = $data['role'] ?? ($_SESSION['role'] ?? null);
    $_SESSION['user_id'] = $data['user_id'] ?? ($_SESSION['user_id'] ?? null);
    $_SESSION['last_activity'] = time();
    if (!isset($_SESSION['session_token'])) {
        $_SESSION['session_token'] = bin2hex(random_bytes(32));
    }
    
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false]);
}
