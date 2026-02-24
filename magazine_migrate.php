<?php
require_once 'includes/config.php';

$sql = "CREATE TABLE IF NOT EXISTS magazines (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    issue_month DATE NOT NULL COMMENT 'Store as first-day-of-month e.g. 2025-02-01',
    file_path   VARCHAR(255) NOT NULL,
    cover_image VARCHAR(255),
    pages       SMALLINT  DEFAULT 0,
    status      ENUM('published','draft') DEFAULT 'published',
    downloads   INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $pdo->exec($sql);
    echo '<p style="font-family:sans-serif;font-size:18px;color:green;">✅ <strong>magazines</strong> table created successfully. <a href=\"admin/magazines.php\">Go to Magazine Admin →</a></p>';
} catch (PDOException $e) {
    echo '<p style="color:red;">❌ Error: ' . $e->getMessage() . '</p>';
}
echo '<p style="font-size:13px;color:#999;font-family:sans-serif;">You can safely delete this file now.</p>';
