<?php

/**
 * Web cron — for shared hosting without cPanel cron access.
 * Runs monitoring at most once per CRON_INTERVAL_SECONDS.
 */
require_once __DIR__ . '/monitor.php';

function web_cron_interval(): int
{
    return max(10, (int) (defined('CRON_INTERVAL_SECONDS') ? CRON_INTERVAL_SECONDS : 60));
}

function web_cron_lock_path(): string
{
    return BASE_PATH . '/cron/.last-run';
}

function web_cron_log_path(): string
{
    return BASE_PATH . '/cron/cron.log';
}

function web_cron_should_run(): bool
{
    $lock = web_cron_lock_path();
    if (!is_file($lock)) {
        return true;
    }
    $last = (int) trim((string) @file_get_contents($lock));
    return (time() - $last) >= web_cron_interval();
}

function web_cron_tick(PDO $pdo, string $source = 'web'): array
{
    if (!web_cron_should_run()) {
        return ['ran' => false, 'reason' => 'wait'];
    }

    $lock = web_cron_lock_path();
    $fp = @fopen($lock, 'c+');
    if ($fp === false) {
        return ['ran' => false, 'reason' => 'lock'];
    }

    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        fclose($fp);
        return ['ran' => false, 'reason' => 'busy'];
    }

    $last = (int) trim(stream_get_contents($fp) ?: '0');
    if ($last > 0 && (time() - $last) < web_cron_interval()) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return ['ran' => false, 'reason' => 'wait'];
    }

    $summary = run_monitoring($pdo, true);
    $now = time();
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, (string) $now);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    $line = date('Y-m-d H:i:s', $now)
        . ' checked=' . $summary['checked']
        . ' total=' . $summary['total']
        . ' telegram=' . ($summary['telegram'] ?? 0)
        . ' source=' . $source;
    @file_put_contents(web_cron_log_path(), $line . PHP_EOL, FILE_APPEND);

    return ['ran' => true, 'summary' => $summary];
}
