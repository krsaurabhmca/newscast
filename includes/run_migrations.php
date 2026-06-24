<?php
/**
 * DB Migration Script for NewsCast
 * Triggered automatically by the Auto-Update system.
 */

if (!isset($pdo)) {
    require_once __DIR__ . '/config.php';
}

// Ensure db_version exists
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'db_version'");
$current_db_version = $stmt->fetchColumn();

if ($current_db_version === false) {
    try {
        $pdo->exec("INSERT INTO settings (setting_key, setting_value) VALUES ('db_version', '1')");
        $current_db_version = 1;
    } catch (PDOException $e) {
        $current_db_version = 1;
    }
} else {
    $current_db_version = (int) $current_db_version;
}

// Define the schema migrations associated with each DB version
// Only new versions need an entry here. No need to redefine tables that already exist in v1.
$migrations = [
    2 => [
        // Version 1.0.1 db changes
    ],
    3 => [
        // Version 2.1.0 / 2.1.1 db changes
    ],
    4 => [
        "ALTER TABLE posts ADD COLUMN source_url VARCHAR(500) NULL DEFAULT NULL AFTER external_label",
        "CREATE TABLE IF NOT EXISTS wp_sources (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_name VARCHAR(255) NOT NULL,
            feed_url VARCHAR(500) NOT NULL,
            category_id INT NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            last_checked DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ],
    5 => [
        "ALTER TABLE categories ADD COLUMN show_on_homepage TINYINT(1) DEFAULT 0 AFTER status"
    ],
    6 => [
        "CREATE TABLE IF NOT EXISTS `ad_click_logs` (
          `id`          INT(11)      NOT NULL AUTO_INCREMENT,
          `ad_id`       INT(11)      DEFAULT NULL,
          `post_id`     INT(11)      DEFAULT NULL,
          `event_type`  ENUM('ad_click','sponsored_post_click') NOT NULL DEFAULT 'ad_click',
          `ad_name`     VARCHAR(255) DEFAULT NULL,
          `ad_location` VARCHAR(50)  DEFAULT NULL,
          `ip_address`  VARCHAR(45)  NOT NULL DEFAULT '',
          `user_agent`  TEXT         DEFAULT NULL,
          `referer_url` VARCHAR(500) DEFAULT NULL,
          `clicked_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_ad_id` (`ad_id`),
          KEY `idx_clicked_at` (`clicked_at`),
          KEY `idx_event_type` (`event_type`),
          KEY `idx_ip_address` (`ip_address`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    ],
    7 => [
        "ALTER TABLE categories ADD COLUMN parent_id INT DEFAULT NULL AFTER id",
        "ALTER TABLE categories ADD FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL",
        "ALTER TABLE categories ADD COLUMN custom_url VARCHAR(500) DEFAULT NULL AFTER slug",
        "ALTER TABLE posts ADD COLUMN likes_count INT DEFAULT 0",
        "ALTER TABLE posts ADD COLUMN dislikes_count INT DEFAULT 0",
        "CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            user_email VARCHAR(100) NOT NULL,
            comment_text TEXT NOT NULL,
            status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ]
];

$force_migrations = isset($force_migrations) ? $force_migrations : false;
$latest_version = $current_db_version;

foreach ($migrations as $version => $queries) {
    if ($force_migrations || $version > $current_db_version) {
        foreach ($queries as $sql) {
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                // If it fails (e.g., column already exists), we can just proceed quietly.
                error_log("DB Migration Error v$version: " . $e->getMessage());
            }
        }
        if ($version > $latest_version) {
            $latest_version = $version;
        }
    }
}

// Update the version in the database
if ($latest_version > $current_db_version) {
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'db_version'");
    $stmt->execute([$latest_version]);
}
