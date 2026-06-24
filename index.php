<?php
if (!file_exists('includes/config.php')) {
    header("Location: install.php");
    exit;
}
include 'includes/public_header.php';

// YouTube ID extractor
function yt_id($url)
{
    $url = trim($url);
    if (preg_match('/(?:v=|youtu\.be\/|embed\/|live\/)([a-zA-Z0-9_-]{11})/', $url, $m))
        return $m[1];
    return null;
}

// 1. Fetch Featured Posts (Lead Stories for Slider)
$stmt = $pdo->query("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors, GROUP_CONCAT(c.slug) as cat_slugs 
                     FROM posts p 
                     JOIN post_categories pc ON p.id = pc.post_id 
                     JOIN categories c ON pc.category_id = c.id AND c.status = 'active'
                     WHERE p.status = 'published' AND p.is_featured = 1 AND p.published_at <= NOW()
                     GROUP BY p.id ORDER BY p.published_at DESC LIMIT 4");
$featured_posts = $stmt->fetchAll();

if (count($featured_posts) == 0) {
    $stmt = $pdo->query("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors, GROUP_CONCAT(c.slug) as cat_slugs 
                         FROM posts p 
                         JOIN post_categories pc ON p.id = pc.post_id 
                         JOIN categories c ON pc.category_id = c.id AND c.status = 'active'
                         WHERE p.status = 'published' AND p.published_at <= NOW()
                         GROUP BY p.id ORDER BY p.published_at DESC LIMIT 4");
    $featured_posts = $stmt->fetchAll();
}

$featured_ids = array_column($featured_posts, 'id');
$featured_id = $featured_ids[0] ?? 0; // fallback for other queries if needed

// 2. Fetch Top 10 Posts (by views)
$stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
                        FROM posts p 
                        JOIN post_categories pc ON p.id = pc.post_id 
                        JOIN categories c ON pc.category_id = c.id AND c.status = 'active'
                        WHERE p.status = 'published' AND p.id != ? AND p.published_at <= NOW()
                        GROUP BY p.id ORDER BY p.views DESC LIMIT 10");
$stmt->execute([$featured_id]);
$top_10 = $stmt->fetchAll();

// 3. Fetch Breaking News (latest 4)
$stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
                        FROM posts p 
                        JOIN post_categories pc ON p.id = pc.post_id 
                        JOIN categories c ON pc.category_id = c.id AND c.status = 'active'
                        WHERE p.status = 'published' AND p.id != ? AND p.published_at <= NOW()
                        GROUP BY p.id ORDER BY p.published_at DESC LIMIT 4");
$stmt->execute([$featured_id]);
$breaking_news_latest = $stmt->fetchAll();

// 4. Fetch Latest News for main grid
$total_posts_stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at <= NOW()");
$total_posts = $total_posts_stmt->fetchColumn();

if ($total_posts > 20) {
    $exclude_ids = array_merge($featured_ids, array_column($top_10, 'id'), array_column($breaking_news_latest, 'id'));
} else {
    // If DB is small, don't hide everything, just exclude the main featured posts
    $exclude_ids = $featured_ids;
}
$exclude_ids = array_unique(array_filter($exclude_ids));
$placeholders = $exclude_ids ? str_repeat('?,', count($exclude_ids) - 1) . '?' : '0';

$sql = "SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
        FROM posts p 
        JOIN post_categories pc ON p.id = pc.post_id 
        JOIN categories c ON pc.category_id = c.id AND c.status = 'active'
        WHERE p.status = 'published' " . ($exclude_ids ? "AND p.id NOT IN ($placeholders)" : "") . " AND p.published_at <= NOW()
        GROUP BY p.id ORDER BY p.published_at DESC LIMIT 12";

$stmt = $pdo->prepare($sql);
$stmt->execute(array_values($exclude_ids));
$latest_news = $stmt->fetchAll();

// Trending Tags
$categories_list = get_all_tags($pdo, 12);

// 5. Live Stream Status
$live_enabled = get_setting('live_youtube_enabled') === '1';
$live_url = get_setting('live_youtube_url');
$live_title = get_setting('live_stream_title', 'Watch Live');
$live_vid_id = $live_url ? yt_id($live_url) : null;

// 6. Fetch Featured Homepage Categories
$stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' AND show_on_homepage = 1 ORDER BY created_at DESC LIMIT 3");
$featured_categories = $stmt->fetchAll();

// 7. Fetch Latest Active Poll
$poll_stmt = $pdo->query("SELECT * FROM polls WHERE status = 'active' AND (starts_at IS NULL OR starts_at <= NOW()) AND (expires_at IS NULL OR expires_at >= NOW()) ORDER BY created_at DESC LIMIT 1");
$active_poll = $poll_stmt->fetch();
$poll_has_voted = false;
$poll_options = [];
$poll_total_votes = 0;
if ($active_poll) {
    $voter_id = $_COOKIE['voter_id'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $check_vote = $pdo->prepare("SELECT id FROM poll_votes WHERE poll_id = ? AND (browser_id = ? OR ip_address = ?)");
    $check_vote->execute([$active_poll['id'], $voter_id, $ip_address]);
    if ($check_vote->fetch()) {
        $poll_has_voted = true;
    }
    $opt_stmt = $pdo->prepare("SELECT * FROM poll_options WHERE poll_id = ? ORDER BY id ASC");
    $opt_stmt->execute([$active_poll['id']]);
    $poll_options = $opt_stmt->fetchAll();
    foreach ($poll_options as $opt) {
        $poll_total_votes += $opt['votes_count'];
    }
}
?>

<?php if (get_setting('homepage_theme', 'theme1') === 'theme2'): ?>
    <!-- ==============================================
         THEME 2: 100% Width Modern Bento Showcase
         ============================================== -->
    <main class="content-container t2-theme-main"
        style="max-width: 100%; margin: 0 auto; background-color: #f8fafc;">

        <!-- Theme 2 Ticker -->
        <?php if (!empty($breaking_news_latest)): ?>
            <div class="t2-breaking-ticker"
                style="display: flex; align-items: center; background: #0f172a; border-radius: 12px; height: 46px; overflow: hidden; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.06); box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                <span
                    style="background: linear-gradient(135deg, var(--primary), #ef4444); color: white; padding: 0 20px; height: 100%; display: flex; align-items: center; font-weight: 850; font-size: 12px; letter-spacing: 1.5px; flex-shrink: 0; text-transform: uppercase;">BREAKING
                    NOW</span>
                <div class="ticker-wrapper" style="flex: 1; overflow: hidden; position: relative;">
                    <div class="ticker-content"
                        style="display: inline-block; white-space: nowrap; animation: ticker 25s linear infinite;">
                        <?php foreach ($breaking_news_latest as $bn):
                            $post_url = ($bn['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $bn['id'] : BASE_URL . "article/" . $bn['slug'];
                            ?>
                            <a href="<?php echo $post_url; ?>" <?php echo ($bn['external_type'] != 'none') ? 'target="_blank"' : ''; ?>
                                style="color: #cbd5e1; text-decoration: none; margin-right: 40px; font-weight: 700; font-size: 13.5px;">
                                <span style="color: var(--primary); font-weight: 900; margin-right: 8px;">•</span>
                                <?php echo htmlspecialchars($bn['title']); ?>
                            </a>
                        <?php endforeach; ?>
                        <!-- Repeat for seamless scrolling -->
                        <?php foreach ($breaking_news_latest as $bn):
                            $post_url = ($bn['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $bn['id'] : BASE_URL . "article/" . $bn['slug'];
                            ?>
                            <a href="<?php echo $post_url; ?>" <?php echo ($bn['external_type'] != 'none') ? 'target="_blank"' : ''; ?>
                                style="color: #cbd5e1; text-decoration: none; margin-right: 40px; font-weight: 700; font-size: 13.5px;">
                                <span style="color: var(--primary); font-weight: 900; margin-right: 8px;">•</span>
                                <?php echo htmlspecialchars($bn['title']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Theme 2 Bento Hero Grid -->
        <?php if (!empty($featured_posts)):
            $f_count = count($featured_posts);
            ?>
            <section class="t2-bento-hero" style="margin-bottom: 45px;">
                <?php if ($f_count >= 3): ?>
                    <!-- Bento Grid Layout -->
                    <div class="t2-bento-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; min-height: 520px;">

                        <!-- Left Large Column (Main Featured) -->
                        <?php
                        $main_f = $featured_posts[0];
                        $main_f_url = ($main_f['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $main_f['id'] : BASE_URL . "article/" . $main_f['slug'];
                        $cats = explode(',', $main_f['cat_names']);
                        $colors = explode(',', $main_f['cat_colors']);
                        ?>
                        <a href="<?php echo $main_f_url; ?>" <?php echo ($main_f['external_type'] != 'none') ? 'target="_blank"' : ''; ?> class="t2-bento-card"
                            style="display: block; position: relative; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-decoration: none; height: 100%;">
                            <img src="<?php echo get_post_thumbnail($main_f['featured_image']); ?>"
                                style="width:100%; height:100%; object-fit:cover; position:absolute; inset:0; transition: transform 0.4s ease;"
                                class="t2-card-img">
                            <div
                                style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15,23,42,0.95) 0%, rgba(15,23,42,0.3) 50%, transparent 100%); display:flex; flex-direction:column; justify-content:flex-end; padding: 40px; z-index: 2;">
                                <span
                                    style="background: <?php echo $colors[0] ?? 'var(--primary)'; ?>; color: #fff; padding: 6px 14px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; width: max-content; margin-bottom: 15px; letter-spacing: 1px;">
                                    <?php echo htmlspecialchars($cats[0] ?? 'Featured'); ?>
                                </span>
                                <h2
                                    style="color: #fff; font-size: 32px; font-weight: 800; line-height: 1.25; margin-bottom: 12px; text-shadow: 0 2px 10px rgba(0,0,0,0.4);">
                                    <?php echo htmlspecialchars($main_f['title']); ?></h2>
                                <p
                                    style="color: #cbd5e1; font-size: 15px; line-height: 1.6; margin: 0 0 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-shadow: 0 1px 5px rgba(0,0,0,0.4);">
                                    <?php echo get_post_excerpt($main_f, 25); ?></p>
                                <span
                                    style="font-size: 12px; color: #94a3b8; font-weight: 700; display:flex; align-items:center; gap:5px;"><i
                                        data-feather="calendar" style="width:14px;height:14px;"></i>
                                    <?php echo format_date($main_f['created_at']); ?></span>
                            </div>
                        </a>

                        <!-- Right Column (Stacked Cards) -->
                        <div style="display: flex; flex-direction: column; gap: 24px; height: 100%;">
                            <?php for ($i = 1; $i <= 2; $i++):
                                if (!isset($featured_posts[$i]))
                                    continue;
                                $sub_f = $featured_posts[$i];
                                $sub_f_url = ($sub_f['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $sub_f['id'] : BASE_URL . "article/" . $sub_f['slug'];
                                $cats = explode(',', $sub_f['cat_names']);
                                $colors = explode(',', $sub_f['cat_colors']);
                                ?>
                                <a href="<?php echo $sub_f_url; ?>" <?php echo ($sub_f['external_type'] != 'none') ? 'target="_blank"' : ''; ?> class="t2-bento-card"
                                    style="display: block; position: relative; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-decoration: none; flex: 1; min-height: 248px;">
                                    <img src="<?php echo get_post_thumbnail($sub_f['featured_image']); ?>"
                                        style="width:100%; height:100%; object-fit:cover; position:absolute; inset:0; transition: transform 0.4s ease;"
                                        class="t2-card-img">
                                    <div
                                        style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15,23,42,0.92) 0%, rgba(15,23,42,0.3) 60%, transparent 100%); display:flex; flex-direction:column; justify-content:flex-end; padding: 25px; z-index: 2;">
                                        <span
                                            style="background: <?php echo $colors[0] ?? 'var(--primary)'; ?>; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; width: max-content; margin-bottom: 10px; letter-spacing: 0.5px;">
                                            <?php echo htmlspecialchars($cats[0] ?? 'Featured'); ?>
                                        </span>
                                        <h3
                                            style="color: #fff; font-size: 19px; font-weight: 800; line-height: 1.3; margin: 0 0 10px; text-shadow: 0 2px 8px rgba(0,0,0,0.4);">
                                            <?php echo htmlspecialchars($sub_f['title']); ?></h3>
                                        <span
                                            style="font-size: 11px; color: #94a3b8; font-weight: 700; display:flex; align-items:center; gap:5px;"><i
                                                data-feather="calendar" style="width:12px;height:12px;"></i>
                                            <?php echo format_date($sub_f['created_at']); ?></span>
                                    </div>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Graceful Fallback List for fewer than 3 posts -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                        <?php foreach ($featured_posts as $f_post):
                            $f_post_url = ($f_post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $f_post['id'] : BASE_URL . "article/" . $f_post['slug'];
                            $cats = explode(',', $f_post['cat_names']);
                            $colors = explode(',', $f_post['cat_colors']);
                            ?>
                            <a href="<?php echo $f_post_url; ?>" <?php echo ($f_post['external_type'] != 'none') ? 'target="_blank"' : ''; ?> class="t2-bento-card"
                                style="display: block; position: relative; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-decoration: none; height: 350px;">
                                <img src="<?php echo get_post_thumbnail($f_post['featured_image']); ?>"
                                    style="width:100%; height:100%; object-fit:cover; position:absolute; inset:0; transition: transform 0.4s ease;"
                                    class="t2-card-img">
                                <div
                                    style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15,23,42,0.92) 0%, rgba(15,23,42,0.3) 60%, transparent 100%); display:flex; flex-direction:column; justify-content:flex-end; padding: 30px; z-index: 2;">
                                    <span
                                        style="background: <?php echo $colors[0] ?? 'var(--primary)'; ?>; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; width: max-content; margin-bottom: 12px;">
                                        <?php echo htmlspecialchars($cats[0] ?? 'Featured'); ?>
                                    </span>
                                    <h3 style="color: #fff; font-size: 20px; font-weight: 800; line-height: 1.35; margin: 0 0 10px;">
                                        <?php echo htmlspecialchars($f_post['title']); ?></h3>
                                    <span
                                        style="font-size: 11px; color: #94a3b8; font-weight: 700; display:flex; align-items:center; gap:5px;"><i
                                            data-feather="calendar" style="width:12px;height:12px;"></i>
                                        <?php echo format_date($f_post['created_at']); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- Theme 2 Top 10 Stories -->
        <?php if (!empty($top_10)): ?>
            <section
                style="margin-bottom: 50px; background: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #e2e8f0;">
                <h3
                    style="font-size: 20px; font-weight: 900; color: #0f172a; display:flex; align-items:center; gap:10px; margin-bottom: 25px; text-transform: uppercase; letter-spacing: 0.5px;">
                    <span
                        style="background: linear-gradient(135deg, var(--primary), #ef4444); color: white; width: 32px; height: 32px; display:flex; align-items:center; justify-content:center; border-radius: 8px; font-size: 15px; font-weight: 950; box-shadow: 0 4px 10px rgba(239,68,68,0.2);">10</span>
                    Top 10 Stories
                </h3>

                <div class="t2-top-10-scroll"
                    style="display: grid; grid-template-columns: repeat(10, 1fr); gap: 24px; overflow-x: auto; padding-bottom: 10px; scroll-snap-type: x mandatory;">
                    <?php foreach ($top_10 as $index => $post):
                        $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
                        $num_str = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                        ?>
                        <div style="min-width: 230px; scroll-snap-align: start;">
                            <a href="<?php echo $post_url; ?>" class="t2-top-10-card"
                                style="text-decoration: none; color: inherit; display: block; position: relative;">
                                <div
                                    style="position: relative; margin-bottom: 12px; border-radius: 12px; overflow: hidden; box-shadow: 0 6px 15px rgba(0,0,0,0.06); border: 1px solid #f1f5f9;">
                                    <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>"
                                        style="width: 100%; aspect-ratio: 4/3; object-fit: cover; transition: transform 0.4s ease;"
                                        class="t2-img-hover">
                                    <!-- Giant Background Rank Number -->
                                    <span
                                        style="position: absolute; top: 0; left: 8px; font-size: 58px; font-weight: 900; color: rgba(255,255,255,0.7); font-family: 'Outfit'; z-index: 2; text-shadow: 0 2px 10px rgba(0,0,0,0.15); pointer-events: none;"><?php echo $num_str; ?></span>
                                </div>
                                <h4
                                    style="font-size: 14.5px; font-weight: 800; line-height: 1.4; color: #1e293b; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 40px; transition: color 0.2s;">
                                    <?php echo htmlspecialchars($post['title']); ?></h4>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Theme 2 Split-layout Featured Categories -->
        <?php if (!empty($featured_categories)): ?>
            <?php foreach ($featured_categories as $fcat):
                $stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
                                       FROM posts p 
                                       JOIN post_categories pc ON p.id = pc.post_id 
                                       JOIN categories c ON pc.category_id = c.id AND c.status = 'active'
                                       WHERE p.status = 'published' AND p.published_at <= NOW() AND pc.category_id = ?
                                       GROUP BY p.id ORDER BY p.published_at DESC LIMIT 3");
                $stmt->execute([$fcat['id']]);
                $cat_posts = $stmt->fetchAll();

                if (count($cat_posts) > 0):
                    $main_c = $cat_posts[0];
                    $main_c_url = ($main_c['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $main_c['id'] : BASE_URL . "article/" . $main_c['slug'];
                    ?>
                    <section
                        style="margin-bottom: 50px; background: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #e2e8f0;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;">
                            <h3
                                style="font-size: 19px; font-weight: 900; color: #0f172a; text-transform: uppercase; display: flex; align-items: center; gap: 10px; letter-spacing: 0.5px;">
                                <span
                                    style="background: <?php echo $fcat['color']; ?>; color: #fff; padding: 6px; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px <?php echo $fcat['color']; ?>30;">
                                    <i data-feather="<?php echo $fcat['icon']; ?>" style="width: 16px; height: 16px;"></i>
                                </span>
                                <?php echo htmlspecialchars($fcat['name']); ?>
                            </h3>
                            <a href="<?php echo BASE_URL; ?>category/<?php echo $fcat['slug']; ?>"
                                style="font-size: 13px; font-weight: 800; color: <?php echo $fcat['color']; ?>; text-decoration: none; display:flex; align-items:center; gap:4px; padding: 6px 14px; background: <?php echo $fcat['color']; ?>12; border-radius: 20px; transition: all 0.2s;"
                                class="t2-view-all">
                                View All <i data-feather="arrow-right" style="width:14px; height:14px;"></i>
                            </a>
                        </div>

                        <!-- Split layout: 1 Large card on left, 2 stacked vertical items on right -->
                        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px;" class="t2-split-row">
                            <!-- Left: Large Featured Category card -->
                            <a href="<?php echo $main_c_url; ?>" <?php echo ($main_c['external_type'] != 'none') ? 'target="_blank"' : ''; ?> class="t2-split-lead"
                                style="display: flex; flex-direction: column; text-decoration: none; color: inherit; background: #f8fafc; border-radius: 16px; border: 1px solid #f1f5f9; overflow: hidden; transition: all 0.3s ease;">
                                <div style="position: relative; overflow: hidden;">
                                    <img src="<?php echo get_post_thumbnail($main_c['featured_image']); ?>"
                                        style="width: 100%; aspect-ratio: 16/9; object-fit: cover; transition: transform 0.4s ease;"
                                        class="t2-img-hover">
                                </div>
                                <div style="padding: 20px;">
                                    <h4
                                        style="font-size: 17px; font-weight: 800; color: #0f172a; line-height: 1.4; margin-bottom: 10px;">
                                        <?php echo htmlspecialchars($main_c['title']); ?></h4>
                                    <p style="font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 15px;">
                                        <?php echo get_post_excerpt($main_c, 20); ?></p>
                                    <span
                                        style="font-size: 12px; color: #94a3b8; font-weight: 600; display:flex; align-items:center; gap:5px;"><i
                                            data-feather="calendar" style="width:13px; height:13px;"></i>
                                        <?php echo format_date($main_c['created_at']); ?></span>
                                </div>
                            </a>

                            <!-- Right: Stacked side news articles list -->
                            <div style="display: flex; flex-direction: column; gap: 20px; justify-content: center;">
                                <?php for ($idx = 1; $idx <= 2; $idx++):
                                    if (!isset($cat_posts[$idx]))
                                        continue;
                                    $sub_c = $cat_posts[$idx];
                                    $sub_c_url = ($sub_c['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $sub_c['id'] : BASE_URL . "article/" . $sub_c['slug'];
                                    ?>
                                    <a href="<?php echo $sub_c_url; ?>" <?php echo ($sub_c['external_type'] != 'none') ? 'target="_blank"' : ''; ?> class="t2-split-item"
                                        style="display: flex; gap: 15px; text-decoration: none; color: inherit; align-items: center; padding: 15px; border-radius: 12px; background: #f8fafc; border: 1px solid #f1f5f9; transition: all 0.3s ease;">
                                        <img src="<?php echo get_post_thumbnail($sub_c['featured_image']); ?>"
                                            style="width: 100px; height: 80px; object-fit: cover; border-radius: 10px; flex-shrink:0;">
                                        <div style="flex: 1;">
                                            <h4
                                                style="font-size: 15px; font-weight: 800; color: #1e293b; margin: 0 0 8px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                <?php echo htmlspecialchars($sub_c['title']); ?></h4>
                                            <span
                                                style="font-size: 12px; color: #94a3b8; font-weight: 600; display:flex; align-items:center; gap:5px;"><i
                                                    data-feather="calendar" style="width:12px; height:12px;"></i>
                                                <?php echo format_date($sub_c['created_at']); ?></span>
                                        </div>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </section>
                <?php
                endif;
            endforeach;
        endif; ?>

        <!-- Theme 2 Main Area: Left (Latest News list) & Right (Sidebar) -->
        <div style="display: grid; grid-template-columns: 1fr 340px; gap: 40px;" class="t2-main-grid">

            <!-- Left: Latest News horizontal lists -->
            <section>
                <div
                    style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px;">
                    <h3
                        style="font-size: 19px; font-weight: 900; color: #0f172a; text-transform: uppercase; display: flex; align-items: center; gap: 8px; letter-spacing: 0.5px;">
                        <i data-feather="clock" style="color: var(--primary);"></i> LATEST UPDATES
                    </h3>
                </div>

                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach ($latest_news as $post):
                        $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
                        $names = explode(',', $post['cat_names']);
                        $colors = explode(',', $post['cat_colors']);
                        ?>
                        <article class="t2-latest-horizontal-card"
                            style="background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 15px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.01);">
                            <a href="<?php echo $post_url; ?>" <?php echo ($post['external_type'] != 'none') ? 'target="_blank"' : ''; ?>
                                style="text-decoration: none; color: inherit; display: flex; gap: 20px; align-items: center;"
                                class="t2-horizontal-flex">
                                <div
                                    style="width: 220px; aspect-ratio: 16/10; overflow: hidden; border-radius: 12px; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
                                    <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>"
                                        style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease;"
                                        class="t2-img-hover">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div
                                        style="font-size: 11px; font-weight: 800; margin-bottom: 6px; color: <?php echo $colors[0] ?? 'var(--primary)'; ?>; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <?php echo htmlspecialchars($names[0] ?? 'News'); ?>
                                    </div>
                                    <h4
                                        style="font-size: 17px; font-weight: 800; margin-bottom: 8px; line-height: 1.35; color: #0f172a;">
                                        <?php echo htmlspecialchars($post['title']); ?></h4>
                                    <p
                                        style="font-size: 13.5px; color: #64748b; margin-bottom: 12px; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo get_post_excerpt($post, 20); ?></p>
                                    <div
                                        style="font-size: 12px; color: #94a3b8; font-weight: 600; display:flex; align-items:center; gap:5px;">
                                        <i data-feather="calendar" style="width:13px; height:13px;"></i>
                                        <?php echo format_date($post['created_at']); ?>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Right Sidebar: Widgets -->
            <aside style="display: flex; flex-direction: column; gap: 30px;">

                <!-- Live Stream Widget (Theme 2 Only) -->
                <?php if ($live_enabled && $live_vid_id):
                    $stream_sound = get_setting('live_stream_sound', '0') === '1' ? '0' : '1';
                    ?>
                    <div class="t2-sidebar-widget"
                        style="background: #0f172a; border-radius: 16px; padding: 20px; border: 1px solid rgba(255,255,255,0.06); box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: flex; flex-direction: column;">
                        <h4
                            style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 12px; margin-bottom: 15px; font-size: 15px; font-weight: 850; text-transform: uppercase; color: #fff; display: flex; align-items: center; gap: 8px; letter-spacing: 0.5px;">
                            <span
                                style="width: 8px; height: 8px; background: #ef4444; border-radius: 50%; animation: pulse 1s infinite; box-shadow: 0 0 8px #ef4444;"></span>
                            Live Broadcast
                        </h4>
                        <div
                            style="position: relative; padding-top: 56.25%; background: #000; border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1);">
                            <iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
                                src="https://www.youtube.com/embed/<?php echo $live_vid_id; ?>?autoplay=1&mute=<?php echo $stream_sound; ?>&rel=0&modestbranding=1&controls=0&disablekb=1"
                                title="Live Stream"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                            </iframe>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Poll Widget -->
                <?php if ($active_poll): ?>
                    <div class="t2-sidebar-widget"
                        style="background: white; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.01);">
                        <h4
                            style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 18px; font-size: 15px; font-weight: 800; text-transform: uppercase; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <div
                                style="background: rgba(99, 102, 241, 0.1); padding: 5px; border-radius: 6px; color: #6366f1; display:flex;">
                                <i data-feather="pie-chart" style="width: 14px; height: 14px;"></i>
                            </div>
                            Poll of the Day
                        </h4>
                        <div style="font-size: 15px; font-weight: 800; color: #1e293b; margin-bottom: 18px; line-height: 1.4;">
                            <?php echo htmlspecialchars($active_poll['question']); ?>
                        </div>

                        <div id="t2-poll-container-<?php echo $active_poll['id']; ?>">
                            <?php if ($poll_has_voted): ?>
                                <!-- Results View -->
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <?php foreach ($poll_options as $opt):
                                        $pct = $poll_total_votes > 0 ? round(($opt['votes_count'] / $poll_total_votes) * 100) : 0;
                                        ?>
                                        <div style="margin-bottom: 4px;">
                                            <div
                                                style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 4px;">
                                                <span><?php echo htmlspecialchars($opt['option_text']); ?></span>
                                                <span><?php echo $pct; ?>%</span>
                                            </div>
                                            <div style="background: #f1f5f9; height: 6px; border-radius: 3px; overflow: hidden;">
                                                <div
                                                    style="width: <?php echo $pct; ?>%; height: 100%; background: #6366f1; border-radius: 3px; transition: width 1s ease-out;">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div
                                    style="margin-top: 15px; font-size: 11px; color: #94a3b8; font-weight: 600; text-align: center;">
                                    Total Votes: <?php echo number_format($poll_total_votes); ?>
                                </div>
                            <?php else: ?>
                                <!-- Voting View -->
                                <form id="t2-poll-form-<?php echo $active_poll['id']; ?>"
                                    onsubmit="submitPoll(event, <?php echo $active_poll['id']; ?>)">
                                    <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px;">
                                        <?php foreach ($poll_options as $opt): ?>
                                            <label
                                                style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                                                <input type="radio" name="poll_option" value="<?php echo $opt['id']; ?>" required
                                                    style="accent-color: #6366f1; width: 14px; height: 14px; cursor: pointer;">
                                                <span
                                                    style="font-size: 13.5px; font-weight: 600; color: #334155;"><?php echo htmlspecialchars($opt['option_text']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="submit"
                                        style="width: 100%; background: #6366f1; color: white; border: none; padding: 10px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; transition: background 0.2s;">
                                        Vote Now
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Photo of the Day Widget -->
                <?php if (get_setting('photo_of_day_image')): ?>
                    <div class="t2-sidebar-widget"
                        style="background: white; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.01);">
                        <h4
                            style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 18px; font-size: 15px; font-weight: 800; text-transform: uppercase; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <div
                                style="background: rgba(249, 115, 22, 0.1); padding: 5px; border-radius: 6px; color: #f97316; display:flex;">
                                <i data-feather="image" style="width: 14px; height: 14px;"></i>
                            </div>
                            Photo of the Day
                        </h4>

                        <div
                            style="border-radius: 12px; overflow: hidden; margin-bottom: 12px; border: 1px solid #e2e8f0; position: relative; cursor: pointer;">
                            <img src="<?php echo BASE_URL; ?>assets/images/<?php echo get_setting('photo_of_day_image'); ?>"
                                alt="<?php echo htmlspecialchars(get_setting('photo_of_day_title', 'Photo of the Day')); ?>"
                                style="width: 100%; height: auto; max-height: 220px; object-fit: cover; transition: transform 0.3s ease; display: block;"
                                class="t2-img-hover">
                        </div>

                        <?php if (get_setting('photo_of_day_title')): ?>
                            <h5 style="margin: 0 0 6px; font-size: 14px; font-weight: 800; color: #1e293b; line-height: 1.4;">
                                <?php echo htmlspecialchars(get_setting('photo_of_day_title')); ?>
                            </h5>
                        <?php endif; ?>

                        <?php if (get_setting('photo_of_day_caption')): ?>
                            <p style="margin: 0; font-size: 12.5px; color: #64748b; line-height: 1.5; font-weight: 500;">
                                <?php echo nl2br(htmlspecialchars(get_setting('photo_of_day_caption'))); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Today's Activity & Events Section -->
                <div class="t2-sidebar-widget"
                    style="background: white; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.01);">
                    <h4
                        style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; font-size: 15px; font-weight: 800; text-transform: uppercase; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <div
                            style="background: #f8fafc; padding: 5px; border-radius: 6px; color: var(--primary); display:flex;">
                            <i data-feather="calendar" style="width: 14px; height: 14px;"></i>
                        </div>
                        EVENTS OF THE DAY
                    </h4>

                    <div style="position: relative; padding-left: 20px;">
                        <div style="position: absolute; left: 4px; top: 0; bottom: 0; width: 2px; background: #e2e8f0;">
                        </div>

                        <?php
                        $timeline_stmt = $pdo->query("SELECT * FROM timeline WHERE event_date = CURDATE() ORDER BY event_time ASC");
                        $timeline_items = $timeline_stmt->fetchAll();
                        $now = date('H:i');

                        if ($timeline_items):
                            foreach ($timeline_items as $item):
                                $color = '#f59e0b'; // Upcoming
                                if ($item['event_time'] < $now) {
                                    $color = '#10b981'; // Completed
                                } elseif ($item['event_time'] == $now) {
                                    $color = '#ef4444'; // Ongoing / Live
                                }
                                ?>
                                <div style="position: relative; margin-bottom: 20px; padding-left: 8px;">
                                    <span
                                        style="position: absolute; left: -24px; top: 4px; width: 10px; height: 10px; background: <?php echo $color; ?>; border: 2px solid white; border-radius: 50%; box-shadow: 0 0 0 2px <?php echo $color; ?>40; z-index: 1;"></span>
                                    <div
                                        style="font-size: 11px; font-weight: 800; color: <?php echo $color; ?>; text-transform: uppercase; margin-bottom: 2px;">
                                        <?php echo date("h:i A", strtotime($item['event_time'])); ?></div>
                                    <div style="font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">
                                        <?php echo htmlspecialchars($item['event_name']); ?></div>
                                    <div style="font-size: 12.5px; font-weight: 500; color: #64748b; line-height: 1.4;">
                                        <?php echo htmlspecialchars($item['description']); ?></div>
                                </div>
                                <?php
                            endforeach;
                        else: ?>
                            <div style="text-align: center; padding: 15px 0;">
                                <i data-feather="clock" style="width: 24px; color: #cbd5e1; margin-bottom: 10px;"></i>
                                <p style="font-size: 13px; color: #94a3b8; font-weight: 500;">No events scheduled for today.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ad in sidebar -->
                <div style="text-align: center;">
                    <?php echo display_ad('sidebar', $pdo); ?>
                </div>

                <!-- Popular News -->
                <div class="t2-sidebar-widget"
                    style="background: white; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.01);">
                    <h4
                        style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; font-size: 15px; font-weight: 800; text-transform: uppercase; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <div style="background: #f8fafc; padding: 5px; border-radius: 6px; color: #ef4444; display:flex;">
                            <i data-feather="trending-up" style="width: 14px; height: 14px;"></i>
                        </div>
                        MOST POPULAR
                    </h4>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php
                        $popular = $pdo->query("SELECT * FROM posts WHERE status = 'published' ORDER BY views DESC LIMIT 5")->fetchAll();
                        foreach ($popular as $index => $tp):
                            $tp_url = ($tp['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $tp['id'] : BASE_URL . "article/" . $tp['slug'];
                            ?>
                            <a href="<?php echo $tp_url; ?>" class="t2-popular-card"
                                style="display: flex; gap: 12px; text-decoration: none; align-items: center;">
                                <div
                                    style="width: 20px; font-size: 20px; font-weight: 800; color: #cbd5e1; font-style: italic;">
                                    <?php echo $index + 1; ?></div>
                                <div style="width: 55px; height: 55px; flex-shrink: 0; border-radius: 8px; overflow: hidden;">
                                    <img src="<?php echo get_post_thumbnail($tp['featured_image']); ?>"
                                        style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div style="flex: 1;">
                                    <h5
                                        style="font-size: 13px; margin: 0 0 4px 0; line-height: 1.35; font-weight: 800; color: #0f172a; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo htmlspecialchars($tp['title']); ?></h5>
                                    <div
                                        style="font-size: 11px; color: #94a3b8; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                        <i data-feather="eye" style="width: 10px; height: 10px;"></i>
                                        <?php echo number_format($tp['views']); ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        </div>
    </main>
<?php else: ?>
    <!-- ==============================================
         THEME 1: Boxed Layout with Sidebar Navigation
         ============================================== -->
    <main class="content-container" style="max-width: 1400px; margin: 0 auto; padding: 30px 20px;">
        <!-- Trending Bar -->
        <div class="trending-scroll-container"
            style="display: flex; gap: 15px; margin-bottom: 35px; overflow-x: auto; padding-bottom: 10px; align-items: center; white-space: nowrap; -ms-overflow-style: none; scrollbar-width: none;">
            <span
                style="background: var(--primary); color: #fff; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 800; letter-spacing: 1px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">TRENDING</span>
            <?php foreach ($categories_list as $tag): ?>
                <a href="<?php echo BASE_URL; ?>tag/<?php echo $tag['slug']; ?>" class="tag-pill">
                    #<?php echo $tag['name']; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Live Stream (If Enabled) -->
        <?php if ($live_enabled && $live_vid_id):
            $stream_sound = get_setting('live_stream_sound', '0') === '1' ? '0' : '1';
            ?>
            <section style="margin-bottom: 50px;">
                <div
                    style="background: #0f172a; border-radius: 20px; overflow: hidden; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.2); display: flex; flex-direction: column;">
                    <div
                        style="padding: 20px 30px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <h3
                            style="font-size: 20px; font-weight: 800; color: #fff; margin:0; display: flex; align-items: center; gap: 12px;">
                            <span
                                style="width: 12px; height: 12px; background: #ef4444; border-radius: 50%; animation: pulse 1s infinite; box-shadow: 0 0 10px #ef4444;"></span>
                            LIVE BROADCAST: <?php echo htmlspecialchars($live_title); ?>
                        </h3>
                    </div>
                    <div style="position: relative; padding-top: 50%; max-height: 500px; background: #000;">
                        <iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
                            src="https://www.youtube.com/embed/<?php echo $live_vid_id; ?>?autoplay=1&mute=<?php echo $stream_sound; ?>&rel=0&modestbranding=1&controls=0&disablekb=1"
                            title="Live Stream"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                        </iframe>
                        <div
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10; cursor: default; background: transparent;">
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Asymmetrical Hero Grid -->
        <section class="hero-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 60px;">
            <!-- Lead Story Slider (Left) -->
            <div style="position: relative; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.1); height: 550px;"
                class="hero-slider-container">
                <?php if (!empty($featured_posts)): ?>
                    <div class="slider-track"
                        style="display: flex; height: 100%; transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);">
                        <?php foreach ($featured_posts as $index => $f_post):
                            $post_url = ($f_post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $f_post['id'] : BASE_URL . "article/" . $f_post['slug'];
                            ?>
                            <a href="<?php echo $post_url; ?>" <?php echo ($f_post['external_type'] != 'none') ? 'target="_blank"' : ''; ?> class="hero-slide"
                                style="flex: 0 0 100%; height: 100%; position: relative; text-decoration: none; overflow: hidden;">
                                <img src="<?php echo get_post_thumbnail($f_post['featured_image']); ?>"
                                    style="width: 100%; height: 100%; object-fit: cover;" class="hero-img">
                                <div
                                    style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.4) 50%, transparent 100%); display: flex; flex-direction: column; justify-content: flex-end; padding: 40px;">
                                    <?php
                                    $f_names = explode(',', $f_post['cat_names']);
                                    $f_colors = explode(',', $f_post['cat_colors']);
                                    ?>
                                    <span
                                        style="background: <?php echo $f_colors[0] ?? 'var(--primary)'; ?>; color: #fff; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 800; text-transform: uppercase; width: max-content; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                                        <?php echo $f_names[0] ?? 'Featured'; ?>
                                    </span>
                                    <h2
                                        style="color: #f8fafc; font-size: 42px; font-weight: 800; line-height: 1.2; margin: 0 0 15px 0; text-shadow: 0 2px 15px rgba(0,0,0,0.3);">
                                        <?php echo $f_post['title']; ?></h2>
                                    <p
                                        style="color: #cbd5e1; font-size: 18px; line-height: 1.6; margin: 0; max-width: 800px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-shadow: 0 1px 5px rgba(0,0,0,0.5);">
                                        <?php echo get_post_excerpt($f_post, 25); ?></p>
                                    <div
                                        style="display: flex; align-items: center; gap: 15px; margin-top: 20px; color: #94a3b8; font-size: 14px; font-weight: 600;">
                                        <span style="display: flex; align-items: center; gap: 5px;"><i data-feather="clock"
                                                style="width: 16px;"></i> <?php echo format_date($f_post['created_at']); ?></span>
                                        <?php if ($f_post['video_url']): ?>
                                            <span style="display: flex; align-items: center; gap: 5px; color: #ef4444;"><i
                                                    data-feather="play-circle" style="width: 16px;"></i> Video Included</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Slider Controls -->
                    <div style="position: absolute; bottom: 40px; right: 40px; display: flex; gap: 10px; z-index: 10;">
                        <button onclick="prevSlide(event)"
                            style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.5); color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; backdrop-filter: blur(5px); transition: 0.3s;"
                            class="slider-btn"><i data-feather="chevron-left"></i></button>
                        <button onclick="nextSlide(event)"
                            style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.5); color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; backdrop-filter: blur(5px); transition: 0.3s;"
                            class="slider-btn"><i data-feather="chevron-right"></i></button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Breaking News Stack (Right) -->
            <div
                style="display: flex; flex-direction: column; background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; overflow: hidden;">
                <div
                    style="padding: 25px 25px 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; background: #f8fafc;">
                    <div
                        style="width: 14px; height: 14px; background: #ef4444; border-radius: 50%; animation: pulse 1.5s infinite; box-shadow: 0 0 8px #ef4444;">
                    </div>
                    <h3
                        style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">
                        Breaking Now</h3>
                </div>

                <div
                    style="display: flex; flex-direction: column; flex: 1; padding: 10px 25px 25px 25px; gap: 20px; overflow-y: auto;">
                    <?php foreach ($breaking_news_latest as $post):
                        $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
                        ?>
                        <a href="<?php echo $post_url; ?>" class="breaking-card" <?php echo ($post['external_type'] != 'none') ? 'target="_blank"' : ''; ?>
                            style="display: flex; gap: 18px; text-decoration: none; align-items: center; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                            <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>"
                                style="width: 100px; height: 90px; object-fit: cover; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.3s;">
                            <div style="flex: 1;">
                                <h4
                                    style="font-size: 16px; font-weight: 800; color: #1e293b; margin: 0 0 8px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; transition: color 0.2s;">
                                    <?php echo $post['title']; ?></h4>
                                <span
                                    style="font-size: 13px; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 5px;"><i
                                        data-feather="clock" style="width: 12px;"></i>
                                    <?php echo format_date($post['created_at']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Web Push Subscription Banner -->
        <?php
        $onesignal_app_identifier = get_setting('onesignal_app_id', '');
        if (!empty($onesignal_app_identifier)):
            ?>
            <section id="push-subscription-banner"
                style="display: none; background: linear-gradient(135deg, var(--primary), #4338ca); color: #fff; border-radius: 16px; margin-bottom: 40px; box-shadow: 0 15px 30px rgba(0,0,0,0.15); position: relative; overflow: hidden;">
                <div
                    style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 10%, transparent 10.01%); background-size: 20px 20px; opacity: 0.5; pointer-events: none;">
                </div>
                <div
                    style="position: relative; z-index: 1; padding: 25px 35px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                    <div style="display: flex; align-items: center; gap: 20px; flex: 1; min-width: 300px;">
                        <div
                            style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 50%; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                            <i data-feather="bell" style="width: 28px; height: 28px;"></i>
                        </div>
                        <div>
                            <h3
                                style="font-size: 20px; font-weight: 800; margin: 0 0 5px 0; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                Never Miss Breaking News!</h3>
                            <p style="font-size: 14px; opacity: 0.9; margin: 0; line-height: 1.4;">Subscribe to our push
                                notifications for top stories and exclusive updates.</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <button onclick="subscribeToWebPush()" class="btn-subscribe"
                            style="background: #fff; color: var(--primary); border: none; padding: 12px 24px; font-size: 14px; font-weight: 800; border-radius: 30px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s; white-space: nowrap;">
                            <i data-feather="zap" style="width: 14px;"></i>
                            Subscribe Now
                        </button>
                        <button onclick="dismissWebPushBanner()" class="btn-dismiss-push"
                            style="background: transparent; border: 2px solid rgba(255,255,255,0.4); color: #fff; padding: 10px 20px; font-size: 14px; font-weight: 600; border-radius: 30px; cursor: pointer; transition: all 0.3s; white-space: nowrap;">
                            Later
                        </button>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Top 10 Section Redesign -->
        <section
            style="margin-bottom: 60px; background: #fff; padding: 35px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h3
                    style="font-size: 22px; font-weight: 800; color: #0f172a; display:flex; align-items:center; gap:12px; text-transform:uppercase;">
                    <div
                        style="background: linear-gradient(135deg, var(--primary), #ef4444); color: #fff; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 16px; font-weight: 800; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);">
                        10</div>
                    Top 10 Stories
                </h3>
            </div>

            <div class="top-10-scroll"
                style="display: grid; grid-template-columns: repeat(10, 1fr); gap: 25px; overflow-x: auto; padding-bottom: 15px; scroll-snap-type: x mandatory;">
                <?php foreach ($top_10 as $index => $post):
                    $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
                    ?>
                    <div style="min-width: 220px; scroll-snap-align: start;">
                        <a href="<?php echo $post_url; ?>" class="top-10-card"
                            style="text-decoration: none; color: inherit; display: block;">
                            <div
                                style="position: relative; margin-bottom: 15px; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                                <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>" alt=""
                                    style="width: 100%; aspect-ratio: 4/3; object-fit: cover; transition: transform 0.4s ease;">
                                <div
                                    style="position: absolute; bottom: 0; left: 0; background: linear-gradient(135deg, var(--primary), #ef4444); color: #fff; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px; border-top-right-radius: 12px;">
                                    <?php echo $index + 1; ?>
                                </div>
                            </div>
                            <h4
                                style="font-size: 15px; font-weight: 800; line-height: 1.4; color: #1e293b; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; transition: color 0.2s;">
                                <?php echo $post['title']; ?>
                            </h4>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Featured Category Sections -->
        <?php if (!empty($featured_categories)): ?>
            <?php foreach ($featured_categories as $fcat):
                $stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
                                       FROM posts p 
                                       JOIN post_categories pc ON p.id = pc.post_id 
                                       JOIN categories c ON pc.category_id = c.id AND c.status = 'active'
                                       WHERE p.status = 'published' AND p.published_at <= NOW() AND pc.category_id = ?
                                       GROUP BY p.id ORDER BY p.published_at DESC LIMIT 3");
                $stmt->execute([$fcat['id']]);
                $cat_posts = $stmt->fetchAll();

                if (count($cat_posts) > 0):
                    ?>
                    <section
                        style="margin-bottom: 60px; background: #fff; padding: 35px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
                            <h3
                                style="font-size: 24px; font-weight: 800; color: #0f172a; text-transform:uppercase; display:flex; align-items:center; gap:12px;">
                                <div
                                    style="background: <?php echo $fcat['color']; ?>; color: #fff; padding: 8px; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px <?php echo $fcat['color']; ?>40;">
                                    <i data-feather="<?php echo $fcat['icon']; ?>" style="width: 20px; height: 20px;"></i>
                                </div>
                                <?php echo $fcat['name']; ?>
                            </h3>
                            <a href="<?php echo BASE_URL; ?>category/<?php echo $fcat['slug']; ?>" class="view-all-btn"
                                style="font-size: 14px; font-weight: 800; color: <?php echo $fcat['color']; ?>; text-decoration: none; display:flex; align-items:center; gap:5px; padding: 8px 16px; background: <?php echo $fcat['color']; ?>15; border-radius: 20px; transition: all 0.3s;">
                                View All <i data-feather="arrow-right" style="width:16px;"></i>
                            </a>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
                            <?php foreach ($cat_posts as $post):
                                $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
                                ?>
                                <article class="cat-grid-card"
                                    style="background: #f8fafc; border-radius: 16px; overflow: hidden; border: 1px solid #f1f5f9; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); display:flex; flex-direction:column;">
                                    <a href="<?php echo $post_url; ?>"
                                        style="text-decoration:none; color:inherit; display:flex; flex-direction:column; height:100%;">
                                        <div style="position: relative; overflow: hidden;">
                                            <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>"
                                                style="width: 100%; aspect-ratio: 16/9; object-fit: cover; transition: transform 0.5s ease;">
                                            <div
                                                style="position:absolute; top:15px; left:15px; background:<?php echo $fcat['color']; ?>; color:#fff; padding:6px 14px; border-radius:20px; font-size:11px; font-weight:800; text-transform:uppercase; box-shadow:0 4px 10px rgba(0,0,0,0.3);">
                                                <?php echo $fcat['name']; ?>
                                            </div>
                                        </div>
                                        <div style="padding: 25px; flex:1; display:flex; flex-direction:column;">
                                            <h4
                                                style="font-size: 18px; font-weight: 800; line-height: 1.4; margin-bottom: 12px; color: #0f172a; transition: color 0.2s;">
                                                <?php echo $post['title']; ?></h4>
                                            <p style="font-size: 15px; color: #64748b; line-height: 1.6; margin-bottom: 20px; flex:1;">
                                                <?php echo get_post_excerpt($post, 18); ?></p>
                                            <div
                                                style="font-size: 13px; color: #94a3b8; font-weight: 600; display:flex; align-items:center; gap:6px;">
                                                <i data-feather="calendar" style="width:14px;"></i>
                                                <?php echo format_date($post['created_at']); ?>
                                            </div>
                                        </div>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php
                endif;
            endforeach;
        endif; ?>

        <!-- Main Content with Sidebar -->
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 40px;">
            <!-- Left: Latest News -->
            <section>
                <div
                    style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
                    <h3
                        style="font-size: 24px; font-weight: 800; color: #0f172a; text-transform:uppercase; display: flex; align-items: center; gap: 10px;">
                        <i data-feather="clock" style="color: var(--primary);"></i> LATEST NEWS
                    </h3>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
                    <?php foreach ($latest_news as $post):
                        $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
                        $names = explode(',', $post['cat_names']);
                        $colors = explode(',', $post['cat_colors']);
                        ?>
                        <article class="latest-card"
                            style="background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; padding: 15px; display: flex; flex-direction: column; transition: all 0.3s ease;">
                            <a href="<?php echo $post_url; ?>"
                                style="text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%;">
                                <div style="position: relative; overflow: hidden; border-radius: 10px; margin-bottom: 15px;">
                                    <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>" alt=""
                                        style="width: 100%; aspect-ratio: 16/9; object-fit: cover; transition: transform 0.4s ease;">
                                </div>
                                <div
                                    style="font-size: 12px; font-weight: 800; margin-bottom: 10px; color: <?php echo $colors[0] ?? 'var(--primary)'; ?>; text-transform: uppercase;">
                                    <?php echo $names[0] ?? 'News'; ?>
                                </div>
                                <h4
                                    style="font-size: 18px; font-weight: 800; margin-bottom: 10px; line-height: 1.4; color: #0f172a; flex: 1;">
                                    <?php echo $post['title']; ?></h4>
                                <p style="font-size: 14px; color: #64748b; margin-bottom: 15px; line-height: 1.5;">
                                    <?php echo get_post_excerpt($post, 15); ?></p>
                                <div
                                    style="font-size: 12px; color: #94a3b8; font-weight: 600; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                                    <?php echo format_date($post['created_at']); ?>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Right: Sidebar -->
            <aside style="display: flex; flex-direction: column; gap: 30px;">
                <!-- Poll Section -->
                <?php if ($active_poll): ?>
                    <div class="poll-widget"
                        style="background: white; border-radius: 16px; padding: 25px; border: 1px solid #f1f5f9; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                        <h4
                            style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px; font-size: 16px; font-weight: 800; text-transform: uppercase; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                            <div style="background: rgba(99, 102, 241, 0.1); padding: 6px; border-radius: 6px; color: #6366f1;">
                                <i data-feather="pie-chart" style="width: 16px;"></i>
                            </div>
                            Poll of the Day
                        </h4>
                        <div style="font-size: 17px; font-weight: 700; color: #1e293b; margin-bottom: 20px; line-height: 1.4;">
                            <?php echo htmlspecialchars($active_poll['question']); ?>
                        </div>

                        <div id="poll-container-<?php echo $active_poll['id']; ?>">
                            <?php if ($poll_has_voted): ?>
                                <!-- Results View -->
                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    <?php foreach ($poll_options as $opt):
                                        $pct = $poll_total_votes > 0 ? round(($opt['votes_count'] / $poll_total_votes) * 100) : 0;
                                        ?>
                                        <div style="margin-bottom: 5px;">
                                            <div
                                                style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 600; color: #475569; margin-bottom: 5px;">
                                                <span><?php echo htmlspecialchars($opt['option_text']); ?></span>
                                                <span><?php echo $pct; ?>%</span>
                                            </div>
                                            <div style="background: #f1f5f9; height: 8px; border-radius: 4px; overflow: hidden;">
                                                <div
                                                    style="width: <?php echo $pct; ?>%; height: 100%; background: #6366f1; border-radius: 4px; transition: width 1s ease-out;">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div
                                    style="margin-top: 20px; font-size: 12px; color: #94a3b8; font-weight: 600; text-align: center;">
                                    Total Votes: <?php echo number_format($poll_total_votes); ?>
                                </div>
                            <?php else: ?>
                                <!-- Voting View -->
                                <form id="poll-form-<?php echo $active_poll['id']; ?>"
                                    onsubmit="submitPoll(event, <?php echo $active_poll['id']; ?>)">
                                    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
                                        <?php foreach ($poll_options as $opt): ?>
                                            <label
                                                style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all 0.2s;">
                                                <input type="radio" name="poll_option" value="<?php echo $opt['id']; ?>" required
                                                    style="accent-color: #6366f1; width: 16px; height: 16px; cursor: pointer;">
                                                <span
                                                    style="font-size: 14px; font-weight: 600; color: #334155;"><?php echo htmlspecialchars($opt['option_text']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="submit"
                                        style="width: 100%; background: #6366f1; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; transition: background 0.2s;">
                                        Vote Now
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <script>
                        function submitPoll(e, pollId) {
                            e.preventDefault();
                            const form = document.getElementById('poll-form-' + pollId);
                            const formData = new FormData(form);
                            const optionId = formData.get('poll_option');
                            if (!optionId) return;

                            const btn = form.querySelector('button');
                            btn.disabled = true;
                            btn.innerHTML = 'Voting...';

                            fetch('<?php echo BASE_URL; ?>api/api_poll_vote.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'poll_id=' + pollId + '&option_id=' + optionId
                            })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) {
                                        window.location.reload(); // Quickest way to show updated results
                                    } else {
                                        alert(data.message);
                                        btn.disabled = false;
                                        btn.innerHTML = 'Vote Now';
                                    }
                                })
                                .catch(err => {
                                    alert('An error occurred. Please try again.');
                                    btn.disabled = false;
                                    btn.innerHTML = 'Vote Now';
                                });
                        }
                    </script>
                <?php endif; ?>

                <!-- Photo of the Day Widget -->
                <?php if (get_setting('photo_of_day_image')): ?>
                    <div class="photo-of-day-widget"
                        style="background: white; border-radius: 16px; padding: 25px; border: 1px solid #f1f5f9; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                        <h4
                            style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px; font-size: 16px; font-weight: 800; text-transform: uppercase; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                            <div style="background: rgba(249, 115, 22, 0.1); padding: 6px; border-radius: 6px; color: #f97316;">
                                <i data-feather="image" style="width: 16px;"></i>
                            </div>
                            Photo of the Day
                        </h4>

                        <div
                            style="border-radius: 12px; overflow: hidden; margin-bottom: 15px; border: 1px solid #e2e8f0; position: relative; cursor: pointer;">
                            <img src="<?php echo BASE_URL; ?>assets/images/<?php echo get_setting('photo_of_day_image'); ?>"
                                alt="<?php echo htmlspecialchars(get_setting('photo_of_day_title', 'Photo of the Day')); ?>"
                                style="width: 100%; height: auto; max-height: 250px; object-fit: cover; transition: transform 0.3s ease; display: block;"
                                onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        </div>

                        <?php if (get_setting('photo_of_day_title')): ?>
                            <h5 style="margin: 0 0 8px 0; font-size: 15px; font-weight: 800; color: #1e293b; line-height: 1.4;">
                                <?php echo htmlspecialchars(get_setting('photo_of_day_title')); ?>
                            </h5>
                        <?php endif; ?>

                        <?php if (get_setting('photo_of_day_caption')): ?>
                            <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.6; font-weight: 500;">
                                <?php echo nl2br(htmlspecialchars(get_setting('photo_of_day_caption'))); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Today's Activity & Events Section -->
                <div
                    style="background: white; border-radius: 16px; padding: 25px; border: 1px solid #f1f5f9; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                    <h4
                        style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 25px; font-size: 16px; font-weight: 800; text-transform: uppercase; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                        <div style="background: #f8fafc; padding: 6px; border-radius: 6px; color: var(--primary);">
                            <i data-feather="calendar" style="width: 16px;"></i>
                        </div>
                        EVENTS OF THE DAY
                    </h4>

                    <div style="position: relative; padding-left: 20px;">
                        <!-- Vertical Line -->
                        <div style="position: absolute; left: 4px; top: 0; bottom: 0; width: 2px; background: #e2e8f0;">
                        </div>

                        <?php
                        $timeline_stmt = $pdo->query("SELECT * FROM timeline WHERE event_date = CURDATE() ORDER BY event_time ASC");
                        $timeline_items = $timeline_stmt->fetchAll();
                        $now = date('H:i');

                        if ($timeline_items):
                            foreach ($timeline_items as $item):
                                $color = '#f59e0b'; // Upcoming
                                if ($item['event_time'] < $now) {
                                    $color = '#10b981'; // Completed
                                } elseif ($item['event_time'] == $now) {
                                    $color = '#ef4444'; // Ongoing / Live
                                }
                                ?>
                                <!-- Timeline Item -->
                                <div style="position: relative; margin-bottom: 25px; padding-left: 10px;">
                                    <span
                                        style="position: absolute; left: -26px; top: 4px; width: 14px; height: 14px; background: <?php echo $color; ?>; border: 3px solid white; border-radius: 50%; box-shadow: 0 0 0 2px <?php echo $color; ?>40; z-index: 1; <?php echo ($item['event_time'] == $now) ? 'animation: pulse 1s infinite;' : ''; ?>"></span>
                                    <div
                                        style="font-size: 12px; font-weight: 800; color: <?php echo $color; ?>; text-transform: uppercase; margin-bottom: 4px;">
                                        <?php echo date("h:i A", strtotime($item['event_time'])); ?></div>
                                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($item['event_name']); ?></div>
                                    <div style="font-size: 13px; font-weight: 500; color: #64748b; line-height: 1.5;">
                                        <?php echo htmlspecialchars($item['description']); ?></div>
                                </div>
                                <?php
                            endforeach;
                        else: ?>
                            <div style="text-align: center; padding: 20px 0;">
                                <i data-feather="clock" style="width: 32px; color: #e2e8f0; margin-bottom: 15px;"></i>
                                <p style="font-size: 14px; color: #94a3b8; font-weight: 500;">No events scheduled for today.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ad in sidebar -->
                <div style="text-align: center;">
                    <?php echo display_ad('sidebar', $pdo); ?>
                </div>

                <!-- Popular News -->
                <div
                    style="background: white; border-radius: 16px; padding: 25px; border: 1px solid #f1f5f9; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                    <h4
                        style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 25px; font-size: 16px; font-weight: 800; text-transform: uppercase; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                        <div style="background: #f8fafc; padding: 6px; border-radius: 6px; color: #ef4444;">
                            <i data-feather="trending-up" style="width: 16px;"></i>
                        </div>
                        MOST POPULAR
                    </h4>
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <?php
                        $popular = $pdo->query("SELECT * FROM posts WHERE status = 'published' ORDER BY views DESC LIMIT 5")->fetchAll();
                        foreach ($popular as $index => $tp):
                            $tp_url = ($tp['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $tp['id'] : BASE_URL . "article/" . $tp['slug'];
                            ?>
                            <a href="<?php echo $tp_url; ?>" class="popular-card"
                                style="display: flex; gap: 15px; text-decoration: none; align-items: center;">
                                <div
                                    style="width: 30px; font-size: 24px; font-weight: 800; color: #e2e8f0; font-style: italic;">
                                    <?php echo $index + 1; ?></div>
                                <div style="width: 70px; height: 70px; flex-shrink: 0; border-radius: 10px; overflow: hidden;">
                                    <img src="<?php echo get_post_thumbnail($tp['featured_image']); ?>"
                                        style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div style="flex: 1;">
                                    <h5
                                        style="font-size: 14px; margin: 0 0 6px 0; line-height: 1.4; font-weight: 800; color: #0f172a; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo $tp['title']; ?></h5>
                                    <div
                                        style="font-size: 11px; color: #94a3b8; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                        <i data-feather="eye" style="width: 12px;"></i>
                                        <?php echo number_format($tp['views']); ?> views
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        </div>
    </main>
<?php endif; ?>

<style>
    /* Styling & Animations for New UI */
    @keyframes pulse {
        0% {
            opacity: 1;
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
        }

        70% {
            opacity: 0.5;
            transform: scale(1.1);
            box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
        }

        100% {
            opacity: 1;
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
        }
    }

    .tag-pill {
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        padding: 6px 14px;
        background: #f8fafc;
        border-radius: 20px;
        transition: all 0.3s;
        border: 1px solid #f1f5f9;
    }

    .tag-pill:hover {
        background: var(--primary);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .btn-subscribe:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3) !important;
    }

    .btn-dismiss-push:hover {
        background: rgba(255, 255, 255, 0.1) !important;
        border-color: #fff !important;
    }

    .hero-slider-container:hover .hero-img {
        transform: scale(1.03);
    }

    .slider-btn:hover {
        background: #fff !important;
        color: var(--primary) !important;
        transform: scale(1.1);
    }

    .breaking-card:first-child {
        border-top: none;
        padding-top: 0;
    }

    .breaking-card img {
        transition: transform 0.3s;
    }

    .breaking-card:hover h4 {
        color: var(--primary) !important;
    }

    .breaking-card:hover img {
        transform: scale(1.05);
    }

    .top-10-scroll::-webkit-scrollbar {
        display: none;
    }

    .top-10-card:hover img {
        transform: scale(1.05);
    }

    .top-10-card:hover h4 {
        color: var(--primary) !important;
    }

    .cat-grid-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08) !important;
        border-color: transparent !important;
    }

    .cat-grid-card:hover img {
        transform: scale(1.04);
    }

    .cat-grid-card:hover h4 {
        color: var(--primary) !important;
    }

    .view-all-btn:hover {
        background: var(--primary) !important;
        color: #fff !important;
    }

    .latest-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        border-color: transparent;
    }

    .latest-card:hover img {
        transform: scale(1.03);
    }

    .latest-card:hover h4 {
        color: var(--primary) !important;
    }

    .popular-card:hover h5 {
        color: var(--primary) !important;
    }

    .btn-subscribe:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3) !important;
    }

    @media (max-width: 1024px) {
        .hero-grid {
            grid-template-columns: 1fr !important;
        }

        .hero-main-card {
            height: 400px !important;
        }

        div[style*="grid-template-columns: 1fr 350px"] {
            grid-template-columns: 1fr !important;
        }

        aside {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }
    }

    @media (max-width: 768px) {
        div[style*="grid-template-columns: repeat(3, 1fr)"] {
            grid-template-columns: 1fr !important;
        }

        div[style*="grid-template-columns: repeat(2, 1fr)"] {
            grid-template-columns: 1fr !important;
        }

        aside {
            grid-template-columns: 1fr !important;
        }
    }

    /* ══ THEME 2 RESPONSIVE RULES ═══════════════════════════════════ */

    /* Theme 2 main container: reduce padding on small screens */
    .t2-theme-main {
        padding: clamp(15px, 4vw, 40px) !important;
    }

    /* ── Bento Hero Grid ── */
    @media (max-width: 900px) {
        .t2-bento-grid {
            grid-template-columns: 1fr !important;
            min-height: auto !important;
        }
        /* Make the left big card a fixed height on tablet */
        .t2-bento-grid > a.t2-bento-card {
            height: 380px !important;
        }
        /* Right stacked column: horizontal row */
        .t2-bento-grid > div {
            flex-direction: row !important;
        }
        .t2-bento-grid > div > a.t2-bento-card {
            min-height: 220px !important;
        }
    }

    @media (max-width: 600px) {
        /* Stack everything vertically */
        .t2-bento-grid > div {
            flex-direction: column !important;
        }
        .t2-bento-grid > a.t2-bento-card {
            height: 280px !important;
        }
        .t2-bento-grid > div > a.t2-bento-card {
            min-height: 180px !important;
        }
        /* Reduce hero text size on mobile */
        .t2-bento-grid > a.t2-bento-card h2 {
            font-size: 20px !important;
        }
        .t2-bento-grid > a.t2-bento-card > div {
            padding: 20px !important;
        }
    }

    /* ── Top 10 Stories — Theme 2 ── */
    .t2-top-10-scroll {
        scroll-snap-type: x mandatory;
    }
    @media (max-width: 768px) {
        .t2-top-10-scroll {
            grid-template-columns: repeat(5, 1fr) !important;
        }
        .t2-top-10-scroll > div {
            min-width: 160px !important;
        }
    }
    @media (max-width: 520px) {
        .t2-top-10-scroll {
            grid-template-columns: repeat(10, 1fr) !important;
        }
        .t2-top-10-scroll > div {
            min-width: 140px !important;
        }
    }

    /* ── Split Category Row — Theme 2 ── */
    @media (max-width: 900px) {
        .t2-split-row {
            grid-template-columns: 1fr !important;
        }
    }

    /* ── Main Grid (Latest News + Sidebar) — Theme 2 ── */
    @media (max-width: 1024px) {
        .t2-main-grid {
            grid-template-columns: 1fr !important;
        }
        .t2-main-grid > aside {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 20px;
        }
    }
    @media (max-width: 640px) {
        .t2-main-grid > aside {
            grid-template-columns: 1fr !important;
        }
    }

    /* ── Latest News Horizontal Card — Theme 2 ── */
    @media (max-width: 640px) {
        .t2-horizontal-flex {
            flex-direction: column !important;
        }
        .t2-horizontal-flex > div:first-child {
            width: 100% !important;
            aspect-ratio: 16/9;
        }
        .t2-latest-horizontal-card {
            padding: 12px !important;
        }
    }

    /* ── Category Section Heading on small screens ── */
    @media (max-width: 480px) {
        .t2-split-row .t2-split-item {
            flex-direction: column;
        }
        .t2-split-row .t2-split-item img {
            width: 100% !important;
            height: 140px !important;
        }
    }

    /* ── Sidebar widget: fix on mobile ── */
    @media (max-width: 640px) {
        .t2-sidebar-widget {
            padding: 18px !important;
        }
    }

</style>

<script>
    // Hero Slider Logic
    let currentSlide = 0;
    const track = document.querySelector('.slider-track');
    const slides = document.querySelectorAll('.hero-slide');
    const totalSlides = slides.length;
    let slideInterval;

    function showSlide(index) {
        if (!track) return;
        currentSlide = (index + totalSlides) % totalSlides;
        track.style.transform = `translateX(-${currentSlide * 100}%)`;
    }

    function nextSlide(e) {
        if (e) { e.preventDefault(); e.stopPropagation(); resetInterval(); }
        showSlide(currentSlide + 1);
    }

    function prevSlide(e) {
        if (e) { e.preventDefault(); e.stopPropagation(); resetInterval(); }
        showSlide(currentSlide - 1);
    }

    function startInterval() {
        if (totalSlides > 1) {
            slideInterval = setInterval(nextSlide, 5000); // 5 seconds
        }
    }

    function resetInterval() {
        clearInterval(slideInterval);
        startInterval();
    }

    startInterval();

    document.addEventListener("DOMContentLoaded", function () {
        if (!localStorage.getItem('webPushDismissed')) {
            window.OneSignalDeferred = window.OneSignalDeferred || [];
            OneSignalDeferred.push(async function (OneSignal) {
                const isSupported = OneSignal.Notifications.isPushSupported();
                if (isSupported) {
                    const hasPermission = OneSignal.Notifications.permission === "granted";
                    if (!hasPermission) {
                        document.getElementById('push-subscription-banner').style.display = 'block';
                    }
                }
            });
        }
    });

    function subscribeToWebPush() {
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        OneSignalDeferred.push(async function (OneSignal) {
            await OneSignal.Slidedown.promptPush();
            document.getElementById('push-subscription-banner').style.display = 'none';
        });
    }

    function dismissWebPushBanner() {
        localStorage.setItem('webPushDismissed', 'true');
        document.getElementById('push-subscription-banner').style.display = 'none';
    }
</script>

<?php include 'includes/public_footer.php'; ?>