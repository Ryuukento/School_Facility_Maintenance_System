<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=school_facility_maintenance', 'root', '');
$stmt = $pdo->query('DESCRIBE maintenance_reports');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== ALL COLUMNS ===\n";
foreach ($cols as $col) {
    echo $col['Field'] . "\n";
}

echo "\n=== NEED_CHANGE COLUMNS ===\n";
$needChangeCols = array_filter($cols, fn($c) => strpos($c['Field'], 'need_change') !== false);
if (empty($needChangeCols)) {
    echo "❌ NO NEED_CHANGE COLUMNS FOUND!\n";
} else {
    foreach ($needChangeCols as $col) {
        echo "✅ " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}

// Check latest report
echo "\n=== LATEST REPORT ===\n";
$latestStmt = $pdo->query('SELECT report_id, title, need_change_item_id, need_change_status FROM maintenance_reports ORDER BY report_id DESC LIMIT 1');
$latest = $latestStmt->fetch(PDO::FETCH_ASSOC);
if ($latest) {
    echo "Report #" . $latest['report_id'] . ": " . $latest['title'] . "\n";
    echo "need_change_item_id: " . ($latest['need_change_item_id'] ?? 'NULL') . "\n";
    echo "need_change_status: " . ($latest['need_change_status'] ?? 'NULL') . "\n";
} else {
    echo "No reports found\n";
}
?>
