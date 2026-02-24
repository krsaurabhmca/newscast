<?php
include 'includes/public_header.php';

// Fetch Featured Post
$stmt = $pdo->query("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors, GROUP_CONCAT(c.slug) as cat_slugs 
                     FROM posts p 
                     JOIN post_categories pc ON p.id = pc.post_id 
                     JOIN categories c ON pc.category_id = c.id 
                     WHERE p.status = 'published' AND p.is_featured = 1 AND p.published_at <= NOW()
                     GROUP BY p.id ORDER BY p.published_at DESC LIMIT 1");
$featured = $stmt->fetch();

// If no featured post, get the latest published
if (!$featured) {
    $stmt = $pdo->query("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors, GROUP_CONCAT(c.slug) as cat_slugs 
                         FROM posts p 
                         JOIN post_categories pc ON p.id = pc.post_id 
                         JOIN categories c ON pc.category_id = c.id 
                         WHERE p.status = 'published' AND p.published_at <= NOW()
                         GROUP BY p.id ORDER BY p.published_at DESC LIMIT 1");
    $featured = $stmt->fetch();
}

// Fetch 4 sub-featured posts
$featured_id = $featured ? $featured['id'] : 0;
$stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
                        FROM posts p 
                        JOIN post_categories pc ON p.id = pc.post_id 
                        JOIN categories c ON pc.category_id = c.id 
                        WHERE p.status = 'published' AND p.id != ? AND p.published_at <= NOW()
                        GROUP BY p.id ORDER BY p.published_at DESC LIMIT 4");
$stmt->execute([$featured_id]);
$sub_featured = $stmt->fetchAll();

// Fetch Recent Posts for grid
$exclude_ids = array_merge([$featured_id], array_column($sub_featured, 'id'));
$placeholders = $exclude_ids ? str_repeat('?,', count($exclude_ids) - 1) . '?' : '0';
$stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
                        FROM posts p 
                        JOIN post_categories pc ON p.id = pc.post_id 
                        JOIN categories c ON pc.category_id = c.id 
                        WHERE p.status = 'published' AND p.id NOT IN ($placeholders) AND p.published_at <= NOW()
                        GROUP BY p.id ORDER BY p.published_at DESC LIMIT 9");
$stmt->execute($exclude_ids ?: []);
$recent_posts = $stmt->fetchAll();

// Trending Tags
$stmt = $pdo->query("SELECT name, slug, color, icon FROM categories WHERE status = 'active' LIMIT 6");
$tags = $stmt->fetchAll();
?>

<main class="content-container">
    <!-- Trending Bar -->
    <div class="trending-tags">
        <span class="trending-label">TRENDING</span>
        <?php foreach($tags as $tag): ?>
            <a href="category.php?slug=<?php echo $tag['slug']; ?>" class="tag-item" style="border-left: 3px solid <?php echo $tag['color']; ?>; display: flex; align-items: center; gap: 5px;">
                <i data-feather="<?php echo $tag['icon']; ?>" style="width: 12px; height: 12px; color: <?php echo $tag['color']; ?>;"></i>
                <?php echo $tag['name']; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Bhaskar Home Hero -->
    <?php if ($featured): ?>
    <section class="bhaskar-hero">
        <div class="main-feature">
            <?php 
                $post_url = ($featured['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $featured['id'] : BASE_URL . "article/" . $featured['slug'];
            ?>
            <a href="<?php echo $post_url; ?>" <?php echo ($featured['external_type'] != 'none') ? 'target="_blank"' : ''; ?>>
                <?php if($featured['external_label'] != 'none'): ?>
                    <span style="background: #000; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 800; margin-bottom: 10px; display: inline-block;"><?php echo strtoupper($featured['external_label']); ?></span>
                <?php endif; ?>
                <h2><?php echo $featured['title']; ?></h2>
                <div style="position: relative;">
                    <img src="<?php echo get_post_thumbnail($featured['featured_image']); ?>" alt="">
                    <?php if ($featured['video_url']): ?>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255, 60, 0, 0.85); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                            <i data-feather="play" style="width: 30px; height: 30px; fill: white;"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </a>
            <p style="color: #666; font-size: 16px;"><?php echo $featured['excerpt']; ?></p>
        </div>

        <div class="sub-features">
            <?php foreach ($sub_featured as $post): 
                $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
            ?>
            <a href="<?php echo $post_url; ?>" class="small-card" <?php echo ($post['external_type'] != 'none') ? 'target="_blank"' : ''; ?>>
                <div style="position: relative; flex-shrink: 0;">
                    <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>" alt="" style="width: 120px; height: 80px; object-fit: cover;">
                    <?php if ($post['video_url']): ?>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255, 60, 0, 0.85); width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                            <i data-feather="play" style="width: 12px; height: 12px; fill: white;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if($post['external_label'] != 'none'): ?>
                        <span style="color: #6366f1; font-size: 10px; font-weight: 800;"><?php echo strtoupper($post['external_label']); ?></span>
                    <?php endif; ?>
                    <h3><?php echo $post['title']; ?></h3>
                    <span style="color: #888; font-size: 12px;"><?php echo format_date($post['created_at']); ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- News Grid Selection -->
    <section>
        <div style="border-top: 2px solid #333; padding-top: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 22px; font-weight: 800; color: #ff3c00;">TOP STORIES</h3>
            <a href="#" style="font-size: 14px; font-weight: 700; color: #444;">VIEW ALL →</a>
        </div>
        
        <div class="news-grid">
            <?php foreach ($recent_posts as $post): 
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
                        <span style="color: #ff3c00; font-size: 10px; font-weight: 800; display: block; margin-top: 10px;"><?php echo strtoupper($post['external_label']); ?></span>
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
        </div>
    </section>
</main>

<?php include 'includes/public_footer.php'; ?>
