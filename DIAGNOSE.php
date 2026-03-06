<?php
/**
 * Complete Diagnostic - Check Database & API
 */

session_start();

// Force session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'super_admin';
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Complete System Check</title>
    <style>
        * { margin: 0; padding: 0; }
        body { font-family: 'Courier New', monospace; background: #0d1117; color: #c9d1d9; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #58a6ff; margin-bottom: 30px; }
        h2 { color: #79c0ff; margin-top: 30px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #30363d; }
        .panel { background: #161b22; border: 1px solid #30363d; border-radius: 6px; padding: 20px; margin: 15px 0; }
        .success { border-left: 5px solid #3fb950; }
        .error { border-left: 5px solid #f85149; }
        .warning { border-left: 5px solid #d29922; }
        .info { border-left: 5px solid #58a6ff; }
        code { background: #0d1117; padding: 3px 8px; border-radius: 3px; color: #a371f7; font-size: 13px; }
        pre { background: #0d1117; padding: 15px; border-radius: 4px; border: 1px solid #30363d; margin: 10px 0; overflow-x: auto; font-size: 12px; color: #79c0ff; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #30363d; }
        th { background: #0d1117; color: #79c0ff; }
        tr:hover { background: rgba(88, 166, 255, 0.05); }
        .action { display: inline-block; margin-top: 15px; padding: 12px 24px; background: #238636; color: white; border-radius: 6px; text-decoration: none; border: none; cursor: pointer; }
        .action:hover { background: #2ea043; }
        .stat { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 15px 0; }
        .stat-box { background: #0d1117; padding: 15px; border-radius: 6px; border-left: 4px solid #58a6ff; }
        .stat-number { font-size: 28px; font-weight: bold; color: #58a6ff; }
        .stat-label { color: #8b949e; font-size: 13px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Complete System Check & Diagnostic</h1>
";

// ============================================================================
// 1. DATABASE CHECK
// ============================================================================
echo "<div class='panel info'>
    <h2>1️⃣ Database Status</h2>";

try {
    require_once __DIR__ . '/backend/config/database.php';
    $pdo = getDBConnection();
    
    echo "<div style='color: #3fb950; margin-bottom: 15px;'>✓ Database connected</div>";
    
    // Get database info
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "<p>Database: <code>" . htmlspecialchars($dbName) . "</code></p>";
    
    // List tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tables: " . count($tables) . " found</p>";
    
    if (count($tables) === 0) {
        echo "<div class='error' style='padding: 15px; margin-top: 15px; border-radius: 6px;'>
            ❌ <strong>NO TABLES IN DATABASE!</strong><br>
            You must run: <a href='FRESH_SETUP.php' style='color: #58a6ff;'>FRESH_SETUP.php</a> first
        </div>";
        echo "</div>";
        echo "</div></body></html>";
        exit;
    }
    
} catch (Exception $e) {
    echo "<div class='error' style='padding: 15px; border-radius: 6px;'>
        ✗ Database Error: " . htmlspecialchars($e->getMessage()) . "
    </div>";
    echo "</div></div></body></html>";
    exit;
}

echo "</div>";

// ============================================================================
// 2. DATA COUNT
// ============================================================================
echo "<div class='panel info'>
    <h2>2️⃣ Data in Database</h2>";

try {
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $reportCount = $pdo->query("SELECT COUNT(*) FROM maintenance_reports")->fetchColumn();
    
    echo "<div class='stat'>
        <div class='stat-box'>
            <div class='stat-number'>" . $userCount . "</div>
            <div class='stat-label'>Users</div>
        </div>
        <div class='stat-box'>
            <div class='stat-number'>" . $reportCount . "</div>
            <div class='stat-label'>Reports</div>
        </div>
        <div class='stat-box'>
            <div class='stat-number'>" . $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn() . "</div>
            <div class='stat-label'>Departments</div>
        </div>
    </div>";
    
    if ($reportCount == 0) {
        echo "<div class='warning' style='padding: 15px; margin-top: 15px; border-radius: 6px;'>
            ⚠️ <strong>NO REPORTS IN DATABASE!</strong><br>
            Run <a href='FRESH_SETUP.php' style='color: #58a6ff;'>FRESH_SETUP.php</a> to create sample data, 
            or create a report manually.
        </div>";
    } else {
        echo "<div class='success' style='padding: 15px; margin-top: 15px; border-radius: 6px;'>
            ✓ Database has " . $reportCount . " reports ready
        </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// ============================================================================
// 3. TEST API DIRECTLY
// ============================================================================
echo "<div class='panel info'>
    <h2>3️⃣ API Test (Reports List)</h2>";

try {
    $query = "
        SELECT 
            r.*,
            creator.full_name as creator_name,
            creator.email as creator_email,
            assigned.full_name as assigned_name,
            d.name as department_name
        FROM maintenance_reports r
        LEFT JOIN users creator ON r.created_by = creator.user_id
        LEFT JOIN users assigned ON r.assigned_to = assigned.user_id
        LEFT JOIN departments d ON r.department_id = d.department_id
        ORDER BY r.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='success' style='padding: 12px; border-radius: 6px;'>
        ✓ Query executed successfully - " . count($reports) . " reports returned
    </div>";
    
    if (count($reports) > 0) {
        echo "<h3 style='margin-top: 15px; color: #79c0ff;'>Sample Data (First Report):</h3>";
        echo "<pre>" . htmlspecialchars(json_encode($reports[0], JSON_PRETTY_PRINT)) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<div class='error' style='padding: 12px; border-radius: 6px;'>
        ✗ Query Failed: " . htmlspecialchars($e->getMessage()) . "
    </div>";
}

echo "</div>";

// ============================================================================
// 4. SESSION CHECK
// ============================================================================
echo "<div class='panel info'>
    <h2>4️⃣ Session & Authentication</h2>";

echo "<p>Session ID: <code>" . htmlspecialchars(session_id()) . "</code></p>";
echo "<p>User ID: <code>" . ($_SESSION['user_id'] ?? 'NOT SET') . "</code></p>";
echo "<p>Role: <code>" . ($_SESSION['role'] ?? 'NOT SET') . "</code></p>";

if ($_SESSION['user_id']) {
    $user = $pdo->query("SELECT user_id, full_name, email, role FROM users WHERE user_id = " . (int)$_SESSION['user_id'])->fetch();
    if ($user) {
        echo "<div class='success' style='padding: 12px; margin-top: 15px; border-radius: 6px;'>
            ✓ User exists in database
        </div>";
        echo "<p><strong>Name:</strong> " . htmlspecialchars($user['full_name']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>";
        echo "<p><strong>Role:</strong> " . htmlspecialchars($user['role']) . "</p>";
    }
}

echo "</div>";

// ============================================================================
// 5. FILE CHECK
// ============================================================================
echo "<div class='panel info'>
    <h2>5️⃣ Critical Files</h2>";

$files = [
    'backend/config/database.php',
    'backend/config/settings.php',
    'backend/api/reports.php',
    'frontend/assets/js/api.js',
    'frontend/pages/reports.php',
    'frontend/pages/dashboard.php'
];

$allExist = true;
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<div style='color: #3fb950; padding: 8px;'>✓ " . htmlspecialchars($file) . "</div>";
    } else {
        echo "<div style='color: #f85149; padding: 8px;'>✗ " . htmlspecialchars($file) . "</div>";
        $allExist = false;
    }
}

if (!$allExist) {
    echo "<div class='error' style='padding: 12px; margin-top: 15px; border-radius: 6px;'>
        Some files are missing!
    </div>";
}

echo "</div>";

// ============================================================================
// 6. NEXT STEPS
// ============================================================================
echo "<div class='panel warning'>
    <h2>⚡ Next Steps</h2>";

if ($reportCount == 0) {
    echo "<h3 style='color: #d29922; margin-bottom: 10px;'>Database is empty!</h3>";
    echo "<p>Choose one:</p>";
    echo "<ol style='margin-left: 20px; margin-top: 10px;'>";
    echo "  <li><button class='action' onclick=\"window.location.href='FRESH_SETUP.php'\">🔄 Run FRESH_SETUP.php (Import Sample Data)</button></li>";
    echo "  <li>OR manually create a report using the "+ New Report" button</li>";
    echo "</ol>";
} else {
    echo "<h3 style='color: #3fb950; margin-bottom: 10px;'>✓ Database has data!</h3>";
    echo "<ol style='margin-left: 20px; margin-top: 10px;'>";
    echo "  <li>Clear your browser cache: <strong>Ctrl+Shift+Delete</strong></li>";
    echo "  <li>Refresh the page: <strong>F5</strong> or <strong>Ctrl+R</strong>";
    echo "  <li><button class='action' onclick=\"window.location.href='/School_Facility_Maintenance_System/frontend/pages/reports.php' style='background: #58a6ff;'\">📋 Go to Reports Page</button>";
    echo "</ol>";
}

echo "</div>";

echo "
    </div>
</body>
</html>
";
?>
