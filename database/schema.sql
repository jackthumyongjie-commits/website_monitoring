CREATE DATABASE IF NOT EXISTS website_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE website_monitor;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS alerts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    website_id INT UNSIGNED NOT NULL,
    alert_type ENUM('DOWN','RECOVERY','SLOW') NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alerts_created (created_at),
    CONSTRAINT fk_alerts_website FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(80) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB;

-- Admin account: run install.php after import (do not commit password hashes in SQL)

INSERT INTO settings (setting_key, setting_value) VALUES
('telegram_bot_token', 'YOUR_TELEGRAM_BOT_TOKEN'),
('telegram_chat_id', 'YOUR_TELEGRAM_CHAT_ID'),
('slow_threshold_ms', '3000')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
