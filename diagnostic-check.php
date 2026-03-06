<?php
// Diagnostic check for Maintenance Dashboard and User Management

echo "<h1>🔍 Diagnostics Check</h1>";
echo "<hr>";

// Check 1: Does maintenance-analytics.php exist?
echo "<h2>1. Check maintenance-analytics.php:</h2>";
$analyticsPath = __DIR__ . '/frontend/pages/maintenance-analytics.php';
if (file_exists($analyticsPath)) {
    echo "<span style='color: green;'>✓ File exists</span>";
} else {
    echo "<span style='color: red;'>✗ File NOT found - This page is referenced in maintenance-dashboard.php but doesn't exist</span>";
}
echo "<br>";

// Check 2: Does maintenance-dashboard.js exist?
echo "<h2>2. Check maintenance-dashboard.js:</h2>";
$jsPath = __DIR__ . '/frontend/assets/js/maintenance-dashboard.js';
if (file_exists($jsPath)) {
    echo "<span style='color: green;'>✓ File exists</span>";
} else {
    echo "<span style='color: red;'>✗ File NOT found</span>";
}
echo "<br>";

// Check 3: Test the maintenance-dashboard-api.php
echo "<h2>3. Test maintenance-dashboard-api.php:</h2>";
echo "Try this URL: <code>http://localhost/School_Facility_Maintenance_System/backend/api/maintenance-dashboard-api.php?action=stats</code>";
echo "<br>";

// Check 4: Test the users-api.php
echo "<h2>4. Test users-api.php:</h2>";
echo "Try this URL: <code>http://localhost/School_Facility_Maintenance_System/backend/api/users-api.php?action=list</code>";
echo "<br>";

// Check 5: Verify session_start() in auth.php
echo "<h2>5. Check auth.php has session_start():</h2>";
$authContent = file_get_contents(__DIR__ . '/backend/api/auth.php');
if (strpos($authContent, 'session_start()') !== false) {
    echo "<span style='color: green;'>✓ session_start() found</span>";
} else {
    echo "<span style='color: red;'>✗ session_start() NOT found</span>";
}
echo "<br>";

// Check 6: Verify users-api.php session handling
echo "<h2>6. Check users-api.php has SessionMiddleware:</h2>";
$usersContent = file_get_contents(__DIR__ . '/backend/api/users-api.php');
if (strpos($usersContent, 'SessionMiddleware') !== false) {
    echo "<span style='color: green;'>✓ SessionMiddleware found</span>";
} else {
    echo "<span style='color: red;'>✗ SessionMiddleware NOT found</span>";
}
echo "<br>";

?>
