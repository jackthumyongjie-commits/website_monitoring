<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/functions.php';

function current_admin(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function require_admin(): void
{
    if (!current_admin()) {
        flash('error', lang('login.required'));
        redirect('/index.php?next=' . urlencode(admin_current_path()));
    }
}

function admin_current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/admin/index.php';
    $base = BASE_URL;
    if ($base !== '' && strpos($uri, $base) === 0) {
        $uri = substr($uri, strlen($base)) ?: '/';
    }
    if ($uri === '' || $uri[0] !== '/') {
        $uri = '/' . ltrim($uri, '/');
    }
    if (strpos($uri, '/admin/') !== 0) {
        return '/admin/index.php';
    }
    return $uri;
}

function safe_admin_next(?string $next): string
{
    $default = '/admin/index.php';
    if ($next === null || $next === '') {
        return $default;
    }
    $next = urldecode($next);
    if ($next[0] !== '/') {
        $next = '/' . ltrim($next, '/');
    }
    if (strpos($next, '/admin/') !== 0) {
        return $default;
    }
    return $next;
}

function login_admin(PDO $pdo, string $username, string $password): bool
{
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return false;
    }
    $_SESSION['admin'] = [
        'id' => $admin['id'],
        'username' => $admin['username'],
    ];
    return true;
}

function logout_admin(): void
{
    unset($_SESSION['admin']);
    session_regenerate_id(true);
}
