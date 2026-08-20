<?php
/**
 * Monitoring engine — run from cron every 1 minute.
 * CLI: php cron/monitor.php
 * Browser (local test): http://localhost/.../cron/monitor.php
 */
require_once dirname(__DIR__) . '/includes/monitor.php';

$summary = run_monitoring($pdo, true);

$line = date('Y-m-d H:i:s') . ' checked=' . $summary['checked'] . ' total=' . $summary['total'] . ' telegram=' . ($summary['telegram'] ?? 0);
@file_put_contents(__DIR__ . '/cron.log', $line . PHP_EOL, FILE_APPEND);
@file_put_contents(dirname(__DIR__) . '/cron/.last-run', (string) time());

if (PHP_SAPI === 'cli') {
    echo 'Checked: ' . $summary['checked'] . ' | Skipped (interval not due): ' . $summary['skipped'] . ' | Total: ' . $summary['total'] . PHP_EOL;
    exit(0);
}

header('Content-Type: text/plain; charset=utf-8');
echo 'Checked: ' . $summary['checked'] . "\n";
echo 'Skipped: ' . $summary['skipped'] . "\n";
echo 'Total: ' . $summary['total'] . "\n";
