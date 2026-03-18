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
        // Example: "ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL"
        // Leave empty for now since version 1.0.1 has no db changes
    ],
    // 3 => [ ... ],
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
