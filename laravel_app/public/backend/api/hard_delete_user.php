<?php
/**
 * Hard Delete User API (no sessions)
 * Permanently removes a user row from the `users` table using a prepared statement.
 * NOTE: This endpoint does NOT use sessions. Protect access to it (network or firewall)
 * if you want to restrict who can call it.
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

$id = $data['id'] ?? null;

if ($id === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing `id` in request body']);
    exit;
}

// Ensure integer
$id = filter_var($id, FILTER_VALIDATE_INT);
if ($id === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid id']);
    exit;
}

try {
    // Check existence
    $check = $pdo->prepare('SELECT user_id FROM users WHERE user_id = ?');
    $check->execute([$id]);
    $row = $check->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Perform hard delete using prepared statement
    $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
    $ok = $stmt->execute([$id]);

    if ($ok) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'User permanently deleted']);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Delete failed']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error', 'detail' => $e->getMessage()]);
    exit;
}

?>
