<?php
include 'includes/public_header.php';

$query = isset($_GET['q']) ? clean($_GET['q']) : '';

if ($query) {
    $stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
                           FROM posts p 
                           JOIN post_categories pc ON p.id = pc.post_id
                           JOIN categories c ON pc.category_id = c.id 
                           WHERE p.status = 'published' AND p.published_at <= NOW()
                           AND (p.title LIKE ? OR p.content LIKE ?) 
                           GROUP BY p.id
                           ORDER BY p.published_at DESC");
    $stmt->execute(["%$query%", "%$query%"]);
    $results = $stmt->fetchAll();
} else {
    $results = [];
}
?>

<main class="content-container">
    <div style="margin-top: 30px; margin-bottom: 40px;">
        <h1 style="font-size: 28px; font-weight: 800; color: #1e293b; margin-bottom: 10px;">
            Search Results for: "<?php echo $query; ?>"
        </h1>
        <p style="color: #64748b;"><?php echo count($results); ?> stories found</p>
    </div>

    <?php if (empty($results)): ?>
        <div style="text-align: center; padding: 100px 20px; background: white; border-radius: 12px; box-shadow: var(--shadow);">
            <i data-feather="search" style="width: 60px; height: 60px; color: #cbd5e1; margin-bottom: 20px;"></i>
            <h2 style="color: #64748b;">No results found.</h2>
            <p style="color: #94a3b8; margin-top: 10px;">Try different keywords or check your spelling.</p>
            <div style="margin-top: 30px; max-width: 400px; margin-left: auto; margin-right: auto;">
                <form action="<?php echo BASE_URL; ?>search.php" method="GET" style="display: flex; gap: 10px;">
                    <input type="text" name="q" class="form-control" placeholder="Search news..." style="padding: 12px;">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="news-grid">
            <?php foreach ($results as $post): 
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
        </div>
    <?php endif; ?>
</main>

<?php include 'includes/public_footer.php'; ?>
