<?php
/**
 * COMPLETE SYSTEM DIAGNOSTIC
 * Checks everything step by step
 */

session_start();

// Force admin session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Complete System Check</title>
    <style>
        * { margin: 0; padding: 0; }
        body { font-family: monospace; background: #0a0e27; color: #e0e6ed; padding: 30px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #60f; font-size: 32px; margin-bottom: 30px; }
        .test { background: #1a1f3a; border: 2px solid #30363d; border-radius: 8px; padding: 20px; margin: 15px 0; }
        .pass { border-color: #3fb950; background: rgba(63, 185, 80, 0.05); }
        .fail { border-color: #f85149; background: rgba(248, 81, 73, 0.05); }
        .warning { border-color: #d29922; background: rgba(210, 153, 34, 0.05); }
        h2 { color: #79c0ff; font-size: 20px; margin-bottom: 15px; margin-top: 0; }
        .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #30363d; }
        .row:last-child { border-bottom: none; }
        .label { color: #8b949e; font-weight: bold; }
        .value { color: #e0e6ed; }
        .code { background: #0d1117; padding: 15px; border-radius: 4px; border-left: 3px solid #58a6ff; margin: 10px 0; overflow-x: auto; }
        button { padding: 12px 24px; background: #238636; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; margin: 5px; }
        button:hover { background: #2ea043; }
        .status-icon { font-weight: bold; margin-right: 5px; }
        .table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .table th, .table td { padding: 10px; text-align: left; border-bottom: 1px solid #30363d; }
        .table th { background: #0d1117; color: #79c0ff; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Complete System Diagnostic</h1>
";

// ============================================
// 1. DATABASE CONNECTION
// ============================================
echo "<div class='test ";

try {
    require_once __DIR__ . '/backend/config/database.php';
    $pdo = getDBConnection();
    
    echo "pass'>
        <h2><span class='status-icon'>✓</span> Database Connection</h2>";
    
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "<div class='row'><div class='label'>Connected Database:</div><div class='value'>" . htmlspecialchars($dbName) . "</div></div>";
    
} catch (Exception $e) {
    echo "fail'>
        <h2><span class='status-icon'>✗</span> Database Connection Failed</h2>
        <div class='code'>" . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

echo "</div>";

// ============================================
// 2. TABLES CHECK
// ============================================
echo "<div class='test ";

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

if (count($tables) > 0) {
    echo "pass'>
        <h2><span class='status-icon'>✓</span> Database Tables</h2>
        <div class='row'><div class='label'>Tables Found:</div><div class='value'>" . count($tables) . "</div></div>
        <div class='code'>";
    foreach ($tables as $t) {
        echo htmlspecialchars($t) . "\n";
    }
    echo "</div>";
} else {
    echo "fail'>
        <h2><span class='status-icon'>✗</span> No Tables Found</h2>
        <p style='color: #f85149; margin-top: 10px;'>⚠️ Database is empty. You must run:</p>
        <button onclick=\"window.location.href='FRESH_SETUP.php'\" style='background: #f85149;'>👉 Click to Run FRESH_SETUP.php</button>";
    echo "</div>";
    exit;
}

echo "</div>";

// ============================================
// 3. DATA CHECK
// ============================================
echo "<div class='test ";

$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$reportCount = $pdo->query("SELECT COUNT(*) FROM maintenance_reports")->fetchColumn();

if ($userCount > 0 && $reportCount >= 0) {
    echo ($reportCount > 0 ? "pass" : "warning") . "'>
        <h2><span class='status-icon'>" . ($reportCount > 0 ? "✓" : "⚠") . "</span> Data in Database</h2>
        <div class='row'><div class='label'>Users:</div><div class='value'>" . $userCount . "</div></div>
        <div class='row'><div class='label'>Reports:</div><div class='value'>" . $reportCount . "</div></div>";
    
    if ($reportCount == 0) {
        echo "<p style='color: #d29922; margin-top: 10px;'>⚠️ No reports yet. Create one or run FRESH_SETUP to import sample data.</p>";
    }
} else {
    echo "fail'>
        <h2><span class='status-icon'>✗</span> Data Error</h2>";
}

echo "</div>";

// ============================================
// 4. RECENT REPORTS
// ============================================
echo "<div class='test pass'>
    <h2><span class='status-icon'>📋</span> Recent Reports (First 5)</h2>";

try {
    $reports = $pdo->query("
        SELECT report_id, title, status, priority, created_at 
        FROM maintenance_reports 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
    if (count($reports) > 0) {
        echo "<table class='table'>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Created</th>
            </tr>";
        
        foreach ($reports as $r) {
            echo "<tr>";
            echo "<td>#" . htmlspecialchars($r['report_id']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($r['title'], 0, 50)) . "</td>";
            echo "<td>" . htmlspecialchars($r['status']) . "</td>";
            echo "<td>" . htmlspecialchars($r['priority']) . "</td>";
            echo "<td>" . htmlspecialchars($r['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: #d29922;'>No reports in database</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: #f85149;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// ============================================
// 5. SESSION CHECK
// ============================================
echo "<div class='test pass'>
    <h2><span class='status-icon'>🔐</span> Session Status</h2>
    <div class='row'><div class='label'>Session ID:</div><div class='value'>" . htmlspecialchars(session_id()) . "</div></div>
    <div class='row'><div class='label'>User ID:</div><div class='value'>" . ($_SESSION['user_id'] ?? 'NOT SET') . "</div></div>
    <div class='row'><div class='label'>Role:</div><div class='value'>" . ($_SESSION['role'] ?? 'NOT SET') . "</div></div>
</div>";

// ============================================
// 6. API SIMULATION TEST
// ============================================
echo "<div class='test ";

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
    $apiReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo (count($apiReports) > 0 ? "pass" : "warning") . "'>
        <h2><span class='status-icon'>🔌</span> API Database Query</h2>
        <div class='row'><div class='label'>Results:</div><div class='value'>" . count($apiReports) . " reports</div></div>";
    
    if (count($apiReports) > 0) {
        echo "<h3 style='color: #79c0ff; margin-top: 15px;'>Sample API Response:</h3>";
        echo "<div class='code'>" . htmlspecialchars(json_encode($apiReports[0], JSON_PRETTY_PRINT)) . "</div>";
    }
    
} catch (Exception $e) {
    echo "fail'>
        <h2><span class='status-icon'>✗</span> API Query Error</h2>
        <div class='code'>" . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// ============================================
// 7. FILES CHECK
// ============================================
echo "<div class='test pass'>
    <h2><span class='status-icon'>📁</span> Critical Files</h2>";

$files = [
    '/backend/config/database.php',
    '/backend/config/settings.php',
    '/backend/api/reports.php',
    '/frontend/assets/js/api.js',
    '/frontend/pages/reports.php'
];

foreach ($files as $f) {
    $exists = file_exists(__DIR__ . $f) ? '✓' : '✗';
    $color = file_exists(__DIR__ . $f) ? '#3fb950' : '#f85149';
    echo "<div class='row'><div class='label'>$exists " . htmlspecialchars($f) . "</div><div class='value' style='color: $color;'></div></div>";
}

echo "</div>";

// ============================================
// 8. NEXT STEPS
// ============================================
echo "<div class='test warning'>
    <h2>⚡ What To Do</h2>";

if ($reportCount == 0) {
    echo "<p style='color: #d29922; margin-bottom: 15px;'><strong>Step 1: Import Sample Data</strong></p>
    <button onclick=\"window.location.href='FRESH_SETUP.php'\" style='background: #d29922;'>
        🔄 Run FRESH_SETUP.php
    </button>";
}

echo "<p style='color: #e0e6ed; margin-top: 20px; margin-bottom: 15px;'><strong>Step 2: Clear Browser Cache & Reload</strong></p>
<p style='color: #8b949e; font-size: 12px;'>Press: Ctrl+Shift+Delete, clear all, then refresh</p>

<p style='color: #e0e6ed; margin-top: 20px; margin-bottom: 15px;'><strong>Step 3: Go to Pages</strong></p>
<button onclick=\"window.location.href='/School_Facility_Maintenance_System/frontend/pages/reports.php'\">
    📋 Go to Reports Page
</button>
<button onclick=\"window.location.href='/School_Facility_Maintenance_System/frontend/pages/dashboard.php'\">
    📊 Go to Dashboard
</button>

</div>";

echo "
    </div>
</body>
</html>
";
?>
