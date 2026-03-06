<?php
header('Content-Type: text/plain');

echo "=== DIAGNOSTIC REPORT ===\n\n";

// 1. Check index.php exists
echo "1. Index.php exists: ";
echo file_exists(__DIR__ . '/index.php') ? "✅ YES\n" : "❌ NO\n";

// 2. Check database.php
echo "2. backend/config/database.php exists: ";
echo file_exists(__DIR__ . '/backend/config/database.php') ? "✅ YES\n" : "❌ NO\n";

// 3. database.php last modified
if (file_exists(__DIR__ . '/backend/config/database.php')) {
    echo "3. database.php last modified: ";
    echo date("Y-m-d H:i:s", filemtime(__DIR__ . '/backend/config/database.php')) . "\n";
} else {
    echo "3. database.php last modified: N/A\n";
}

// 4. Testing MySQL connection
echo "\n4. Testing MySQL connection...\n";
try {
    $pdo = new PDO('mysql:host=localhost;port=3306', 'root', '');
    echo "   ✅ MySQL server is reachable\n";

    // Check if database exists
    $databases = $pdo->query("SHOW DATABASES LIKE 'school_facility_maintenance'")->fetchAll();
    if (count($databases) > 0) {
        echo "   ✅ Database 'school_facility_maintenance' exists\n";

        // Check tables
        $pdo->exec("USE school_facility_maintenance");
        $tables = $pdo->query("SHOW TABLES")->fetchAll();
        echo "   ✅ Found " . count($tables) . " tables\n";
    } else {
        echo "   ❌ Database 'school_facility_maintenance' NOT FOUND\n";
        echo "   → Please create it and import database/SINGLE_IMPORT.sql\n";
    }
} catch (PDOException $e) {
    echo "   ❌ MySQL Error: " . $e->getMessage() . "\n";
}

// 5. Apache document root + paths
echo "\n5. Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "6. Current Path: " . __DIR__ . "\n";
echo "7. Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";

// 8. PHP version
echo "\n8. PHP Version: " . PHP_VERSION . "\n";

// 9. Headers sent
echo "9. Headers sent: " . (headers_sent() ? "YES" : "NO") . "\n";

echo "\n=== END DIAGNOSTIC ===\n";
?>