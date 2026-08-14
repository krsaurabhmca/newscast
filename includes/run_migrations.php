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
    ],
    8 => [
        "ALTER TABLE users MODIFY COLUMN role ENUM('dev', 'admin', 'editor', 'reporter') DEFAULT 'editor'",
        "ALTER TABLE feedback ADD COLUMN category_id INT DEFAULT NULL AFTER phone",
        "ALTER TABLE feedback ADD COLUMN featured_image VARCHAR(255) DEFAULT NULL AFTER message",
        "ALTER TABLE feedback MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'",
        "ALTER TABLE feedback ADD CONSTRAINT fk_feedback_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL"
    ],
    9 => [
        "CREATE TABLE IF NOT EXISTS media_library (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255),
            file_size INT DEFAULT 0,
            width INT DEFAULT 0,
            height INT DEFAULT 0,
            uploaded_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ],
    10 => [
        "CREATE TABLE IF NOT EXISTS `post_views_logs` (
            `id`          INT(11)      NOT NULL AUTO_INCREMENT,
            `post_id`     INT(11)      NOT NULL,
            `ip_address`  VARCHAR(45)  NOT NULL DEFAULT '',
            `user_agent`  TEXT         DEFAULT NULL,
            `viewed_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_post_id` (`post_id`),
            KEY `idx_viewed_at` (`viewed_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
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

// Seed mock data for views and clicks if they are empty
try {
    $views_count = $pdo->query("SELECT COUNT(*) FROM post_views_logs")->fetchColumn();
    if ($views_count == 0) {
        $post_ids = $pdo->query("SELECT id FROM posts")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($post_ids)) {
            $stmt = $pdo->prepare("INSERT INTO post_views_logs (post_id, ip_address, user_agent, viewed_at) VALUES (?, ?, ?, ?)");
            for ($i = 0; $i < 90; $i++) {
                $date_str = date('Y-m-d H:i:s', strtotime("-$i days"));
                $num_views = rand(30, 120);
                for ($j = 0; $j < $num_views; $j++) {
                    $pid = $post_ids[array_rand($post_ids)];
                    $stmt->execute([$pid, '127.0.0.1', 'Mozilla/5.0 Mock', $date_str]);
                }
            }
            // Sync posts table views counter
            $pdo->exec("UPDATE posts p SET p.views = (SELECT COUNT(*) FROM post_views_logs pvl WHERE pvl.post_id = p.id)");
        }
    }
    
    $clicks_count = $pdo->query("SELECT COUNT(*) FROM ad_click_logs")->fetchColumn();
    if ($clicks_count == 0) {
        $stmt_click = $pdo->prepare("INSERT INTO ad_click_logs (ad_id, post_id, event_type, ad_name, ad_location, ip_address, user_agent, clicked_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        for ($i = 0; $i < 90; $i++) {
            $date_str = date('Y-m-d H:i:s', strtotime("-$i days"));
            $num_clicks = rand(5, 25);
            for ($j = 0; $j < $num_clicks; $j++) {
                $stmt_click->execute([1, null, 'ad_click', 'Sample Banner Ad', 'sidebar', '127.0.0.1', 'Mozilla/5.0 Mock', $date_str]);
            }
        }
    }
} catch (Exception $e) {
    error_log("Seeding views/clicks stats failed: " . $e->getMessage());
}

// Update the version in the database
if ($latest_version > $current_db_version) {
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'db_version'");
    $stmt->execute([$latest_version]);
}
