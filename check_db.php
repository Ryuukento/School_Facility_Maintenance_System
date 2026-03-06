<?php
// Quick check
require_once __DIR__ . '/backend/config/database.php';

try {
    $pdo = getDBConnection();
    $count = $pdo->query("SELECT COUNT(*) FROM maintenance_reports")->fetchColumn();
    echo "Reports in database: " . $count . "\n";
    
    if ($count == 0) {
        echo "\n⚠️  NO DATA IN DATABASE!\n";
        echo "You must run: http://localhost/School_Facility_Maintenance_System/FRESH_SETUP.php\n";
    } else {
        echo "✓ Database has data\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
