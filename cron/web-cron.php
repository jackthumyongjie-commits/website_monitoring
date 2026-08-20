<?php
/**
 * Web cron endpoint — call every minute (public uptime page does this automatically).
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/web_cron.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$result = web_cron_tick($pdo, 'web-cron');
echo json_encode($result, JSON_UNESCAPED_UNICODE);
