<?php
/**
 * Cron 循环进程（不用 Windows 计划任务）
 * 用法: php cron/run-cron.php
 * 保持这个窗口开着，就会按间隔自动检测并立刻发送 Telegram。
 * 按 Ctrl+C 停止。
 */
require_once dirname(__DIR__) . '/config/config.php';

$interval = CRON_INTERVAL_SECONDS;
$php = 'C:\\xampp\\php\\php.exe';
$monitor = __DIR__ . DIRECTORY_SEPARATOR . 'monitor.php';

echo "Website Monitoring Cron started\n";
echo "Interval: {$interval} seconds\n";
echo "Log: " . __DIR__ . DIRECTORY_SEPARATOR . "cron.log\n";
echo "Press Ctrl+C to stop.\n\n";

while (true) {
    passthru('"' . $php . '" "' . $monitor . '"');
    sleep($interval);
}
