<?php
/**
 * Database Import Tool
 * Imports the complete database schema from SINGLE_IMPORT.sql
 */

require_once __DIR__ . '/backend/config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Import</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .message { padding: 15px; margin: 15px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🗄️ School Facility Maintenance System - Database Setup</h1>
";

try {
    // Try to connect to MySQL (without selecting database first)
    $conn = new mysqli('localhost', 'root', '', '');
    
    if ($conn->connect_error) {
        echo "<div class='message error'>
            <strong>Connection Error:</strong> " . htmlspecialchars($conn->connect_error) . "<br>
            Make sure MySQL is running (XAMPP Control Panel > Start MySQL)
        </div>";
        exit;
    }
    
    echo "<div class='message info'><strong>✓ MySQL Connection Established</strong></div>";
    
    // Check if database already exists
    $result = $conn->query("SHOW DATABASES LIKE 'school_facility_maintenance'");
    if ($result && $result->num_rows > 0) {
        echo "<div class='message warning'>
            <strong>⚠️ Database already exists!</strong><br>
            The system will only add missing tables and data. Existing reports and user data will NOT be deleted.
        </div>";
    } else {
        echo "<div class='message info'>Creating new database...</div>";
    }
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/database/backups/SINGLE_IMPORT.sql';
    
    if (!file_exists($sqlFile)) {
        echo "<div class='message error'>
            <strong>Error:</strong> SQL import file not found at <code>" . htmlspecialchars($sqlFile) . "</code>
        </div>";
        exit;
    }
    
    echo "<div class='message info'><strong>✓ SQL Import File Found</strong></div>";
    
    $sql = file_get_contents($sqlFile);
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', preg_split('/;[\s]*[\n\r]+/', $sql)));
    
    echo "<div class='message info'>Found " . count($statements) . " SQL statements to execute</div>";
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    // Execute each statement
    foreach ($statements as $index => $statement) {
        if (empty($statement)) continue;
        
        // Skip comment-only lines
        if (substr(trim($statement), 0, 2) === '--') continue;
        
        try {
            if ($conn->multi_query($statement)) {
                // Clear results
                while ($conn->more_results()) {
                    $conn->next_result();
                }
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Statement " . ($index + 1) . ": " . $conn->error;
            }
        } catch (Exception $e) {
            $errorCount++;
            $errors[] = "Statement " . ($index + 1) . ": " . $e->getMessage();
        }
    }
    
    echo "<div class='message success'>
        <strong>✓ Database Setup Complete!</strong><br>
        Successfully executed: $successCount statements
    </div>";
    
    if ($errorCount > 0) {
        echo "<div class='message warning'>
            <strong>⚠ " . $errorCount . " statements encountered warnings/errors:</strong><br>
            <pre>";
        foreach ($errors as $error) {
            echo htmlspecialchars($error) . "\n";
        }
        echo "</pre>
        </div>";
    }
    
    // Verify tables were created
    $result = $conn->query("SHOW TABLES FROM school_facility_maintenance");
    
    if ($result && $result->num_rows > 0) {
        echo "<div class='message success'>
            <strong>✓ Database Tables Created:</strong><br>";
        while ($row = $result->fetch_row()) {
            echo "  • " . htmlspecialchars($row[0]) . "<br>";
        }
        echo "</div>";
    }
    
    echo "<div class='message success'>
        <h3>✅ Database Setup Complete!</h3>
        <p>Your database is now ready. You can now:</p>
        <ul>
            <li>Log in to the system</li>
            <li>View the dashboard</li>
            <li>Create and view maintenance reports</li>
        </ul>
        <p><strong>Default Admin Account:</strong></p>
        <ul>
            <li>Email: admin@example.com</li>
            <li>Password: password123</li>
        </ul>
    </div>";
    
    echo "<br><button onclick=\"window.location.href='/School_Facility_Maintenance_System/frontend/pages/index.php'\">
        Go to Login Page →
    </button>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='message error'>
        <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
    </div>";
}

echo "
    </div>
</body>
</html>";
?>
