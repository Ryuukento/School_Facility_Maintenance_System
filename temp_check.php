<?php
require_once __DIR__ . '/backend/config/database.php';
$pdo = getDBConnection();
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
