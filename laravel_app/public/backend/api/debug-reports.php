<?php
/**
 * Debug Reports API
 * Shows session info and data for troubleshooting
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Get session status
$sessionInfo = [
    'session_id' => session_id(),
    'session_status' => session_status() === PHP_SESSION_NONE ? 'NOT_STARTED' : (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'DISABLED'),
    'user_id' => $_SESSION['user_id'] ?? null,
    'role' => $_SESSION['role'] ?? null,
    'user_data' => $_SESSION['user'] ?? null,
    'all_session_vars' => array_keys($_SESSION)
];

try {
    $pdo = getDBConnection();
    
    // Test database connection
    $testQuery = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $testQuery->fetch()['count'];
    
    $testQuery = $pdo->query("SELECT COUNT(*) as count FROM maintenance_reports");
    $reportCount = $testQuery->fetch()['count'];
    
    $testQuery = $pdo->query("SELECT COUNT(*) as count FROM departments");
    $deptCount = $testQuery->fetch()['count'];
    
    // Get sample reports
    $query = "
        SELECT 
            r.report_id,
            r.title,
            r.status,
            r.priority,
            r.created_by,
            r.created_at,
            u.full_name
        FROM maintenance_reports r
        LEFT JOIN users u ON r.created_by = u.user_id
        LIMIT 3
    ";
    
    $stmt = $pdo->query($query);
    $sampleReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'message' => 'Debug info retrieved',
        'data' => [
            'session' => $sessionInfo,
            'database' => [
                'users_count' => $userCount,
                'reports_count' => $reportCount,
                'departments_count' => $deptCount,
                'sample_reports' => $sampleReports
            ],
            'connection_status' => 'Connected',
            'endpoint_called' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => phpversion()
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'session' => $sessionInfo
    ], JSON_PRETTY_PRINT);
}
?>
