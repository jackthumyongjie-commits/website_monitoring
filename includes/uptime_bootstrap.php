<?php
/**
 * Shared uptime list + detail body.
 * Set before include:
 *   $uptimeBase   e.g. BASE_URL . '/uptime/' or BASE_URL . '/admin/uptime.php'
 *   $uptimePublic bool
 */
require_once __DIR__ . '/uptime.php';

if (!function_exists('uptime_detail_url')) {
    function uptime_detail_url(string $base, int $id): string
    {
        if (substr($base, -1) === '/') {
            return $base . '?id=' . $id;
        }
        return $base . '?id=' . $id;
    }
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM websites WHERE id = ?');
    $stmt->execute([$id]);
    $site = $stmt->fetch();
    if (!$site) {
        if (!empty($uptimePublic)) {
            http_response_code(404);
            $pageTitle = lang('web.not_found');
            $pageSub = lang('up.public_sub');
            $uptimeDetail = true;
            $site = null;
        } else {
            flash('error', lang('web.not_found'));
            redirect('/admin/uptime.php');
        }
    } else {
    $s90 = uptime_period_stats($pdo, $id, uptime_hours());
    $ticks = uptime_daily_ticks($pdo, $id, uptime_days());
    $incidents = uptime_incidents($pdo, $id, uptime_days());
    $since = status_since($pdo, $id);
    $sinceLabel = $since ? format_duration(max(0, time() - strtotime($since))) : '—';

    $logStmt = $pdo->prepare(
        'SELECT * FROM logs WHERE website_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY created_at DESC LIMIT 100'
    );
    $logStmt->execute([$id, uptime_days()]);
    $checks = $logStmt->fetchAll();

    $maxRt = 1;
    foreach ($ticks as $t) {
        if (!empty($t['response_time'])) {
            $maxRt = max($maxRt, (int) $t['response_time']);
        }
    }

    $st = strtolower((string) $site['status']);
    $pageTitle = $site['name'];
    $pageSub = lang('up.detail_sub');
    $uptimeDetail = true;
    }
    return;
}

$sites = $pdo->query('SELECT * FROM websites ORDER BY name ASC')->fetchAll();
$pct90 = uptime_all_period_stats($pdo, uptime_hours());
$bars = uptime_ticks_map($pdo, uptime_days());

$pageTitle = lang('up.title');
$pageSub = !empty($uptimePublic) ? lang('up.public_sub') : lang('up.sub');
$uptimeDetail = false;
