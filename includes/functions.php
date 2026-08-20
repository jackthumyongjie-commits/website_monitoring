<?php

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $message = null)
{
    if ($message === null) {
        if (!isset($_SESSION['flash'][$key])) {
            return null;
        }
        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $value;
    }
    $_SESSION['flash'][$key] = $message;
}

function setting(PDO $pdo, string $key, ?string $default = null): string
{
    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if ($row && $row['setting_value'] !== '') {
        $value = (string) $row['setting_value'];
        if (in_array($value, ['YOUR_TELEGRAM_BOT_TOKEN', 'YOUR_TELEGRAM_CHAT_ID'], true)) {
            return (string) $default;
        }
        return $value;
    }
    return (string) $default;
}

function set_setting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

function format_datetime(?string $dt): string
{
    if (!$dt) {
        return '-';
    }
    return date('Y-m-d H:i:s', strtotime($dt));
}

function status_badge(string $status): string
{
    $status = strtoupper($status);
    if ($status === 'UP') {
        return '<span class="badge badge-up">' . h(lang('status.up')) . '</span>';
    }
    if ($status === 'DOWN') {
        return '<span class="badge badge-down">' . h(lang('status.down')) . '</span>';
    }
    return '<span class="badge badge-unknown">' . h(lang('status.unknown')) . '</span>';
}

function is_valid_url(string $url): bool
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $scheme = parse_url($url, PHP_URL_SCHEME);
    return in_array($scheme, ['http', 'https'], true);
}

function normalize_http_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    $parts = parse_url($url);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
        return $url;
    }
    $path = isset($parts['path']) ? $parts['path'] : '';
    $segments = array_values(array_filter(explode('/', $path), static fn($s) => $s !== ''));
    $encodedPath = implode('/', array_map('rawurlencode', $segments));
    $result = $parts['scheme'] . '://' . $parts['host'];
    if ($encodedPath !== '') {
        $result .= '/' . $encodedPath;
    }
    if (isset($parts['query'])) {
        $result .= '?' . $parts['query'];
    }
    return $result;
}

function nav_active(string $file): string
{
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($file === 'websites.php' && in_array($script, ['websites.php', 'website-form.php'], true)) {
        return 'active';
    }
    return $script === $file ? 'active' : '';
}

function telegram_configured(string $token, string $chatId): bool
{
    return $token !== '' && $token !== 'YOUR_TELEGRAM_BOT_TOKEN'
        && $chatId !== '' && $chatId !== 'YOUR_TELEGRAM_CHAT_ID';
}

function last_alert_type(PDO $pdo, int $websiteId): ?string
{
    $stmt = $pdo->prepare('SELECT alert_type FROM alerts WHERE website_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$websiteId]);
    $row = $stmt->fetch();
    return $row ? (string) $row['alert_type'] : null;
}

function cron_last_run(): ?int
{
    $log = BASE_PATH . '/cron/cron.log';
    if (!is_file($log)) {
        return null;
    }
    $lines = @file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return null;
    }
    $last = (string) end($lines);
    if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $last, $m)) {
        return strtotime($m[1]) ?: null;
    }
    return null;
}

function cron_is_running(): bool
{
    $last = cron_last_run();
    $grace = defined('CRON_INTERVAL_SECONDS') ? (CRON_INTERVAL_SECONDS * 3) : 90;
    return $last !== null && (time() - $last) <= $grace;
}
