<?php
/**
 * Application configuration (TEMPLATE).
 * Copy to config.php and fill in your values. Do NOT commit config.php with real secrets.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kuala_Lumpur');

define('APP_NAME', 'Website Monitoring System');
define('BASE_PATH', dirname(__DIR__));

$baseUrl = '/project/Website Monitoring System';
if (PHP_SAPI !== 'cli' && !empty($_SERVER['DOCUMENT_ROOT'])) {
    $doc = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    $root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    if ($doc && $root && strpos($root, $doc) === 0) {
        $baseUrl = substr($root, strlen($doc));
    }
}
define('BASE_URL', str_replace('\\', '/', $baseUrl));

/* Telegram — replace with your bot token and chat ID, or set in Admin → Settings */
define('TELEGRAM_BOT_TOKEN_DEFAULT', 'YOUR_TELEGRAM_BOT_TOKEN');
define('TELEGRAM_CHAT_ID_DEFAULT', 'YOUR_TELEGRAM_CHAT_ID');

define('HTTP_TIMEOUT_SECONDS', 8);
define('SLOW_THRESHOLD_MS_DEFAULT', 3000);
define('CRON_INTERVAL_SECONDS', 10);
define('UPTIME_DAYS', 90);

/* Forgot-password page reset code — change to a long random string */
define('RESET_CODE', 'CHANGE_THIS_RESET_CODE');

require_once BASE_PATH . '/includes/i18n.php';
i18n_boot();
