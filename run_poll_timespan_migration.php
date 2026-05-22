<?php
require_once __DIR__ . '/includes/config.php';

try {
    $pdo->exec("ALTER TABLE polls ADD COLUMN starts_at DATETIME NULL AFTER status");
    $pdo->exec("ALTER TABLE polls ADD COLUMN expires_at DATETIME NULL AFTER starts_at");
    echo "Migration successful: 'starts_at' and 'expires_at' added to polls.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Migration already applied (columns exist).\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
