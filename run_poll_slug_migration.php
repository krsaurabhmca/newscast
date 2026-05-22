<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

try {
    // Add slug column
    $pdo->exec("ALTER TABLE polls ADD COLUMN slug VARCHAR(255) NULL UNIQUE AFTER id");
    
    // Fetch all polls to assign unique slugs
    $polls = $pdo->query("SELECT id, question FROM polls WHERE slug IS NULL")->fetchAll();
    
    $stmt = $pdo->prepare("UPDATE polls SET slug = ? WHERE id = ?");
    
    foreach ($polls as $poll) {
        $base_slug = create_slug($poll['question']);
        if (empty($base_slug)) {
            $base_slug = 'poll';
        }
        $slug = $base_slug . '-' . substr(md5(uniqid()), 0, 6);
        $stmt->execute([$slug, $poll['id']]);
    }
    
    echo "Slug migration successful.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Slug column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
