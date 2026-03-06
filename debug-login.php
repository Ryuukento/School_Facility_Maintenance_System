<?php
// Debug login issue

require_once __DIR__ . '/backend/config/database.php';

$pdo = getDBConnection();

echo "<h2>🔍 Debugging Login Issue</h2>";
echo "<hr>";

// Check if maintenance admin user exists
echo "<h3>1. Check if maintenance.admin@school.edu exists:</h3>";
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'maintenance.admin@school.edu'");
$stmt->execute();
$user = $stmt->fetch();

if ($user) {
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    // Test password verification
    $testPassword = 'Admin@123';
    $passwordHash = $user['password'];
    
    echo "<h3>2. Testing password verification:</h3>";
    echo "Test Password: $testPassword<br>";
    echo "Stored Hash: " . substr($passwordHash, 0, 20) . "...<br>";
    
    if (password_verify($testPassword, $passwordHash)) {
        echo "<strong style='color: green;'>✓ Password verification PASSED</strong><br>";
    } else {
        echo "<strong style='color: red;'>✗ Password verification FAILED</strong><br>";
    }
} else {
    echo "<strong style='color: red;'>✗ User not found in database</strong><br>";
    echo "<h3>All users in database:</h3>";
    $stmt = $pdo->query("SELECT user_id, full_name, email, role FROM users");
    $users = $stmt->fetchAll();
    echo "<pre>";
    print_r($users);
    echo "</pre>";
}

// Check if the password hash in database matches what we think it should be
echo "<h3>3. Password hash analysis:</h3>";
$testHash = '$2y$12$thIZHPu74DGT2MGOFHQP6e5rMLQSvM20oqWAmZVfPkH1JnXN4TnPC';
if (password_verify('Admin@123', $testHash)) {
    echo "<strong style='color: green;'>✓ Test hash is correct for password 'Admin@123'</strong>";
} else {
    echo "<strong style='color: red;'>✗ Test hash is NOT correct for password 'Admin@123'</strong>";
}

?>
