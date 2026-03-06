<?php
/**
 * PASSWORD RESET TOOL - RESET ALL USERS
 * Fixes bcrypt hashing (cost 12) for all test accounts
 */

// Database connection
$host = 'localhost';
$db = 'school_facility_maintenance';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected!\n\n";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Password to use for all test accounts
$password = 'admin123';
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "🔐 Resetting ALL user passwords to: admin123\n";
echo "   Using bcrypt cost 12 (secure)\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // Reset password for ALL users
    $stmt = $pdo->prepare("UPDATE users SET password = :password");
    $stmt->execute([':password' => $hashedPassword]);
    
    $affectedRows = $stmt->rowCount();
    
    if ($affectedRows > 0) {
        echo "✅ SUCCESS! Updated $affectedRows user accounts\n\n";
    } else {
        echo "⚠️  No users found in database\n";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit;
}

// List all users with new password
echo str_repeat("=", 60) . "\n";
echo "ALL USER ACCOUNTS - Login Credentials:\n";
echo str_repeat("=", 60) . "\n";
echo sprintf("%-3s | %-20s | %-30s | %-20s\n", 'ID', 'Name', 'Email', 'Role');
echo str_repeat("-", 60) . "\n";

try {
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, role FROM users ORDER BY user_id");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $u) {
        echo sprintf("%-3s | %-20s | %-30s | %-20s\n", 
            $u['user_id'], 
            substr($u['full_name'], 0, 20),
            $u['email'],
            $u['role']
        );
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🔑 PASSWORD FOR ALL ACCOUNTS: admin123\n";
echo str_repeat("=", 60) . "\n";
echo "\n✨ Try these accounts:\n";
echo "   Super Admin:   admin@school.edu\n";
echo "   Dept Admin:    elec.admin@school.edu\n";
echo "   Dept Admin:    plumb.admin@school.edu\n";
echo "   Dept Admin:    hvac.admin@school.edu\n";
echo "   Maintenance:   john.smith@school.edu\n";
echo "   Regular User:  sarah.johnson@school.edu\n";
echo "\n🎉 All accounts are now ready to login!\n";
echo str_repeat("=", 60) . "\n";

?>
