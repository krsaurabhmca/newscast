<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_GET['slug'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$slug = $_GET['slug'];
$stmt = $pdo->prepare("SELECT * FROM tags WHERE slug = ?");
$stmt->execute([$slug]);
$tag = $stmt->fetch();

if (!$tag) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$page_title = "Posts tagged with #" . $tag['name'];
include 'includes/public_header.php';

// Fetch Posts with this tag
$stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(DISTINCT c.name) as cat_names, GROUP_CONCAT(DISTINCT c.color) as cat_colors 
                        FROM posts p 
                        JOIN post_tags pt ON p.id = pt.post_id
                        JOIN post_categories pc ON p.id = pc.post_id 
                        JOIN categories c ON pc.category_id = c.id
                        WHERE pt.tag_id = ? AND p.status = 'published' AND p.published_at <= NOW()
                        GROUP BY p.id
                        ORDER BY p.published_at DESC");
$stmt->execute([$tag['id']]);
$posts = $stmt->fetchAll();
?>

<main class="content-container">
    <header style="margin-bottom: 40px; border-bottom: 3px solid #6366f1; padding-bottom: 20px; display: flex; align-items: center; gap: 15px;">
        <div style="background: #6366f1; color: #fff; padding: 10px 20px; border-radius: 8px; font-weight: 900; font-size: 24px;">#</div>
        <div>
            <h1 style="font-size: 32px; font-weight: 900; color: #1e293b; margin: 0;"><?php echo strtoupper($tag['name']); ?></h1>
            <p style="color: #64748b; font-size: 14px; font-weight: 600; margin-top: 4px;">Explore stories tagged under this topic</p>
        </div>
    </header>

    <div class="news-grid">
        <?php if (empty($posts)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 100px 0;">
                <h3 style="color: #94a3b8;">No stories found with this tag yet.</h3>
                <a href="<?php echo BASE_URL; ?>" style="color: var(--primary); margin-top: 10px; display: inline-block; font-weight: 700;">Back to Home</a>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): 
                $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
            ?>
            <article class="news-card">
                <a href="<?php echo $post_url; ?>" <?php echo ($post['external_type'] != 'none') ? 'target="_blank"' : ''; ?>>
                    <div style="position: relative;">
                        <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>" alt="" style="aspect-ratio: 16/9; object-fit: cover; border-radius: 8px;">
                        <?php if ($post['video_url']): ?>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255, 60, 0, 0.85); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                <i data-feather="play" style="width: 20px; height: 20px; fill: white;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if($post['external_label'] != 'none'): ?>
                        <span style="color: #6366f1; font-size: 10px; font-weight: 800; display: block; margin-top: 10px;"><?php echo strtoupper($post['external_label']); ?></span>
                    <?php endif; ?>
                    <h4 style="font-size: 18px; line-height: 1.4; margin-top: 12px; font-weight: 700; color: #1e293b;"><?php echo $post['title']; ?></h4>
                </a>
                <div class="meta" style="margin-top: 12px; font-size: 12px; color: #94a3b8;">
                    <?php 
                        $names = explode(',', $post['cat_names']);
                        $colors = explode(',', $post['cat_colors']);
                    ?>
                    <span style="color: <?php echo $colors[0] ?? '#6366f1'; ?>; font-weight: 700;"><?php echo strtoupper($names[0] ?? 'NEWS'); ?></span> | 
                    <span><?php echo format_date($post['created_at']); ?></span>
                </div>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<style>
    .news-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }
    .news-card {
        background: #fff;
        transition: transform 0.3s ease;
    }
    .news-card:hover {
        transform: translateY(-5px);
    }
    .news-card img {
        width: 100%;
        height: auto;
        display: block;
    }
    @media (max-width: 1024px) {
        .news-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .news-grid { grid-template-columns: 1fr; }
    }
</style>

<?php include 'includes/public_footer.php'; ?>
