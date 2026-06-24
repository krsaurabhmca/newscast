<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_GET['slug'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$slug = $_GET['slug'];
$current_slug = $slug; // used by public_header for active state

// Pagination
$per_page = 12;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND status = 'active'");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// Sub-categories of this category
$sub_cats_stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? AND status = 'active' ORDER BY name ASC");
$sub_cats_stmt->execute([$category['id']]);
$sub_categories = $sub_cats_stmt->fetchAll();

// Total posts for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(DISTINCT p.id) FROM posts p 
    JOIN post_categories pc ON p.id = pc.post_id 
    WHERE pc.category_id = ? AND p.status = 'published' AND p.published_at <= NOW()");
$count_stmt->execute([$category['id']]);
$total_posts = (int)$count_stmt->fetchColumn();
$total_pages = ceil($total_posts / $per_page);

// Fetch posts for this page
$stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ',') as cat_names, 
                               GROUP_CONCAT(DISTINCT c.color ORDER BY c.name SEPARATOR ',') as cat_colors
                        FROM posts p 
                        JOIN post_categories pc ON p.id = pc.post_id 
                        JOIN categories c ON pc.category_id = c.id AND c.status = 'active'
                        WHERE pc.category_id = ? AND p.status = 'published' AND p.published_at <= NOW()
                        GROUP BY p.id
                        ORDER BY p.published_at DESC
                        LIMIT ? OFFSET ?");
$stmt->execute([$category['id'], $per_page, $offset]);
$posts = $stmt->fetchAll();

