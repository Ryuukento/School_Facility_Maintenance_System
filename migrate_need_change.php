<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=school_facility_maintenance', 'root', '');

$sql = "ALTER TABLE maintenance_reports ADD COLUMN IF NOT EXISTS need_change_item_id INT NULL AFTER due_date,
ADD COLUMN IF NOT EXISTS need_change_quantity INT DEFAULT 1 AFTER need_change_item_id,
ADD COLUMN IF NOT EXISTS need_change_status VARCHAR(50) NULL AFTER need_change_quantity,
ADD COLUMN IF NOT EXISTS need_change_approved_by INT NULL AFTER need_change_status,
ADD COLUMN IF NOT EXISTS need_change_approved_at DATETIME NULL AFTER need_change_approved_by,
ADD COLUMN IF NOT EXISTS need_change_deducted_at DATETIME NULL AFTER need_change_approved_at";

try {
    $pdo->exec($sql);
    echo "✅ SUCCESS! Added need_change columns to maintenance_reports table\n";
    
    // Verify
    $verify = $pdo->query("DESCRIBE maintenance_reports");
    $cols = $verify->fetchAll(PDO::FETCH_ASSOC);
    $needChangeCols = array_filter($cols, fn($c) => strpos($c['Field'], 'need_change') !== false);
    
    echo "\n✅ Columns created:\n";
    foreach ($needChangeCols as $col) {
        echo "   - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
