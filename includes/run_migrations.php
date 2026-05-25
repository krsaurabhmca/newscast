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
];

$latest_version = $current_db_version;

foreach ($migrations as $version => $queries) {
    if ($version > $current_db_version) {
        foreach ($queries as $sql) {
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                // If it fails (e.g., column already exists), we can just proceed quietly.
                error_log("DB Migration Error v$version: " . $e->getMessage());
            }
        }
        $latest_version = $version;
    }
}

// Update the version in the database
if ($latest_version > $current_db_version) {
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'db_version'");
    $stmt->execute([$latest_version]);
}
