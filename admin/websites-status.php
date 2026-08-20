<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/web_cron.php';

header('Content-Type: application/json; charset=utf-8');

web_cron_tick($pdo, 'admin-status');

$rows = $pdo->query(
    'SELECT id, status, response_time, last_checked, is_slow FROM websites ORDER BY id ASC'
)->fetchAll();

$items = [];
foreach ($rows as $site) {
    $statusHtml = status_badge((string) $site['status']);
    if ((int) $site['is_slow'] === 1) {
        $statusHtml .= ' <span class="badge badge-slow">' . h(lang('status.slow')) . '</span>';
    }
    $items[] = [
        'id' => (int) $site['id'],
        'status' => (string) $site['status'],
        'status_html' => $statusHtml,
        'response_time' => $site['response_time'] !== null ? (int) $site['response_time'] . ' ms' : '-',
        'last_checked' => format_datetime($site['last_checked']),
    ];
}

echo json_encode(['sites' => $items], JSON_UNESCAPED_UNICODE);
