<?php
require_once 'includes/config.php';

echo "Starting Database Update...\n";

try {
    // 1. Create Bookmarks Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookmarks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_post (user_id, post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✔ Bookmarks table checked/created.\n";

    // 2. Create User Activity Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_activity (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        action_type ENUM('view', 'bookmark', 'share') DEFAULT 'view',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✔ Activity history table checked/created.\n";
    
    // 3. Create Timeline Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS timeline (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_time VARCHAR(20) NOT NULL,
        description TEXT NOT NULL,
        status_color VARCHAR(20) DEFAULT '#6366f1',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✔ Timeline table checked/created.\n";

    // 3. Initialize Settings
    $default_settings = [
        'translation_enabled' => 'no',
        'tts_enabled' => 'yes'
    ];

    foreach ($default_settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
    echo "✔ Default advanced settings initialized.\n";

    echo "\nDatabase update completed successfully!\n";

} catch (PDOException $e) {
    die("❌ Error during database update: " . $e->getMessage() . "\n");
}
?>
