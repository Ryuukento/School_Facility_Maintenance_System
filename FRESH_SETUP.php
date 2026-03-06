<?php
/**
 * Fresh Database Setup - Drops old and creates new
 */

// Prevent timeout on large imports
set_time_limit(300);

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Reset & Import Database</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; max-width: 700px; width: 100%; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .body { padding: 40px; }
        .message { padding: 15px 20px; margin: 15px 0; border-radius: 6px; border-left: 4px solid #2196F3; }
        .success { background: #d4edda; color: #155724; border-left-color: #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left-color: #dc3545; }
        .warning { background: #fff3cd; color: #856404; border-left-color: #ffc107; }
        .info { background: #d1ecf1; color: #0c5460; border-left-color: #17a2b8; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; color: #c7254e; }
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0; }
        .stat-box { background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #667eea; }
        .stat-number { font-size: 24px; font-weight: bold; color: #667eea; }
        .stat-label { color: #666; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; color: #333; font-weight: 600; }
        button { padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; margin: 10px 0; width: 100%; }
        button:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .footer-text { text-align: center; margin-top: 30px; color: #999; font-size: 14px; }
        .login-creds { background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .cred-item { padding: 8px 0; font-family: monospace; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🗄️ Database Reset & Import</h1>
            <p>School Facility Maintenance System</p>
        </div>
        <div class='body'>
";

try {
    // Connect to MySQL
    $conn = new mysqli('localhost', 'root', '', '');
    
    if ($conn->connect_error) {
        echo "<div class='message error'>
            ❌ <strong>MySQL Connection Failed</strong><br>
            Error: " . htmlspecialchars($conn->connect_error) . "<br>
            <strong>Solution:</strong> Make sure MySQL is running in XAMPP Control Panel
        </div>";
        echo "</div></div></body></html>";
        exit;
    }
    
    echo "<div class='message info'>✅ Connected to MySQL server</div>";
    
    // Step 1: Check if database already exists
    $dbExistsResult = $conn->query("SHOW DATABASES LIKE 'school_facility_maintenance'");
    if ($dbExistsResult && $dbExistsResult->num_rows > 0) {
        echo "<div class='message warning'>⚠️ Database already exists. Using IF NOT EXISTS — existing data will be preserved.</div>";
    } else {
        echo "<div class='message info'>📦 Creating fresh database...</div>";
    }
    
    // Step 2: Read SQL file
    $sqlFile = __DIR__ . '/database/backups/SINGLE_IMPORT.sql';
    
    if (!file_exists($sqlFile)) {
        echo "<div class='message error'>
            ❌ SQL file not found: <code>" . htmlspecialchars($sqlFile) . "</code>
        </div>";
        exit;
    }
    
    echo "<div class='message info'>✅ Found SQL import file</div>";
    
    // Step 3: Read and split SQL
    $sql = file_get_contents($sqlFile);
    $statements = array_filter(array_map('trim', preg_split('/;[\s]*[\n\r]+/', $sql)));
    
    echo "<div class='message info'>Found " . count($statements) . " SQL statements to execute</div>";
    
    // Step 4: Execute statements
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($statements as $index => $statement) {
        if (empty($statement) || substr(trim($statement), 0, 2) === '--') {
            continue;
        }
        
        try {
            if ($conn->multi_query($statement)) {
                while ($conn->more_results()) {
                    $conn->next_result();
                }
                $successCount++;
            } else {
                $errorCount++;
                if (strlen($conn->error) > 0) {
                    $errors[] = "Statement " . ($index + 1) . ": " . $conn->error;
                }
            }
        } catch (Exception $e) {
            $errorCount++;
            $errors[] = "Statement " . ($index + 1) . ": " . $e->getMessage();
        }
    }
    
    echo "<div class='message success'>
        ✅ <strong>Database Import Complete!</strong><br>
        Executed: <strong>" . $successCount . "</strong> statements
    </div>";
    
    if ($errorCount > 0) {
        echo "<div class='message warning'>
            ⚠️ <strong>" . $errorCount . " statements had warnings/errors</strong>
        </div>";
    }
    
    // Step 5: Verify setup
    echo "<div class='message info'><strong>Verifying setup...</strong></div>";
    
    $result = $conn->query("SHOW TABLES FROM school_facility_maintenance");
    $tableCount = $result ? $result->num_rows : 0;
    
    echo "<div class='stats-grid'>
        <div class='stat-box'>
            <div class='stat-number'>" . $tableCount . "</div>
            <div class='stat-label'>Tables Created</div>
        </div>";
    
    // Count users
    $usersResult = $conn->query("SELECT COUNT(*) as count FROM school_facility_maintenance.users");
    $usersCount = $usersResult ? $usersResult->fetch_assoc()['count'] : 0;
    echo "<div class='stat-box'>
            <div class='stat-number'>" . $usersCount . "</div>
            <div class='stat-label'>Users Created</div>
        </div>";
    
    // Count reports
    $reportsResult = $conn->query("SELECT COUNT(*) as count FROM school_facility_maintenance.maintenance_reports");
    $reportsCount = $reportsResult ? $reportsResult->fetch_assoc()['count'] : 0;
    echo "<div class='stat-box'>
            <div class='stat-number'>" . $reportsCount . "</div>
            <div class='stat-label'>Sample Reports</div>
        </div>";
    
    echo "</div>";
    
    if ($tableCount > 0 && $usersCount > 0) {
        echo "<div class='message success'>
            ✅ <strong>Database Setup Successful!</strong><br>
            Everything is ready to use.
        </div>";
    }
    
    // Show tables created
    if ($result && $result->num_rows > 0) {
        echo "<h3>✅ Tables Created:</h3>";
        echo "<table>";
        while ($row = $result->fetch_row()) {
            echo "<tr><td><code>" . htmlspecialchars($row[0]) . "</code></td></tr>";
        }
        echo "</table>";
    }
    
    // Show default login credentials
    echo "<h3>🔐 Default Login Credentials</h3>";
    echo "<div class='login-creds'>
        <div class='cred-item'><strong>Email:</strong> admin@school.edu</div>
        <div class='cred-item'><strong>Password:</strong> Admin@123</div>
        <br>
        <p style='color: #666; font-size: 13px;'>All test users have the same password: <code>Admin@123</code></p>
    </div>";
    
    echo "<h3>👥 Test User Accounts</h3>";
    $usersQuery = $conn->query("SELECT user_id, full_name, email, role FROM school_facility_maintenance.users ORDER BY user_id");
    if ($usersQuery && $usersQuery->num_rows > 0) {
        echo "<table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
            </tr>";
        while ($user = $usersQuery->fetch_assoc()) {
            echo "<tr>
                <td>" . $user['user_id'] . "</td>
                <td>" . htmlspecialchars($user['full_name']) . "</td>
                <td><code>" . htmlspecialchars($user['email']) . "</code></td>
                <td>" . htmlspecialchars($user['role']) . "</td>
            </tr>";
        }
        echo "</table>";
    }
    
    // Show sample reports
    echo "<h3>📋 Sample Maintenance Reports (5)</h3>";
    $reportsQuery = $conn->query("SELECT report_id, title, priority, status FROM school_facility_maintenance.maintenance_reports LIMIT 5");
    if ($reportsQuery && $reportsQuery->num_rows > 0) {
        echo "<table>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Priority</th>
                <th>Status</th>
            </tr>";
        while ($report = $reportsQuery->fetch_assoc()) {
            echo "<tr>
                <td>#" . $report['report_id'] . "</td>
                <td>" . htmlspecialchars($report['title']) . "</td>
                <td><strong>" . strtoupper($report['priority']) . "</strong></td>
                <td>" . str_replace('_', ' ', strtoupper($report['status'])) . "</td>
            </tr>";
        }
        echo "</table>";
    }
    
    echo "<button onclick=\"window.location.href='/School_Facility_Maintenance_System/frontend/pages/index.php'\">
        ✅ Go to Login Page
    </button>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='message error'>
        ❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
    </div>";
}

echo "
            <div class='footer-text'>
                <p>Set up completed on " . date('Y-m-d H:i:s') . "</p>
                <p>If you encounter any issues, contact system administrator</p>
            </div>
        </div>
    </div>
</body>
</html>
";
?>
