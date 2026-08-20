<?php
/**
 * MySQL connection (TEMPLATE).
 * Copy to database.php and fill in your database credentials.
 * Do NOT commit database.php with real passwords.
 */
$dbHost = 'localhost';
$dbName = 'your_database_name';
$dbUser = 'your_database_user';
$dbPass = 'your_database_password';
$dbCharset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die('Database connection failed. Copy config/database.example.php to database.php and import database/schema.sql. Error: ' . htmlspecialchars($e->getMessage()));
}

try {
    $col = $pdo->query("SHOW COLUMNS FROM websites LIKE 'last_issue'")->fetch();
    if (!$col) {
        $pdo->exec('ALTER TABLE websites ADD last_issue VARCHAR(255) NULL AFTER is_slow');
    }
} catch (PDOException $e) {
    // table may not exist yet during first install
}
