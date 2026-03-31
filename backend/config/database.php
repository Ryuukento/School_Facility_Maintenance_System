<?php
/**
 * Database Configuration
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'school_facility_maintenance');
define('DB_PORT', 3306);

/**
 * Create PDO connection.
 */
function getDBConnection() {
    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        return new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $isApiRequest = strpos($requestUri, '/backend/api/') !== false;

        if ($isApiRequest) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
            }

            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed. Please check your configuration.'
            ]);
            exit;
        }

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
        }

        echo 'Database connection failed. Please import/setup database first.';
        exit;
    }
}

$pdo = getDBConnection();

return $pdo;
