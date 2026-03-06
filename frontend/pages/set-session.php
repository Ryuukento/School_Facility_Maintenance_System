<?php
/**
 * Set Session Helper
 * Used by login page to set PHP session variables
 */
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $_SESSION['user'] = $data;
    $_SESSION['role'] = $data['role'];
    
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false]);
}
