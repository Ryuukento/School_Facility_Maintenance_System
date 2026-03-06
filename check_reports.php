<?php
require 'backend/config/database.php';
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM maintenance_reports');
    $result = $stmt->fetch();
    echo "Reports in database: " . $result['count'] . "\n";
    
    // Also list the reports
    $stmt = $pdo->query('SELECT report_id, title, created_by FROM maintenance_reports');
    $reports = $stmt->fetchAll();
    echo "\nReports:\n";
    foreach ($reports as $report) {
        echo "- ID: " . $report['report_id'] . ", Title: " . $report['title'] . ", Created by user_id: " . $report['created_by'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
