<?php
$_SERVER['HTTP_HOST'] = 'localhost';
include 'includes/config.php';

$slugs = [
    'revolutionize-newsroom-newscast-auto-share',
    'save-hours-social-media-automation',
    'power-multi-platform-news-distribution',
    'professional-branding-digital-publishers',
    'manual-dispatch-breaking-news-control',
    'social-signals-boost-news-seo',
    'seamless-setup-social-news-feed',
    'science-social-share-engagement',
    'digital-seal-empowering-independent-media',
    'stay-connected-real-time-diagnostics'
];

$category_id = 1; // Technology

foreach ($slugs as $index => $slug) {
    // 1. Get post ID and content
    $stmt = $pdo->prepare("SELECT id, content FROM posts WHERE slug = ?");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();

    if ($post) {
        $post_id = $post['id'];

        // 2. Update post properties
        $is_featured = ($index === 0) ? 1 : 0; // First one is featured
        $views = rand(5000, 15000);
        $excerpt = substr($post['content'], 0, 150) . '...';

        $update = $pdo->prepare("UPDATE posts SET is_featured = ?, views = ?, excerpt = ? WHERE id = ?");
        $update->execute([$is_featured, $views, $excerpt, $post_id]);

        // 3. Update category mapping
        // First delete existing mappings for this post to avoid duplicates
        $pdo->prepare("DELETE FROM post_categories WHERE post_id = ?")->execute([$post_id]);

        // Insert new mapping
        $pdo->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)")->execute([$post_id, $category_id]);

        echo "Updated post ID $post_id ($slug)\n";
    }
}
