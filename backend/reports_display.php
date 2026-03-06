<?php
/**
 * reports_display.php
 * Simple server-side page to fetch and render saved reports from DB.
 */
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo "<p>Database connection failed</p>";
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, title, description, created_at FROM reports ORDER BY created_at DESC');
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reports = [];
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Saved Reports</title>
    <style>body{font-family:Arial,Helvetica,sans-serif;background:#f7f7f7;padding:20px} .report{background:#fff;padding:15px;border-radius:6px;margin-bottom:12px;box-shadow:0 1px 4px rgba(0,0,0,0.06)}.meta{font-size:12px;color:#666}</style>
</head>
<body>
    <h1>Saved Reports</h1>
    <?php if (empty($reports)): ?>
        <p>No reports found.</p>
    <?php else: ?>
        <?php foreach ($reports as $r): ?>
            <div class="report">
                <h3><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="meta">Created at: <?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?> | ID: <?= (int)$r['id'] ?></div>
                <p><?= nl2br(htmlspecialchars($r['description'], ENT_QUOTES, 'UTF-8')) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
