<?php
/**
 * Authentication API
 * Handles auth-related endpoints
 */

require_once dirname(__DIR__) . '/bootstrap.php';

// Get action from query string
$action = $_GET['action'] ?? null;

// Get request body
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    $authController = new AuthController($pdo);
    
    switch ($action) {
        case 'login':
            $authController->login($input);
            break;
        
        case 'logout':
            $authController->logout();
            break;
        
        case 'register':
            $authController->register($input);
            break;
        
        default:
            Response::error('Invalid action', [], Response::HTTP_BAD_REQUEST);
    }
} catch (Exception $e) {
    Logger::error('API error', ['action' => $action, 'error' => $e->getMessage()]);
    Response::error('Internal server error', [], Response::HTTP_INTERNAL_ERROR);
}
