<?php
require_once __DIR__ . '/includes/config.php';

try {
    $pdo->exec("ALTER TABLE timeline ADD COLUMN event_name VARCHAR(255) NULL AFTER id");
    $pdo->exec("ALTER TABLE timeline ADD COLUMN event_date DATE NULL AFTER event_name");
    echo "Migration successful: 'event_name' and 'event_date' added to timeline.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Migration already applied (columns exist).\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
