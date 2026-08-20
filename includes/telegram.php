<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/functions.php';

function telegram_send(PDO $pdo, string $text, ?string $buttonText = null, ?string $buttonUrl = null): bool
{
    $token = setting($pdo, 'telegram_bot_token', TELEGRAM_BOT_TOKEN_DEFAULT);
    $chatId = setting($pdo, 'telegram_chat_id', TELEGRAM_CHAT_ID_DEFAULT);

    if (!telegram_configured($token, $chatId)) {
        return false;
    }

    $payload = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    ];

    if ($buttonText && $buttonUrl) {
        $buttonUrl = normalize_http_url($buttonUrl);
        if (is_valid_url($buttonUrl)) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => [[
                    ['text' => $buttonText, 'url' => $buttonUrl],
                ]],
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $error) {
        @file_put_contents(
            BASE_PATH . '/cron/cron.log',
            date('Y-m-d H:i:s') . ' telegram_error=' . $error . PHP_EOL,
            FILE_APPEND
        );
        return false;
    }

    $data = json_decode($response, true);
    $ok = is_array($data) && !empty($data['ok']);
    if (!$ok) {
        $desc = is_array($data) ? (string) ($data['description'] ?? 'unknown') : 'bad_json';
        @file_put_contents(
            BASE_PATH . '/cron/cron.log',
            date('Y-m-d H:i:s') . ' telegram_api_error=' . $desc . PHP_EOL,
            FILE_APPEND
        );
    }
    return $ok;
}

function save_alert(PDO $pdo, int $websiteId, string $type, string $message): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO alerts (website_id, alert_type, message) VALUES (?, ?, ?)'
    );
    $stmt->execute([$websiteId, $type, $message]);
}

function describe_issue(array $result): string
{
    $code = (int) ($result['http_code'] ?? 0);
    $error = strtolower((string) ($result['error'] ?? ''));

    if ($error !== '') {
        if (strpos($error, 'timed out') !== false || strpos($error, 'timeout') !== false) {
            return lang('tg.issue.timeout');
        }
        if (strpos($error, 'could not resolve') !== false || strpos($error, 'resolve host') !== false) {
            return lang('tg.issue.resolve');
        }
        if (strpos($error, 'refused') !== false) {
            return lang('tg.issue.refused');
        }
        if (strpos($error, 'ssl') !== false || strpos($error, 'certificate') !== false) {
            return lang('tg.issue.ssl');
        }
        return lang('tg.issue.generic', ['error' => (string) ($result['error'] ?? '')]);
    }

    if ($code === 401 || $code === 403) {
        return lang('tg.issue.http403', ['code' => (string) $code]);
    }
    if ($code === 404) {
        return lang('tg.issue.http404');
    }
    if ($code === 429) {
        return lang('tg.issue.http429');
    }
    if ($code >= 500 && $code <= 599) {
        return lang('tg.issue.http5xx', ['code' => (string) $code]);
    }
    if ($code >= 400) {
        return lang('tg.issue.http4xx', ['code' => (string) $code]);
    }
    if ($code === 0) {
        return lang('tg.issue.no_response');
    }
    return lang('tg.issue.failed', ['code' => (string) $code]);
}

function build_down_message(array $site, array $result, string $issue, string $eventTime): string
{
    $name = htmlspecialchars((string) $site['name'], ENT_QUOTES, 'UTF-8');
    $url = htmlspecialchars((string) $site['url'], ENT_QUOTES, 'UTF-8');
    $issueSafe = htmlspecialchars($issue, ENT_QUOTES, 'UTF-8');
    $rt = (int) ($result['response_time'] ?? 0);

    return '🔴 <b>' . lang('tg.down.title') . '</b>' . "\n"
        . lang('tg.down.issue') . ': ' . $issueSafe . "\n"
        . lang('tg.down.site') . ': ' . $name . "\n"
        . lang('tg.down.url') . ': ' . $url . "\n"
        . lang('tg.down.status') . ': DOWN' . "\n"
        . lang('tg.down.time_ms') . ': ' . $rt . ' ms' . "\n"
        . lang('tg.down.at') . ': ' . $eventTime . "\n"
        . lang('tg.down.footer');
}

function build_up_message(array $site, array $result, string $resolved, string $eventTime): string
{
    $name = htmlspecialchars((string) $site['name'], ENT_QUOTES, 'UTF-8');
    $url = htmlspecialchars((string) $site['url'], ENT_QUOTES, 'UTF-8');
    $resolvedSafe = htmlspecialchars($resolved, ENT_QUOTES, 'UTF-8');
    $rt = (int) ($result['response_time'] ?? 0);
    $code = (int) ($result['http_code'] ?? 0);

    return '🩵 <b>' . lang('tg.up.title') . '</b>' . "\n"
        . lang('tg.up.resolved') . ': ' . $resolvedSafe . "\n"
        . lang('tg.down.site') . ': ' . $name . "\n"
        . lang('tg.down.url') . ': ' . $url . "\n"
        . lang('tg.down.status') . ': UP (HTTP ' . $code . ')' . "\n"
        . lang('tg.down.time_ms') . ': ' . $rt . ' ms' . "\n"
        . lang('tg.down.at') . ': ' . $eventTime . "\n"
        . lang('tg.up.footer');
}

function build_slow_message(array $site, array $result, int $threshold, string $eventTime): string
{
    $name = htmlspecialchars((string) $site['name'], ENT_QUOTES, 'UTF-8');
    $url = htmlspecialchars((string) $site['url'], ENT_QUOTES, 'UTF-8');
    $rt = (int) ($result['response_time'] ?? 0);

    return '🟡 <b>' . lang('tg.slow.title') . '</b>' . "\n"
        . lang('tg.slow.issue', ['rt' => (string) $rt, 'threshold' => (string) $threshold]) . "\n"
        . lang('tg.down.site') . ': ' . $name . "\n"
        . lang('tg.down.url') . ': ' . $url . "\n"
        . lang('tg.down.at') . ': ' . $eventTime . "\n"
        . lang('tg.slow.footer');
}
