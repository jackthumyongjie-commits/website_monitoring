<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/web_cron.php';

header('Content-Type: application/json; charset=utf-8');

web_cron_tick($pdo, 'list-data');

$count = (int) $pdo->query('SELECT COUNT(*) FROM websites')->fetchColumn();
$ids = $pdo->query('SELECT id FROM websites ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'count' => $count,
    'ids' => array_map('intval', $ids),
], JSON_UNESCAPED_UNICODE);
