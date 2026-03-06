<?php
/**
 * save_report.php
 * Accepts JSON { "title": "...", "description": "..." }
 * Inserts a persistent report row into `reports` using PDO prepared statements.
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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];

$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');

if ($title === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit;
}

if ($description === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Description is required']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO reports (title, description, created_at) VALUES (?, ?, NOW())');
    $ok = $stmt->execute([$title, $description]);

    if ($ok) {
        $id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Report saved', 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save report']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error', 'detail' => $e->getMessage()]);
}

?>
