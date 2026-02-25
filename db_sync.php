<?php
require_once 'includes/config.php';

echo "<h2>Database Synchronization Utility</h2>";
echo "<pre style='background: #f1f5f9; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; font-family: monospace;'>";

try {
    echo "[1/4] Checking Core Tables...\n";
    
    // Core structure map
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'editor') DEFAULT 'editor',
            profile_image VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'categories' => "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            icon VARCHAR(100) DEFAULT 'folder',
            color VARCHAR(20) DEFAULT '#6366f1',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'tags' => "CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            slug VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'posts' => "CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content LONGTEXT NOT NULL,
            excerpt TEXT,
            featured_image VARCHAR(255),
            video_url VARCHAR(255),
            external_link TEXT,
            external_type ENUM('none', 'url', 'whatsapp', 'call') DEFAULT 'none',
            external_label ENUM('none', 'Ad', 'Promoted', 'Sponsored') DEFAULT 'none',
            status ENUM('draft', 'published') DEFAULT 'draft',
            views INT DEFAULT 0,
            is_featured BOOLEAN DEFAULT FALSE,
            is_breaking BOOLEAN DEFAULT FALSE,
            meta_description VARCHAR(160),
            published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        'post_categories' => "CREATE TABLE IF NOT EXISTS post_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            category_id INT NOT NULL
        )",
        'post_tags' => "CREATE TABLE IF NOT EXISTS post_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            tag_id INT NOT NULL
        )",
        'ads' => "CREATE TABLE IF NOT EXISTS ads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            location VARCHAR(50) NOT NULL,
            type ENUM('image', 'code') NOT NULL,
            image_path VARCHAR(255),
            link_url TEXT,
            link_type ENUM('url', 'whatsapp', 'call') DEFAULT 'url',
            ad_code TEXT,
            start_date DATE,
            end_date DATE,
            status BOOLEAN DEFAULT TRUE,
            impressions INT DEFAULT 0,
            clicks INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'settings' => "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        'feedback' => "CREATE TABLE IF NOT EXISTS feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            email VARCHAR(100),
            subject VARCHAR(255),
            message TEXT,
            status ENUM('new', 'read', 'archived') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'epapers' => "CREATE TABLE IF NOT EXISTS epapers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            paper_date DATE NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            thumbnail VARCHAR(255),
            dimensions VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'magazines' => "CREATE TABLE IF NOT EXISTS magazines (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            issue_month DATE NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            cover_image VARCHAR(255),
            pages SMALLINT DEFAULT 0,
            status ENUM('published','draft') DEFAULT 'published',
            downloads INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'bookmarks' => "CREATE TABLE IF NOT EXISTS bookmarks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY user_post (user_id, post_id)
        )",
        'user_activity' => "CREATE TABLE IF NOT EXISTS user_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            action_type ENUM('view', 'bookmark', 'share') DEFAULT 'view',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
        echo "✔ Table '$name' verified.\n";
    }

    echo "\n[2/4] Patching Missing Columns...\n";
    
    // List of potential missing columns (for existing tables)
    $patches = [
        'posts' => [
            'external_link' => "TEXT AFTER video_url",
            'external_type' => "ENUM('none', 'url', 'whatsapp', 'call') DEFAULT 'none' AFTER external_link",
            'external_label' => "ENUM('none', 'Ad', 'Promoted', 'Sponsored') DEFAULT 'none' AFTER external_type"
        ]
    ];

    foreach ($patches as $table => $columns) {
        foreach ($columns as $column => $definition) {
            $check = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetch();
            if (!$check) {
                $pdo->exec("ALTER TABLE `$table` ADD `$column` $definition");
                echo "✔ Added column '$column' to '$table'.\n";
            }
        }
    }

    echo "\n[3/4] Initializing Advanced Settings...\n";
    $default_settings = [
        'site_name' => 'Panchayat Voice',
        'translation_enabled' => 'no',
        'tts_enabled' => 'yes',
        'breaking_news_enabled' => 'yes',
        'live_youtube_enabled' => '0',
        'theme_color' => '#ff3c00'
    ];

    foreach ($default_settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
    echo "✔ All core settings initialized.\n";

    echo "\n[4/4] Finalizing...\n";
    echo "✔ Database schema is fully synchronized with application features.\n";
    
    echo "\n<strong style='color: #16a34a;'>SYNC SUCCESSFUL!</strong>";
    echo "</pre>";

} catch (PDOException $e) {
    echo "\n<strong style='color: #dc2626;'>FATAL ERROR:</strong> " . $e->getMessage();
    echo "</pre>";
}
?>
