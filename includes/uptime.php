<?php

function uptime_days(): int
{
    return defined('UPTIME_DAYS') ? (int) UPTIME_DAYS : 90;
}

function uptime_hours(): int
{
    return uptime_days() * 24;
}

function uptime_period_stats(PDO $pdo, int $websiteId, int $hours): array
{
    $stmt = $pdo->prepare(
        'SELECT
            SUM(status = "UP") AS up_n,
            SUM(status = "DOWN") AS down_n,
            COUNT(*) AS total_n,
            AVG(CASE WHEN status = "UP" THEN response_time END) AS avg_rt
         FROM logs
         WHERE website_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)'
    );
    $stmt->execute([$websiteId, $hours]);
    $row = $stmt->fetch() ?: [];
    $total = (int) ($row['total_n'] ?? 0);
    $up = (int) ($row['up_n'] ?? 0);
    return [
        'total' => $total,
        'up' => $up,
        'down' => (int) ($row['down_n'] ?? 0),
        'pct' => $total > 0 ? round(($up / $total) * 100, 2) : null,
        'avg_rt' => $row['avg_rt'] !== null ? (int) round((float) $row['avg_rt']) : null,
    ];
}

function uptime_all_period_stats(PDO $pdo, int $hours): array
{
    $stmt = $pdo->prepare(
        'SELECT website_id,
            SUM(status = "UP") AS up_n,
            COUNT(*) AS total_n
         FROM logs
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
         GROUP BY website_id'
    );
    $stmt->execute([$hours]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $total = (int) $row['total_n'];
        $out[(int) $row['website_id']] = $total > 0
            ? round(((int) $row['up_n'] / $total) * 100, 2)
            : null;
    }
    return $out;
}

function uptime_ticks(PDO $pdo, int $websiteId, ?int $limit = null): array
{
    if ($limit === null) {
        return uptime_daily_ticks($pdo, $websiteId, uptime_days());
    }
    $stmt = $pdo->prepare(
        'SELECT status, response_time, created_at
         FROM logs
         WHERE website_id = ?
         ORDER BY created_at DESC
         LIMIT ' . (int) $limit
    );
    $stmt->execute([$websiteId]);
    return array_reverse($stmt->fetchAll());
}

function uptime_daily_ticks(PDO $pdo, int $websiteId, int $days): array
{
    $days = max(1, $days);
    $stmt = $pdo->prepare(
        'SELECT DATE(created_at) AS day,
                SUM(status = "UP") AS up_n,
                COUNT(*) AS total_n,
                AVG(CASE WHEN status = "UP" THEN response_time END) AS avg_rt
         FROM logs
         WHERE website_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY DATE(created_at)
         ORDER BY day ASC'
    );
    $stmt->execute([$websiteId, $days - 1]);
    $byDay = [];
    foreach ($stmt->fetchAll() as $row) {
        $byDay[(string) $row['day']] = $row;
    }

    $ticks = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime('-' . $i . ' days'));
        if (!isset($byDay[$day])) {
            $ticks[] = [
                'status' => 'UNKNOWN',
                'response_time' => null,
                'created_at' => $day . ' 00:00:00',
            ];
            continue;
        }
        $row = $byDay[$day];
        $total = (int) $row['total_n'];
        $up = (int) $row['up_n'];
        $ticks[] = [
            'status' => ($total > 0 && $up === $total) ? 'UP' : 'DOWN',
            'response_time' => $row['avg_rt'] !== null ? (int) round((float) $row['avg_rt']) : null,
            'created_at' => $day . ' 12:00:00',
            'day_label' => $day,
        ];
    }
    return $ticks;
}

function uptime_ticks_map(PDO $pdo, ?int $days = null): array
{
    $days = $days ?? uptime_days();
    $sites = $pdo->query('SELECT id FROM websites')->fetchAll(PDO::FETCH_COLUMN);
    $map = [];
    foreach ($sites as $siteId) {
        $map[(int) $siteId] = uptime_daily_ticks($pdo, (int) $siteId, $days);
    }
    return $map;
}

function uptime_incidents(PDO $pdo, int $websiteId, ?int $days = null): array
{
    $days = $days ?? uptime_days();
    $stmt = $pdo->prepare(
        'SELECT status, created_at
         FROM logs
         WHERE website_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
         ORDER BY created_at ASC'
    );
    $stmt->execute([$websiteId, $days]);
    $rows = $stmt->fetchAll();
    $incidents = [];
    $open = null;
    foreach ($rows as $row) {
        if ($row['status'] === 'DOWN' && $open === null) {
            $open = $row['created_at'];
        } elseif ($row['status'] === 'UP' && $open !== null) {
            $incidents[] = [
                'started' => $open,
                'ended' => $row['created_at'],
                'ongoing' => false,
                'seconds' => max(0, strtotime($row['created_at']) - strtotime($open)),
            ];
            $open = null;
        }
    }
    if ($open !== null) {
        $incidents[] = [
            'started' => $open,
            'ended' => null,
            'ongoing' => true,
            'seconds' => max(0, time() - strtotime($open)),
        ];
    }
    return array_reverse($incidents);
}

function format_duration(int $seconds): string
{
    if ($seconds < 60) {
        return lang('up.dur_s', ['n' => (string) $seconds]);
    }
    $m = intdiv($seconds, 60);
    if ($m < 60) {
        return lang('up.dur_m', ['n' => (string) $m]);
    }
    $h = intdiv($m, 60);
    $rm = $m % 60;
    return lang('up.dur_hm', ['h' => (string) $h, 'm' => (string) $rm]);
}

function render_uptime_bar(array $ticks): string
{
    if (!$ticks) {
        return '<div class="uptime-bar empty-bar" title="-"><span class="uptime-tick none"></span></div>';
    }
    $html = '<div class="uptime-bar" title="' . h(lang('up.bar_hint')) . '">';
    foreach ($ticks as $tick) {
        $status = strtoupper((string) ($tick['status'] ?? 'UNKNOWN'));
        if ($status === 'UNKNOWN') {
            $cls = 'none';
        } else {
            $cls = $status === 'DOWN' ? 'down' : 'up';
        }
        $label = $tick['day_label'] ?? ($tick['created_at'] ?? '');
        $title = h($label . ' · ' . ($tick['status'] ?? ''));
        $html .= '<span class="uptime-tick ' . $cls . '" title="' . $title . '"></span>';
    }
    return $html . '</div>';
}

function format_uptime_pct(?float $pct): string
{
    if ($pct === null) {
        return '—';
    }
    return number_format($pct, 2) . '%';
}

function status_since(PDO $pdo, int $websiteId): ?string
{
    $stmt = $pdo->prepare(
        'SELECT status, created_at FROM logs WHERE website_id = ? ORDER BY created_at DESC LIMIT 400'
    );
    $stmt->execute([$websiteId]);
    $rows = $stmt->fetchAll();
    if (!$rows) {
        return null;
    }
    $current = $rows[0]['status'];
    $since = $rows[0]['created_at'];
    foreach ($rows as $row) {
        if ($row['status'] !== $current) {
            break;
        }
        $since = $row['created_at'];
    }
    return $since;
}
