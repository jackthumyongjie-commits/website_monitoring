<?php
/**
 * One-click installer for XAMPP.
 * Uses credentials from config/database.php (create from database.example.php first).
 * Delete this file after installation.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS websites (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                url VARCHAR(500) NOT NULL,
                interval_minutes INT UNSIGNED NOT NULL DEFAULT 5,
                status ENUM('UNKNOWN','UP','DOWN') NOT NULL DEFAULT 'UNKNOWN',
                last_checked DATETIME NULL,
                response_time INT UNSIGNED NULL,
                is_slow TINYINT(1) NOT NULL DEFAULT 0,
                last_issue VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                website_id INT UNSIGNED NOT NULL,
                status ENUM('UP','DOWN') NOT NULL,
                response_time INT UNSIGNED NULL,
                http_code INT NULL,
                note VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_website_created (website_id, created_at),
                CONSTRAINT fk_logs_website FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS alerts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                website_id INT UNSIGNED NOT NULL,
                alert_type ENUM('DOWN','RECOVERY','SLOW') NOT NULL,
                message TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_alerts_created (created_at),
                CONSTRAINT fk_alerts_website FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(80) NOT NULL UNIQUE,
                setting_value TEXT NOT NULL
            ) ENGINE=InnoDB
        ");

        $defaultPassword = 'admin123';
        $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
        $stmt->execute(['admin']);
        if (!$stmt->fetch()) {
            $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')->execute(['admin', $hash]);
        }

        $defaults = [
            'telegram_bot_token' => 'YOUR_TELEGRAM_BOT_TOKEN',
            'telegram_chat_id' => 'YOUR_TELEGRAM_CHAT_ID',
            'slow_threshold_ms' => '3000',
        ];
        $ins = $pdo->prepare('INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)');
        foreach ($defaults as $k => $v) {
            $ins->execute([$k, $v]);
        }

        $message = lang('install.ok');
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
$pageHeadTitle = lang('install.title');
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="auth-page">
    <section class="auth-hero">
        <div class="tag"><?php echo h(lang('login.tag')); ?></div>
        <h1><?php echo h(lang('install.title')); ?></h1>
        <p><?php echo h(lang('install.hint')); ?></p>
    </section>
    <div class="auth-form-wrap">
        <div class="login-box">
            <div class="auth-lang"><?php echo lang_switcher(); ?></div>
            <h2><?php echo h(lang('install.title')); ?></h2>
            <p class="muted"><?php echo h(lang('install.hint')); ?></p>
            <?php if ($message): ?><div class="alert alert-success"><?php echo h($message); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?php echo h($error); ?></div><?php endif; ?>
            <form method="post">
                <button type="submit" style="width:100%;justify-content:center;"><?php echo h(lang('install.btn')); ?></button>
            </form>
            <p><a href="index.php"><?php echo h(lang('install.login')); ?></a></p>
        </div>
    </div>
</div>
</body>
</html>
