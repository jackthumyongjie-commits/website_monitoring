<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/telegram.php';

/**
 * HTTP check. UP = reachable with HTTP 200-399. DOWN = timeout / connection error / HTTP 400+.
 */
function check_website(string $url): array
{
    $start = microtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => HTTP_TIMEOUT_SECONDS,
        CURLOPT_CONNECTTIMEOUT => HTTP_TIMEOUT_SECONDS,
        CURLOPT_USERAGENT => 'WebsiteMonitoringSystem/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_NOBODY => false,
        CURLOPT_HEADER => false,
    ]);
    curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $elapsedMs = (int) round((microtime(true) - $start) * 1000);

    $up = ($error === '' && $httpCode >= 200 && $httpCode < 400);
    return [
        'status' => $up ? 'UP' : 'DOWN',
        'response_time' => $elapsedMs,
        'http_code' => $httpCode,
        'error' => $error,
    ];
}

function website_due(array $site): bool
{
    if (empty($site['last_checked'])) {
        return true;
    }
    $interval = max(1, (int) $site['interval_minutes']);
    $next = strtotime($site['last_checked']) + ($interval * 60);
    return time() >= $next;
}

function remove_legacy_deployment_guard(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $guardId = (int) setting($pdo, 'deployment_website_id', '0');
    if ($guardId > 0) {
        $pdo->prepare('DELETE FROM websites WHERE id = ?')->execute([$guardId]);
    }

    // Delete any leftover folder-guard rows (ping.php only used by that feature)
    try {
        $stmt = $pdo->query('SELECT id, url FROM websites');
        foreach ($stmt->fetchAll() as $row) {
            $url = (string) ($row['url'] ?? '');
            if (strpos($url, 'ping.php') !== false) {
                $pdo->prepare('DELETE FROM websites WHERE id = ?')->execute([(int) $row['id']]);
            }
        }
    } catch (Throwable $e) {
        // ignore cleanup failures
    }

    set_setting($pdo, 'deployment_website_id', '0');
}

function process_check_result(PDO $pdo, array $site, array $result): int
{
    $now = date('Y-m-d H:i:s');
    $status = $result['status'];
    $rt = (int) $result['response_time'];
    $previous = strtoupper((string) ($site['status'] ?? 'UNKNOWN'));
    $threshold = (int) setting($pdo, 'slow_threshold_ms', (string) SLOW_THRESHOLD_MS_DEFAULT);

    $logStmt = $pdo->prepare(
        'INSERT INTO logs (website_id, status, response_time, http_code, note) VALUES (?, ?, ?, ?, ?)'
    );
    $note = $result['error'] !== '' ? $result['error'] : ('HTTP ' . $result['http_code']);
    $logStmt->execute([$site['id'], $status, $rt, $result['http_code'], $note]);

    $issue = describe_issue($result);
    $previousIssue = trim((string) ($site['last_issue'] ?? ''));
    if ($previousIssue === '') {
        $previousIssue = lang('tg.issue.default');
    }

    $openUrl = (string) $site['url'];
    $sent = 0;
    $websiteId = (int) $site['id'];
    $lastAlert = last_alert_type($pdo, $websiteId);

    $sendDown = ($status === 'DOWN' && ($previous !== 'DOWN' || $lastAlert !== 'DOWN'));
    $sendUp = ($status === 'UP' && ($previous === 'DOWN' || $lastAlert === 'DOWN'));

    if ($sendDown) {
        $msg = build_down_message($site, $result, $issue, $now);
        if (telegram_send($pdo, $msg, lang('tg.btn.down'), $openUrl)) {
            $sent++;
            save_alert($pdo, $websiteId, 'DOWN', strip_tags($msg));
        }
    } elseif ($sendUp) {
        $msg = build_up_message($site, $result, $previousIssue, $now);
        if (telegram_send($pdo, $msg, lang('tg.btn.up'), $openUrl)) {
            $sent++;
            save_alert($pdo, $websiteId, 'RECOVERY', strip_tags($msg));
        }
    }

    $update = $pdo->prepare(
        'UPDATE websites
         SET status = ?, last_checked = ?, response_time = ?, last_issue = ?, updated_at = NOW()
         WHERE id = ?'
    );
    $storedIssue = $status === 'DOWN' ? $issue : ($status === 'UP' ? null : $previousIssue);
    $update->execute([$status, $now, $rt, $storedIssue, $site['id']]);

    $wasSlow = (int) ($site['is_slow'] ?? 0) === 1;
    $isSlow = ($status === 'UP' && $rt > $threshold);

    if ($isSlow && !$wasSlow) {
        $msg = build_slow_message($site, $result, $threshold, $now);
        if (telegram_send($pdo, $msg, lang('tg.btn.slow'), $openUrl)) {
            $sent++;
        }
        save_alert($pdo, (int) $site['id'], 'SLOW', strip_tags($msg));
    }

    $slowStmt = $pdo->prepare('UPDATE websites SET is_slow = ? WHERE id = ?');
    $slowStmt->execute([$isSlow ? 1 : 0, $site['id']]);

    return $sent;
}

function run_monitoring(PDO $pdo, bool $forceAll = false): array
{
    remove_legacy_deployment_guard($pdo);

    $sites = $pdo->query('SELECT * FROM websites ORDER BY id ASC')->fetchAll();
    $checked = 0;
    $skipped = 0;
    $telegram = 0;

    foreach ($sites as $site) {
        if (!$forceAll && !website_due($site)) {
            $skipped++;
            continue;
        }
        $result = check_website((string) $site['url']);
        $telegram += process_check_result($pdo, $site, $result);
        $checked++;
    }

    return ['checked' => $checked, 'skipped' => $skipped, 'total' => count($sites), 'telegram' => $telegram];
}

function run_one_website(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM websites WHERE id = ?');
    $stmt->execute([$id]);
    $site = $stmt->fetch();
    if (!$site) {
        return null;
    }
    $result = check_website((string) $site['url']);
    process_check_result($pdo, $site, $result);
    $result['name'] = $site['name'];
    return $result;
}
