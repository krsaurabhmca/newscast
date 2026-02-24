<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_GET['slug'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$slug = $_GET['slug'];
$stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$page_title = $category['name'];
include 'includes/public_header.php';

// Fetch Posts in this category using the pivot table
$stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
                        FROM posts p 
                        JOIN post_categories pc ON p.id = pc.post_id 
                        JOIN categories c ON pc.category_id = c.id
                        WHERE pc.category_id = ? AND p.status = 'published' AND p.published_at <= NOW()
                        GROUP BY p.id
                        ORDER BY p.published_at DESC");
$stmt->execute([$category['id']]);
$posts = $stmt->fetchAll();
?>

<main class="content-container">
    <header style="margin-bottom: 40px; border-bottom: 3px solid <?php echo $category['color']; ?>; padding-bottom: 20px;">
        <h1 style="font-size: 36px; font-weight: 900; color: <?php echo $category['color']; ?>;"><?php echo strtoupper($category['name']); ?></h1>
        <?php if ($category['description']): ?>
            <p style="margin-top: 10px; color: #64748b; font-size: 16px; font-weight: 500;"><?php echo $category['description']; ?></p>
        <?php endif; ?>
    </header>

    <div class="news-grid">
        <?php if (empty($posts)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 100px 0;">
                <h3 style="color: #94a3b8;">No stories found in this category yet.</h3>
                <a href="index.php" style="color: var(--primary); margin-top: 10px; display: inline-block; font-weight: 700;">Back to Home</a>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): 
                $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
            ?>
            <article class="news-card">
                <a href="<?php echo $post_url; ?>" <?php echo ($post['external_type'] != 'none') ? 'target="_blank"' : ''; ?>>
                    <div style="position: relative;">
                        <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>" alt="">
                        <?php if ($post['video_url']): ?>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255, 60, 0, 0.85); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                <i data-feather="play" style="width: 20px; height: 20px; fill: white;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if($post['external_label'] != 'none'): ?>
                        <span style="color: #6366f1; font-size: 10px; font-weight: 800; display: block; margin-top: 10px;"><?php echo strtoupper($post['external_label']); ?></span>
                    <?php endif; ?>
                    <h4><?php echo $post['title']; ?></h4>
                </a>
                <div class="meta">
                    <?php 
                        $names = explode(',', $post['cat_names']);
                        $colors = explode(',', $post['cat_colors']);
                    ?>
                    <span style="color: <?php echo $colors[0]; ?>; font-weight: 700;"><?php echo $names[0]; ?></span> | 
                    <span><?php echo format_date($post['created_at']); ?></span>
                </div>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/public_footer.php'; ?>
