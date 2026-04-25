<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=school_facility_maintenance', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if user exists (user_id = 14 has full_name = "5756756")
$check = $pdo->prepare('SELECT user_id, full_name, email, status FROM users WHERE user_id = ?');
$check->execute([14]);
$user = $check->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Found user: " . $user['full_name'] . " (" . $user['email'] . ") - Status: " . $user['status'] . PHP_EOL;
    
    // Delete activity logs first (foreign key constraint)
    $delLogs = $pdo->prepare('DELETE FROM activity_logs WHERE user_id = ?');
    $delLogs->execute([14]);
    echo "Deleted " . $delLogs->rowCount() . " activity log entries" . PHP_EOL;
    
    // Delete user
    $del = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
    $del->execute([14]);
    echo "✓ User account deleted successfully!" . PHP_EOL;
} else {
    echo "❌ User ID 14 not found" . PHP_EOL;
}
