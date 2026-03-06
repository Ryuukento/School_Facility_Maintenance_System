<?php
/**
 * API Diagnostic - Check what's happening
 */

session_start();

// Set test session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'super_admin';
}

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>API Test & Diagnostic</title>
    <style>
        * { margin: 0; padding: 0; }
        body { font-family: 'Courier New', monospace; background: #0d1117; color: #c9d1d9; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #58a6ff; margin-bottom: 20px; }
        h2 { color: #79c0ff; margin-top: 30px; margin-bottom: 10px; border-bottom: 2px solid #30363d; padding-bottom: 10px; }
        .section { background: #161b22; border: 1px solid #30363d; border-radius: 6px; padding: 15px; margin: 15px 0; }
        .success { border-left: 4px solid #3fb950; background: rgba(63, 185, 80, 0.1); }
        .error { border-left: 4px solid #f85149; background: rgba(248, 81, 73, 0.1); }
        .warning { border-left: 4px solid #d29922; background: rgba(210, 153, 34, 0.1); }
        .info { border-left: 4px solid #58a6ff; background: rgba(88, 166, 255, 0.1); }
        code { background: #0d1117; padding: 2px 6px; border-radius: 3px; }
        pre { background: #0d1117; padding: 15px; border-radius: 6px; overflow-x: auto; border: 1px solid #30363d; margin: 10px 0; }
        button { padding: 10px 20px; background: #238636; color: white; border: none; border-radius: 6px; cursor: pointer; margin: 10px 5px 10px 0; }
        button:hover { background: #2ea043; }
        .api-call { background: #0d1117; border: 1px solid #30363d; padding: 15px; border-radius: 6px; margin: 10px 0; }
        .request { color: #79c0ff; margin: 5px 0; }
        .response { color: #a371f7; margin: 5px 0; white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 API Diagnostic Test</h1>
";

// 1. Database Check
echo "<div class='section info'>
    <h2>1️⃣ Database Status</h2>";

try {
    require_once __DIR__ . '/backend/config/database.php';
    $pdo = getDBConnection();
    
    echo "<div class='success'>✓ Database connected</div>";
    
    // Check database name
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "<div>Database: <code>" . htmlspecialchars($dbName) . "</code></div>";
    
    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<div>Tables found: " . count($tables) . "</div>";
    
    if (count($tables) === 0) {
        echo "<div class='error'>❌ NO TABLES FOUND! You need to run FRESH_SETUP.php first</div>";
        echo "<p style='margin-top: 10px;'><a href='FRESH_SETUP.php' style='color: #58a6ff;'>👉 Click here to run FRESH_SETUP.php</a></p>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// 2. Session Check
echo "<div class='section info'>
    <h2>2️⃣ Session Status</h2>";
echo "<div>user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</div>";
echo "<div>role: " . ($_SESSION['role'] ?? 'NOT SET') . "</div>";
echo "</div>";

// 3. Test API Calls
echo "<div class='section info'>
    <h2>3️⃣ API Test Calls</h2>";

// Test Reports API
echo "<h3>Testing: /backend/api/reports.php?action=list</h3>";
echo "<div class='api-call'>";

try {
    // Simulate API request by directly requiring the file
    $testUrl = 'http://localhost/School_Facility_Maintenance_System/backend/api/reports.php?action=list';
    echo "<div class='request'>GET " . htmlspecialchars($testUrl) . "</div>";
    
    // Use cURL if available
    if (function_exists('curl_init')) {
        $ch = curl_init($testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        // Set fake session cookie
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "<div class='error'>❌ cURL Error: " . htmlspecialchars($error) . "</div>";
        } else {
            echo "<div>HTTP Status: " . $httpCode . "</div>";
            echo "<div class='response'>" . htmlspecialchars($response) . "</div>";
            
            // Try to decode JSON
            $data = json_decode($response, true);
            if ($data) {
                echo "<div style='margin-top: 10px; color: #3fb950;'>";
                if (isset($data['success'])) {
                    echo \"<strong>✓ API Response is valid JSON</strong><br>\";
                    echo \"Success: \" . ($data['success'] ? 'true' : 'false') . \"<br>\";
                    echo \"Message: \" . htmlspecialchars($data['message'] ?? 'N/A') . \"<br>\";
                    
                    if (isset($data['data']['reports'])) {
                        echo \"Reports found: \" . count($data['data']['reports']) . \"<br>\";
                    }
                }
                echo \"</div>\";
            }
        }
    } else {
        echo "<div class='warning'>⚠ cURL not available, trying file_get_contents...</div>";
        
        // Check if tables have data
        $reportsCount = $pdo->query(\"SELECT COUNT(*) FROM maintenance_reports\")->fetchColumn();
        echo \"<div>Reports in database: \" . $reportsCount . \"</div>\";
        
        if ($reportsCount == 0) {
            echo \"<div class='error'>❌ No reports in database!</div>\";
        }
    }
    
} catch (Exception $e) {
    echo \"<div class='error'>Error: \" . htmlspecialchars($e->getMessage()) . \"</div>\";
}

echo \"</div></div>\";

// 4. Database Data Check
echo \"<div class='section info'>
    <h2>4️⃣ Database Data Check</h2>\";

try {
    $pdo = getDBConnection();
    
    // Count users
    \$userCount = \$pdo->query(\"SELECT COUNT(*) FROM users\")->fetchColumn();
    echo \"<div>Users: <strong>\" . \$userCount . \"</strong></div>\";
    
    // Count reports  
    \$reportCount = \$pdo->query(\"SELECT COUNT(*) FROM maintenance_reports\")->fetchColumn();
    echo \"<div>Reports: <strong>\" . \$reportCount . \"</strong></div>\";
    
    if (\$reportCount == 0) {
        echo \"<div class='error' style='margin-top: 15px;'>
            ❌ <strong>NO REPORTS IN DATABASE</strong><br>
            You need to import the sample data first.
        </div>\";
    } else {
        echo \"<div class='success' style='margin-top: 15px;'>
            ✓ Database has \" . \$reportCount . \" reports ready
        </div>\";
        
        // Show sample reports
        echo \"<h3>Sample Reports:</h3>\";
        \$reports = \$pdo->query(\"SELECT report_id, title, status FROM maintenance_reports LIMIT 3\")->fetchAll();
        foreach (\$reports as \$r) {
            echo \"<div style='padding: 5px; margin: 5px 0; background: #0d1117; border-left: 3px solid #58a6ff;'>\";
            echo \"  #\" . \$r['report_id'] . \" - \" . htmlspecialchars(\$r['title']) . \" [\" . \$r['status'] . \"]\";
            echo \"</div>\";
        }
    }
    
} catch (Exception \$e) {
    echo \"<div class='error'>Error checking data: \" . htmlspecialchars(\$e->getMessage()) . \"</div>\";
}

echo \"</div>\";

// 5. Quick Actions
echo \"<div class='section warning'>
    <h2>⚡ Quick Actions</h2>\";

echo \"<p>If reports are not showing, you need to:</p>\";
echo \"<ol style='margin-left: 20px; margin-top: 10px;'>\";
echo \"  <li><a href='FRESH_SETUP.php' style='color: #58a6ff; text-decoration: none;'><strong>🔄 Run FRESH_SETUP.php</strong></a> - Clears and re-imports database</li>\";
echo \"  <li><a href='javascript:location.reload()' style='color: #58a6ff; text-decoration: none;'><strong>🔄 Refresh this page</strong></a></li>\";
echo \"  <li><a href='/School_Facility_Maintenance_System/frontend/pages/reports.php' style='color: #58a6ff; text-decoration: none;'><strong>📋 Go back to Reports</strong></a></li>\";
echo \"</ol>\";

echo \"</div>\";

echo \"
    </div>
</body>
</html>
\";
?>