// Breaking news for this category
$breaking_stmt = $pdo->prepare("SELECT p.id, p.title, p.slug, p.external_type, p.published_at FROM posts p
    JOIN post_categories pc ON p.id = pc.post_id
    WHERE pc.category_id = ? AND p.status = 'published' AND p.published_at <= NOW()
    ORDER BY p.published_at DESC LIMIT 6");
$breaking_stmt->execute([$category['id']]);
$breaking_posts = $breaking_stmt->fetchAll();

// Popular posts in this category
$popular_stmt = $pdo->prepare("SELECT p.* FROM posts p
    JOIN post_categories pc ON p.id = pc.post_id
    WHERE pc.category_id = ? AND p.status = 'published' AND p.published_at <= NOW()
    ORDER BY p.views DESC LIMIT 5");
$popular_stmt->execute([$category['id']]);
$popular_posts = $popular_stmt->fetchAll();

$page_title = $category['name'];
$meta_description = $category['description'] ?: "Read the latest news and stories about " . $category['name'] . " on " . SITE_NAME . ".";
include 'includes/public_header.php';

// Lead post (first post for hero card)
$lead_post = !empty($posts) ? $posts[0] : null;
$grid_posts = array_slice($posts, 1);
?>

<!-- ══════════════════════════════════════════════
     CATEGORY PAGE: Breaking Banner + Full Layout
     ══════════════════════════════════════════════ -->

<?php if (!empty($breaking_posts)): ?>
<!-- Breaking News Ticker Bar -->
<div style="background: #0f172a; border-bottom: 2px solid <?php echo $category['color']; ?>; overflow: hidden;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; height: 44px; padding: 0 20px; gap: 0;">
        <span style="background: <?php echo $category['color']; ?>; color: #fff; padding: 0 20px; height: 100%; display: flex; align-items: center; font-weight: 900; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; flex-shrink: 0; white-space: nowrap;">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 6px;"><path d="m13 2-2 2.5h3L12 7"/><path d="M10 14v-3"/><path d="M14 14v-3"/><path d="M11 19H6.5a3.5 3.5 0 1 1 0-7h11a3.5 3.5 0 1 1 0 7H17"/></svg>
            Breaking
        </span>
        <div style="flex: 1; overflow: hidden; position: relative; mask-image: linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%);">
            <div class="cat-ticker" style="display: inline-flex; white-space: nowrap; animation: catTicker 30s linear infinite; align-items: center; height: 44px; gap: 0;">
                <?php foreach ($breaking_posts as $bn):
                    $bn_url = ($bn['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $bn['id'] : BASE_URL . "article/" . $bn['slug'];
                ?>
                <a href="<?php echo $bn_url; ?>" style="color: #cbd5e1; text-decoration: none; font-size: 13px; font-weight: 600; margin-right: 50px; display: inline-flex; align-items: center; gap: 8px; transition: color 0.2s;" onmouseover="this.style.color='<?php echo $category['color']; ?>'" onmouseout="this.style.color='#cbd5e1'">
                    <span style="width: 6px; height: 6px; background: <?php echo $category['color']; ?>; border-radius: 50%; flex-shrink: 0;"></span>
                    <?php echo htmlspecialchars($bn['title']); ?>
                </a>
                <?php endforeach; ?>
                <!-- Duplicate for seamless loop -->
                <?php foreach ($breaking_posts as $bn):
                    $bn_url = ($bn['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $bn['id'] : BASE_URL . "article/" . $bn['slug'];
                ?>
                <a href="<?php echo $bn_url; ?>" style="color: #cbd5e1; text-decoration: none; font-size: 13px; font-weight: 600; margin-right: 50px; display: inline-flex; align-items: center; gap: 8px; transition: color 0.2s;" onmouseover="this.style.color='<?php echo $category['color']; ?>'" onmouseout="this.style.color='#cbd5e1'">
                    <span style="width: 6px; height: 6px; background: <?php echo $category['color']; ?>; border-radius: 50%; flex-shrink: 0;"></span>
                    <?php echo htmlspecialchars($bn['title']); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <span style="font-size: 11px; color: #475569; font-weight: 700; padding-left: 15px; border-left: 1px solid #1e293b; margin-left: 10px; white-space: nowrap; display: flex; align-items: center; gap: 5px; flex-shrink: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <?php echo date('d M Y'); ?>
        </span>
    </div>
</div>
<?php endif; ?>

<main class="content-container" style="max-width: 1200px; padding-top: 0;">

    <!-- ── Category Hero Banner ── -->
    <div style="background: linear-gradient(135deg, <?php echo $category['color']; ?>18 0%, <?php echo $category['color']; ?>06 100%); border-radius: 0 0 20px 20px; padding: 35px 30px 30px; margin-bottom: 30px; border-bottom: 3px solid <?php echo $category['color']; ?>; position: relative; overflow: hidden;">
        <!-- Background pattern -->
        <div style="position: absolute; inset: 0; background-image: radial-gradient(<?php echo $category['color']; ?>20 1.5px, transparent 1.5px); background-size: 22px 22px; pointer-events: none;"></div>
        <div style="position: relative; z-index: 1; display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <a href="<?php echo BASE_URL; ?>" style="font-size: 12px; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 4px; text-decoration: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Home
                    </a>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    <span style="font-size: 12px; color: <?php echo $category['color']; ?>; font-weight: 700;"><?php echo htmlspecialchars($category['name']); ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="background: <?php echo $category['color']; ?>; color: #fff; width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px <?php echo $category['color']; ?>40; flex-shrink: 0;">
                        <i data-feather="<?php echo htmlspecialchars($category['icon'] ?: 'folder'); ?>" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div>
                        <h1 style="font-size: 34px; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; line-height: 1;"><?php echo strtoupper(htmlspecialchars($category['name'])); ?></h1>
                        <?php if ($category['description']): ?>
                            <p style="margin-top: 5px; color: #64748b; font-size: 14px; font-weight: 500; max-width: 600px;"><?php echo htmlspecialchars($category['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <div style="background: #fff; border-radius: 12px; padding: 10px 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                    <div style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Stories</div>
                    <div style="font-size: 22px; font-weight: 900; color: <?php echo $category['color']; ?>;"><?php echo number_format($total_posts); ?></div>
                </div>
                <?php if (!empty($sub_categories)): ?>
                <div style="background: #fff; border-radius: 12px; padding: 10px 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                    <div style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Sub-categories</div>
                    <div style="font-size: 22px; font-weight: 900; color: #0f172a;"><?php echo count($sub_categories); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sub-category pills -->
        <?php if (!empty($sub_categories)): ?>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 20px; padding-top: 20px; border-top: 1px solid <?php echo $category['color']; ?>20; position: relative; z-index: 1;">
            <span style="font-size: 12px; font-weight: 700; color: #94a3b8; display: flex; align-items: center; gap: 4px; margin-right: 4px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                Sub-topics:
            </span>
            <?php foreach ($sub_categories as $sub): ?>
            <a href="<?php echo BASE_URL; ?>category/<?php echo $sub['slug']; ?>" style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; background: #fff; border: 1.5px solid <?php echo $sub['color']; ?>40; border-radius: 20px; font-size: 12px; font-weight: 700; color: <?php echo $sub['color']; ?>; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='<?php echo $sub['color']; ?>';this.style.color='#fff';" onmouseout="this.style.background='#fff';this.style.color='<?php echo $sub['color']; ?>';">
                <i data-feather="<?php echo htmlspecialchars($sub['icon'] ?: 'tag'); ?>" style="width: 11px; height: 11px;"></i>
                <?php echo htmlspecialchars($sub['name']); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($posts)): ?>
    <!-- ── Empty State ── -->
    <div style="text-align: center; padding: 80px 20px; background: #fff; border-radius: 20px; border: 1px solid #e2e8f0;">
        <div style="width: 80px; height: 80px; background: #f8fafc; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <i data-feather="inbox" style="width: 36px; height: 36px; color: #cbd5e1;"></i>
        </div>
        <h3 style="font-size: 22px; font-weight: 800; color: #1e293b; margin-bottom: 8px;">No stories yet</h3>
        <p style="color: #94a3b8; font-size: 15px; margin-bottom: 25px;">No published articles found in this category.</p>
        <a href="<?php echo BASE_URL; ?>" style="display: inline-flex; align-items: center; gap: 8px; background: <?php echo $category['color']; ?>; color: #fff; padding: 12px 25px; border-radius: 25px; font-weight: 700; font-size: 14px; text-decoration: none;">
            <i data-feather="home" style="width: 16px; height: 16px;"></i> Back to Home
        </a>
    </div>

    <?php else: ?>

    <!-- ── Main Content Layout: Left + Sidebar ── -->
    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 30px; align-items: start;" class="cat-layout-grid">
        
        <!-- Left Column: Lead + Grid -->
        <div>
            <!-- Lead / Hero Post (first post) -->
            <?php if ($lead_post && $current_page == 1):
                $lead_url = ($lead_post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $lead_post['id'] : BASE_URL . "article/" . $lead_post['slug'];
                $lead_names = explode(',', $lead_post['cat_names']);
                $lead_colors = explode(',', $lead_post['cat_colors']);
            ?>
            <a href="<?php echo $lead_url; ?>" <?php echo ($lead_post['external_type'] != 'none') ? 'target="_blank"' : ''; ?> class="cat-lead-card" style="display: block; position: relative; border-radius: 20px; overflow: hidden; text-decoration: none; margin-bottom: 30px; height: 460px; box-shadow: 0 20px 50px rgba(0,0,0,0.12);">
                <img src="<?php echo get_post_thumbnail($lead_post['featured_image']); ?>" style="width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; transition: transform 0.5s ease;" class="cat-lead-img">
                <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15,23,42,0.97) 0%, rgba(15,23,42,0.5) 45%, transparent 100%); display: flex; flex-direction: column; justify-content: flex-end; padding: 35px;">
                    <!-- Category badge -->
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
                        <span style="background: <?php echo $category['color']; ?>; color: #fff; padding: 5px 13px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </span>
                        <?php if ($lead_post['video_url']): ?>
                        <span style="background: rgba(239,68,68,0.9); color: #fff; padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; display: flex; align-items: center; gap: 4px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="white"><polygon points="5 3 19 12 5 21 5 3"/></svg> VIDEO
                        </span>
                        <?php endif; ?>
                    </div>
                    <h2 style="color: #fff; font-size: 27px; font-weight: 900; line-height: 1.3; margin-bottom: 12px; text-shadow: 0 2px 10px rgba(0,0,0,0.4);"><?php echo htmlspecialchars($lead_post['title']); ?></h2>
                    <p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo get_post_excerpt($lead_post, 20); ?></p>
                    <div style="display: flex; align-items: center; gap: 15px; font-size: 12px; color: #64748b; font-weight: 700;">
                        <span style="display: flex; align-items: center; gap: 5px; color: #94a3b8;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <?php echo format_date($lead_post['created_at']); ?>
                        </span>
                        <span style="display: flex; align-items: center; gap: 5px; color: #94a3b8;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <?php echo number_format($lead_post['views'] ?? 0); ?> views
                        </span>
                    </div>
                </div>
                <!-- Read More CTA -->
                <div style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.15); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.25); color: #fff; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 5px;">
                    FEATURED
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
            </a>
            <?php endif; ?>

            <!-- Article Grid -->
            <?php if (!empty($grid_posts)): ?>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px; margin-bottom: 30px;" class="cat-article-grid">
                <?php foreach ($grid_posts as $post):
                    $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
                    $p_names = explode(',', $post['cat_names']);
                    $p_colors = explode(',', $post['cat_colors']);
                ?>
                <article class="cat-grid-item" style="background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                    <a href="<?php echo $post_url; ?>" <?php echo ($post['external_type'] != 'none') ? 'target="_blank"' : ''; ?> style="text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%;">
                        <!-- Thumbnail -->
                        <div style="position: relative; overflow: hidden; aspect-ratio: 16/10; flex-shrink: 0;">
                            <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease;" class="cat-grid-img">
                            <?php if ($post['video_url']): ?>
                            <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
                                <div style="width: 36px; height: 36px; background: rgba(239,68,68,0.85); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="white"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                </div>
                            </div>
                            <?php endif; ?>
                            <!-- Category tag on image -->
                            <div style="position: absolute; top: 10px; left: 10px;">
                                <span style="background: <?php echo $category['color']; ?>; color: #fff; padding: 3px 10px; border-radius: 5px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </span>
                            </div>
                        </div>
                        <!-- Content -->
                        <div style="padding: 16px 18px 18px; flex: 1; display: flex; flex-direction: column; gap: 8px;">
                            <?php if ($post['external_label'] != 'none'): ?>
                            <span style="font-size: 10px; font-weight: 800; color: #6366f1; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo strtoupper(htmlspecialchars($post['external_label'])); ?></span>
                            <?php endif; ?>
                            <h4 style="font-size: 15px; font-weight: 800; color: #0f172a; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; flex: 1; transition: color 0.2s;" class="cat-grid-title">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </h4>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 10px; border-top: 1px solid #f1f5f9; margin-top: auto;">
                                <span style="font-size: 12px; color: #94a3b8; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <?php echo format_date($post['created_at']); ?>
                                </span>
                                <span style="font-size: 12px; color: #94a3b8; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <?php echo number_format($post['views'] ?? 0); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
            <?php elseif ($current_page == 1 && !$lead_post): ?>
            <!-- No posts at all -->
            <div style="text-align: center; padding: 60px 20px; color: #94a3b8;">
                <i data-feather="inbox" style="width: 40px; height: 40px;"></i>
                <p style="margin-top: 15px; font-size: 15px;">No more stories in this category.</p>
            </div>
            <?php endif; ?>

            <!-- ── Pagination ── -->
            <?php if ($total_pages > 1): ?>
            <div style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 10px; padding: 25px 0; flex-wrap: wrap;">
                <?php if ($current_page > 1): ?>
                <a href="?slug=<?php echo urlencode($slug); ?>&page=<?php echo $current_page - 1; ?>" style="display: flex; align-items: center; gap: 5px; padding: 9px 18px; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13px; font-weight: 700; color: #475569; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.borderColor='<?php echo $category['color']; ?>';this.style.color='<?php echo $category['color']; ?>';" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#475569';">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                    Prev
                </a>
                <?php endif; ?>

                <?php
                $range = 2;
                $start_p = max(1, $current_page - $range);
                $end_p   = min($total_pages, $current_page + $range);
                if ($start_p > 1) echo '<span style="color:#94a3b8; font-size:13px; padding: 0 5px;">…</span>';
                for ($p = $start_p; $p <= $end_p; $p++):
                    $is_curr = ($p == $current_page);
                ?>
                <a href="?slug=<?php echo urlencode($slug); ?>&page=<?php echo $p; ?>" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 14px; font-weight: 700; text-decoration: none; transition: all 0.2s; <?php echo $is_curr ? "background: {$category['color']}; color: #fff; border: none; box-shadow: 0 4px 12px {$category['color']}40;" : 'background: #fff; border: 1.5px solid #e2e8f0; color: #475569;'; ?>">
                    <?php echo $p; ?>
                </a>
                <?php endfor;
                if ($end_p < $total_pages) echo '<span style="color:#94a3b8; font-size:13px; padding: 0 5px;">…</span>'; ?>

                <?php if ($current_page < $total_pages): ?>
                <a href="?slug=<?php echo urlencode($slug); ?>&page=<?php echo $current_page + 1; ?>" style="display: flex; align-items: center; gap: 5px; padding: 9px 18px; background: <?php echo $category['color']; ?>; border: 1.5px solid <?php echo $category['color']; ?>; border-radius: 10px; font-size: 13px; font-weight: 700; color: #fff; text-decoration: none; transition: all 0.2s; box-shadow: 0 4px 12px <?php echo $category['color']; ?>35;">
                    Next
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Right Sidebar ── -->
        <aside style="display: flex; flex-direction: column; gap: 24px; position: sticky; top: 20px;">

            <!-- Popular in this category -->
            <?php if (!empty($popular_posts)): ?>
            <div style="background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
                <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px; background: linear-gradient(135deg, <?php echo $category['color']; ?>12 0%, transparent 100%);">
                    <div style="background: <?php echo $category['color']; ?>; color: #fff; padding: 6px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    </div>
                    <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">Most Read</h4>
                </div>
                <div style="padding: 10px 0;">
                    <?php foreach ($popular_posts as $idx => $pp):
                        $pp_url = ($pp['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $pp['id'] : BASE_URL . "article/" . $pp['slug'];
                    ?>
                    <a href="<?php echo $pp_url; ?>" style="display: flex; gap: 12px; align-items: center; padding: 12px 18px; text-decoration: none; color: inherit; transition: background 0.2s; border-bottom: 1px solid #f8fafc;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <span style="font-size: 22px; font-weight: 900; color: <?php echo $idx < 3 ? $category['color'] : '#e2e8f0'; ?>; font-style: italic; width: 28px; text-align: center; flex-shrink: 0; line-height: 1;"><?php echo $idx + 1; ?></span>
                        <div style="min-width: 60px; max-width: 60px; height: 55px; border-radius: 8px; overflow: hidden; flex-shrink: 0;">
                            <img src="<?php echo get_post_thumbnail($pp['featured_image']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <h5 style="font-size: 13px; font-weight: 800; color: #1e293b; line-height: 1.4; margin: 0 0 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" class="cat-pop-title"><?php echo htmlspecialchars($pp['title']); ?></h5>
                            <span style="font-size: 11px; color: #94a3b8; font-weight: 600; display: flex; align-items: center; gap: 3px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <?php echo number_format($pp['views'] ?? 0); ?>
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Sub-categories quick nav -->
            <?php if (!empty($sub_categories)): ?>
            <div style="background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
                <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px;">
                    <div style="background: #f8fafc; color: #64748b; padding: 6px; border-radius: 8px; display: flex;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </div>
                    <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">Sub-Topics</h4>
                </div>
                <div style="padding: 12px 16px; display: flex; flex-direction: column; gap: 6px;">
                    <?php foreach ($sub_categories as $sub): ?>
                    <a href="<?php echo BASE_URL; ?>category/<?php echo $sub['slug']; ?>" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; text-decoration: none; color: inherit; border: 1px solid #f1f5f9; transition: all 0.2s;" onmouseover="this.style.background='<?php echo $sub['color']; ?>10';this.style.borderColor='<?php echo $sub['color']; ?>40';" onmouseout="this.style.background='transparent';this.style.borderColor='#f1f5f9';">
                        <div style="background: <?php echo $sub['color']; ?>18; color: <?php echo $sub['color']; ?>; width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-feather="<?php echo htmlspecialchars($sub['icon'] ?: 'tag'); ?>" style="width: 14px; height: 14px;"></i>
                        </div>
                        <span style="font-size: 13px; font-weight: 700; color: #334155;"><?php echo htmlspecialchars($sub['name']); ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2" style="margin-left: auto; flex-shrink: 0;"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ad / Newsletter block placeholder -->
            <div style="background: linear-gradient(135deg, <?php echo $category['color']; ?> 0%, <?php echo $category['color']; ?>cc 100%); border-radius: 16px; padding: 25px; text-align: center; color: #fff; position: relative; overflow: hidden; box-shadow: 0 10px 30px <?php echo $category['color']; ?>35;">
                <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
                <div style="position: absolute; bottom: -30px; left: -15px; width: 80px; height: 80px; background: rgba(255,255,255,0.08); border-radius: 50%;"></div>
                <div style="position: relative; z-index: 1;">
                    <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <h4 style="font-size: 15px; font-weight: 800; margin-bottom: 6px;">Stay Updated</h4>
                    <p style="font-size: 12px; opacity: 0.85; line-height: 1.5; margin-bottom: 16px;">Get the latest <?php echo htmlspecialchars($category['name']); ?> stories directly in your inbox.</p>
                    <a href="<?php echo BASE_URL; ?>contact.php" style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.95); color: <?php echo $category['color']; ?>; padding: 9px 20px; border-radius: 20px; font-size: 12px; font-weight: 800; text-decoration: none; transition: all 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                        Subscribe
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </div>

        </aside>
    </div>
    <?php endif; ?>

</main>

<style>
    @keyframes catTicker {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    .cat-ticker:hover { animation-play-state: paused; }

    /* Lead card hover */
    .cat-lead-card:hover .cat-lead-img { transform: scale(1.04); }

    /* Grid card hover */
    .cat-grid-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.08) !important;
        border-color: transparent !important;
    }
    .cat-grid-item:hover .cat-grid-img { transform: scale(1.05); }
    .cat-grid-item:hover .cat-grid-title { color: var(--primary) !important; }

    /* Popular titles */
    a:hover .cat-pop-title { color: var(--primary) !important; }

    /* Responsive */
    @media (max-width: 1024px) {
        .cat-layout-grid { grid-template-columns: 1fr !important; }
        aside { position: static !important; }
    }
    @media (max-width: 640px) {
        .cat-article-grid { grid-template-columns: 1fr !important; }
    }
</style>

<?php include 'includes/public_footer.php'; ?>
