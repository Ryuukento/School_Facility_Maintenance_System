<?php
require 'backend/config/database.php';
try {
    $pdo = getDBConnection();
    
    echo "=== DATABASE STATUS ===\n\n";
    
    // Check database
    $stmt = $pdo->query("SELECT DATABASE()");
    $db = $stmt->fetchColumn();
    echo "✓ Database: " . $db . "\n\n";
    
    // Check tables
    $tables = [
        'departments',
        'users',
        'maintenance_reports',
        'activity_logs',
        'notifications',
        'report_comments'
    ];
    
    echo "=== TABLES CHECK ===\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo ($exists ? "✓" : "✗") . " $table\n";
    }
    
    echo "\n=== DATA COUNT ===\n";
    $counts = [
        'departments' => 'SELECT COUNT(*) FROM departments',
        'users' => 'SELECT COUNT(*) FROM users',
        'maintenance_reports' => 'SELECT COUNT(*) FROM maintenance_reports',
        'activity_logs' => 'SELECT COUNT(*) FROM activity_logs',
        'notifications' => 'SELECT COUNT(*) FROM notifications',
        'report_comments' => 'SELECT COUNT(*) FROM report_comments'
    ];
    
    foreach ($counts as $table => $query) {
        $count = $pdo->query($query)->fetchColumn();
        echo "$table: $count records\n";
    }
    
    echo "\n✓ DATABASE IS FULLY MIGRATED AND READY!\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
