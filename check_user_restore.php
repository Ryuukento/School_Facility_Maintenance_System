<?php
require 'backend/config/database.php';
try {
    $pdo = getDBConnection();

    echo "Current users:\n";
    $stmt = $pdo->query("SELECT user_id, full_name, email, role, status, created_at FROM users ORDER BY user_id ASC");
    $users = $stmt->fetchAll();
    foreach ($users as $u) {
        echo "- {$u['user_id']} | {$u['full_name']} | {$u['email']} | {$u['role']} | {$u['status']} | created: {$u['created_at']}\n";
    }

    echo "\nRecent activity logs (CREATE_USER, DELETE_USER, CREATE_REPORT):\n";
    $stmt = $pdo->prepare("SELECT log_id, user_id, action, entity_type, entity_id, details, created_at FROM activity_logs WHERE action IN ('CREATE_USER','DELETE_USER','CREATE_REPORT','UPDATE_REPORT') ORDER BY created_at DESC LIMIT 50");
    $stmt->execute();
    $logs = $stmt->fetchAll();
    foreach ($logs as $l) {
        echo "- [{$l['created_at']}] id:{$l['log_id']} user:{$l['user_id']} action:{$l['action']} entity:{$l['entity_type']} eid:{$l['entity_id']} details:{$l['details']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>