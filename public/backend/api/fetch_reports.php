<?php
/**
 * fetch_reports.php
 * Returns JSON list of reports stored in `reports` table.
 */

require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, title, description, created_at FROM reports ORDER BY created_at DESC');
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'reports' => $reports]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error', 'detail' => $e->getMessage()]);
}

?>
