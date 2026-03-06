<?php
/**
 * Test Reports API
 * Diagnostic tool to verify reports are being returned correctly
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    // Test 1: Check if table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'maintenance_reports'")->fetchAll();
    
    if (empty($tables)) {
        echo json_encode([
            'success' => false,
            'message' => 'maintenance_reports table does not exist',
            'tables_found' => count($tables)
        ]);
        exit;
    }
    
    // Test 2: Count total reports
    $totalResult = $pdo->query("SELECT COUNT(*) as count FROM maintenance_reports")->fetch();
    $totalReports = $totalResult['count'] ?? 0;
    
    // Test 3: Fetch all reports
    $allReportsResult = $pdo->query("
        SELECT r.*, 
               creator.full_name as creator_name,
               assigned.full_name as assigned_name,
               d.name as department_name
        FROM maintenance_reports r
        LEFT JOIN users creator ON r.created_by = creator.user_id
        LEFT JOIN users assigned ON r.assigned_to = assigned.user_id
        LEFT JOIN departments d ON r.department_id = d.department_id
        ORDER BY r.created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Test 4: Check session
    $sessionInfo = [
        'session_status' => session_status() == PHP_SESSION_NONE ? 'not started' : 'started',
        'user_id' => $_SESSION['user_id'] ?? 'not set',
        'role' => $_SESSION['role'] ?? 'not set',
        'email' => $_SESSION['email'] ?? 'not set'
    ];
    
    // Test 5: Test the actual API
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'super_admin';
    
    include __DIR__ . '/reports.php';
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}

// Return results
echo json_encode([
    'success' => true,
    'tests' => [
        'database_connected' => true,
        'table_exists' => true,
        'total_reports' => $totalReports,
        'sample_reports' => $allReportsResult,
        'session_info' => $sessionInfo
    ],
    'message' => "Database is working. Total reports: " . $totalReports
]);
