<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=school_facility_maintenance', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Current Users in System ===\n";
$stmt = $pdo->query('SELECT user_id, full_name, email, status FROM users ORDER BY user_id DESC LIMIT 15');
foreach ($stmt as $row) {
    echo $row['user_id'] . " - " . $row['full_name'] . " (" . $row['email'] . ") - " . $row['status'] . "\n";
}
