<?php
include 'includes/config.php';
$premium_category_id = get_setting('premium_category_id', '');
echo "Premium ID: " . $premium_category_id . "\n";
if (!empty($premium_category_id)) {
    try {
        $stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
                               FROM posts p 
                               JOIN post_categories pc ON p.id = pc.post_id 
                               JOIN categories c ON pc.category_id = c.id 
                               WHERE p.status = 'published' AND c.id = ? AND p.published_at <= NOW()
                               GROUP BY p.id ORDER BY p.published_at DESC LIMIT 4");
        $stmt->execute([$premium_category_id]);
        $premium_posts = $stmt->fetchAll();
        echo "Found " . count($premium_posts) . " posts.\n";
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "No premium category set.\n";
}
