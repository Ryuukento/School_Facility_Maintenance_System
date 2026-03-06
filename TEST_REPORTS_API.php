<?php
/**
 * Simple Test Script - Tests if reports are working end to end
 */

// Start session
session_start();

// Set a test session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';
$_SESSION['user'] = [
    'user_id' => 1,
    'full_name' => 'Test Admin',
    'email' => 'admin@school.edu',
    'role' => 'super_admin'
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Reports API Test</title>
    <style>
        body { font-family: monospace; background: #0d1117; color: #e6edf3; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #58a6ff; }
        .test { background: #161b22; border: 1px solid #30363d; border-radius: 6px; padding: 15px; margin: 15px 0; }
        .success { border-color: #3fb950; }
        .error { border-color: #f85149; }
        pre { background: #0d1117; padding: 10px; border-radius: 4px; overflow-x: auto; }
        code { color: #79c0ff; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔬 Reports API Test</h1>";

// Test 1: Check session
echo "<div class='test success'>
    <h2>Session Status</h2>
    <p><strong>Session ID:</strong> " . session_id() . "</p>
    <p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>
    <p><strong>Role:</strong> " . ($_SESSION['role'] ?? 'NOT SET') . "</p>
    <p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'NOT ACTIVE') . "</p>
</div>";

// Test 2: Database connection
echo "<div class='test'>";
try {
    require_once __DIR__ . '/backend/config/settings.php';
    require_once __DIR__ . '/backend/config/database.php';
    
    $pdo = getDBConnection();
    echo "<h2>✅ Database Connection</h2>";
    
    // Test users table
    $result = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $result->fetch()['count'];
    echo "<p><strong>Users in DB:</strong> $userCount</p>";
    
    // Test reports table
    $result = $pdo->query("SELECT COUNT(*) as count FROM maintenance_reports");
    $reportCount = $result->fetch()['count'];
    echo "<p><strong>Reports in DB:</strong> $reportCount</p>";
    
    echo "<h3>Sample Reports:</h3>";
    echo "<pre>";
    $result = $pdo->query("SELECT report_id, title, status, priority FROM maintenance_reports LIMIT 3");
    $reports = $result->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($reports, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test error'>";
    echo "<h2>❌ Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

// Test 3: Simulate API call
echo "<div class='test'>";
echo "<h2>Simulated API Response (with session active)</h2>";

try {
    // Include the API functions
    require_once __DIR__ . '/backend/api/reports.php';
    
} catch (Exception $e) {
    echo "<p style='color: #f85149;'>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "
    </div>
</body>
</html>";
?>
