<?php
/**
 * Main API Router
 * Handles all API requests
 */

require_once dirname(__DIR__) . '/bootstrap.php';

// Parse the request URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Route the request
if (preg_match('/\/api\/auth\/?$/', $requestUri)) {
    require_once ROOT_DIR . '/backend/api/auth-api.php';
} elseif (preg_match('/\/api\/reports\/?$/', $requestUri)) {
    require_once ROOT_DIR . '/backend/api/reports-api.php';
} else {
    Response::error('Endpoint not found', [], Response::HTTP_NOT_FOUND);
}
