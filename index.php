<?php
include 'includes/public_header.php';

// YouTube ID extractor
function yt_id($url) {
    $url = trim($url);
    if (preg_match('/(?:v=|youtu\.be\/|embed\/|live\/)([a-zA-Z0-9_-]{11})/', $url, $m)) return $m[1];
    return null;
}

// 1. Fetch Featured Post (Lead Story)
$stmt = $pdo->query("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors, GROUP_CONCAT(c.slug) as cat_slugs 
                     FROM posts p 
                     JOIN post_categories pc ON p.id = pc.post_id 
                     JOIN categories c ON pc.category_id = c.id 
                     WHERE p.status = 'published' AND p.is_featured = 1 AND p.published_at <= NOW()
                     GROUP BY p.id ORDER BY p.published_at DESC LIMIT 1");
$featured = $stmt->fetch();

if (!$featured) {
    $stmt = $pdo->query("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors, GROUP_CONCAT(c.slug) as cat_slugs 
                         FROM posts p 
                         JOIN post_categories pc ON p.id = pc.post_id 
                         JOIN categories c ON pc.category_id = c.id 
                         WHERE p.status = 'published' AND p.published_at <= NOW()
                         GROUP BY p.id ORDER BY p.published_at DESC LIMIT 1");
    $featured = $stmt->fetch();
}

$featured_id = $featured ? $featured['id'] : 0;

// 2. Fetch Top 10 Posts (by views)
$stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
                        FROM posts p 
                        JOIN post_categories pc ON p.id = pc.post_id 
                        JOIN categories c ON pc.category_id = c.id 
                        WHERE p.status = 'published' AND p.id != ? AND p.published_at <= NOW()
                        GROUP BY p.id ORDER BY p.views DESC LIMIT 10");
$stmt->execute([$featured_id]);
$top_10 = $stmt->fetchAll();

// 3. Fetch Breaking News (latest 6)
$stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
                        FROM posts p 
                        JOIN post_categories pc ON p.id = pc.post_id 
                        JOIN categories c ON pc.category_id = c.id 
                        WHERE p.status = 'published' AND p.id != ? AND p.published_at <= NOW()
                        GROUP BY p.id ORDER BY p.published_at DESC LIMIT 6");
$stmt->execute([$featured_id]);
$breaking_news_latest = $stmt->fetchAll();

// 4. Fetch Latest News for main grid
$exclude_ids = array_merge([$featured_id], array_column($top_10, 'id'), array_column($breaking_news_latest, 'id'));
$placeholders = $exclude_ids ? str_repeat('?,', count($exclude_ids) - 1) . '?' : '0';
$stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
                        FROM posts p 
                        JOIN post_categories pc ON p.id = pc.post_id 
                        JOIN categories c ON pc.category_id = c.id 
                        WHERE p.status = 'published' AND p.id NOT IN ($placeholders) AND p.published_at <= NOW()
                        GROUP BY p.id ORDER BY p.published_at DESC LIMIT 12");
$stmt->execute($exclude_ids ?: []);
$latest_news = $stmt->fetchAll();

// Trending Tags / Categories
$stmt = $pdo->query("SELECT name, slug, color, icon FROM categories WHERE status = 'active' LIMIT 10");
$categories_list = $stmt->fetchAll();

// 5. Live Stream Status
$live_enabled = get_setting('live_youtube_enabled') === '1';
$live_url     = get_setting('live_youtube_url');
$live_title   = get_setting('live_stream_title', 'Watch Live');
$live_vid_id  = $live_url ? yt_id($live_url) : null;
?>

<main class="content-container">
    <!-- Trending Bar -->
    <div class="trending-tags">
        <span class="trending-label">TRENDING</span>
        <?php foreach($categories_list as $tag): ?>
            <a href="<?php echo BASE_URL; ?>category/<?php echo $tag['slug']; ?>" class="tag-item" style="border-left: 3px solid <?php echo $tag['color']; ?>; display: flex; align-items: center; gap: 5px;">
                <i data-feather="<?php echo $tag['icon']; ?>" style="width: 12px; height: 12px; color: <?php echo $tag['color']; ?>;"></i>
                <?php echo $tag['name']; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Bhaskar Home Hero (Lead Story Section) -->
    <section class="bhaskar-hero">
        <div class="main-feature">
            <div style="border-bottom: 2px solid var(--primary); padding-bottom: 10px; margin-bottom: 20px;">
                <h3 style="font-size: 18px; font-weight: 800; color: var(--primary); margin:0;">LEAD STORY</h3>
            </div>
            <?php if ($featured): 
                $post_url = ($featured['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $featured['id'] : BASE_URL . "article/" . $featured['slug'];
            ?>
            <a href="<?php echo $post_url; ?>" <?php echo ($featured['external_type'] != 'none') ? 'target="_blank"' : ''; ?>>
                <div style="position: relative;">
                    <img src="<?php echo get_post_thumbnail($featured['featured_image']); ?>" alt="" style="aspect-ratio: 16/9; object-fit: cover;">
                    <?php if ($featured['video_url']): ?>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255, 60, 0, 0.85); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                            <i data-feather="play" style="width: 30px; height: 30px; fill: white;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <h2 style="margin-top:15px;"><?php echo $featured['title']; ?></h2>
            </a>
            <p style="color: #666; font-size: 16px;"><?php echo get_excerpt($featured['excerpt'], 30); ?></p>
            <?php endif; ?>
        </div>

        <div class="sub-features">
            <div style="border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">
                <h3 style="font-size: 18px; font-weight: 800; color: #333; margin:0;">BREAKING NEWS</h3>
            </div>
            <?php foreach ($breaking_news_latest as $post):
                $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
            ?>
            <a href="<?php echo $post_url; ?>" class="small-card" <?php echo ($post['external_type'] != 'none') ? 'target="_blank"' : ''; ?> style="display: flex; gap: 12px; text-decoration: none; color: inherit; margin-bottom: 15px;">
                <div style="position: relative; flex-shrink: 0;">
                    <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>" alt="" style="width: 100px; height: 70px; object-fit: cover; border-radius: 4px;">
                </div>
                <div>
                    <h3 style="font-size: 14px; margin: 0 0 5px 0; line-height: 1.3; font-weight: 700; color: #1a1a1b;"><?php echo $post['title']; ?></h3>
                    <p style="font-size: 12px; color: #666; margin-bottom: 5px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        <?php echo get_excerpt($post['excerpt'], 15); ?>
                    </p>
                    <span style="color: #888; font-size: 11px;"><?php echo format_date($post['created_at']); ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- YouTube Live Stream Section -->
    <?php if ($live_enabled && $live_vid_id): ?>
    <section style="margin-bottom: 50px; background: #000; border-radius: 12px; overflow: hidden; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 0;">
            <!-- Player -->
            <div style="position: relative; padding-top: 56.25%;">
                <iframe 
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
                    src="https://www.youtube.com/embed/<?php echo $live_vid_id; ?>?autoplay=0&rel=0&modestbranding=1" 
                    title="Live Stream" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            </div>
            <!-- Live Info -->
            <div style="background: linear-gradient(135deg, #1a1a1b 0%, #000 100%); padding: 30px; display: flex; flex-direction: column; justify-content: center;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <span style="background: #ff0000; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 900; letter-spacing: 1px; animation: pulse 1.5s infinite;">LIVE NOW</span>
                    <span style="color: rgba(255,255,255,0.6); font-size: 11px; font-weight: 700; text-transform: uppercase;">Direct Broadcast</span>
                </div>
                <h2 style="color: #fff; font-size: 24px; font-weight: 800; line-height: 1.3; margin: 0 0 15px 0;"><?php echo htmlspecialchars($live_title); ?></h2>
                <p style="color: rgba(255,255,255,0.7); font-size: 14px; line-height: 1.6; margin-bottom: 25px;">Stay connected with our real-time coverage. Watch the latest updates and breaking news as it happens.</p>
                <div style="display: flex; gap: 15px;">
                    <button style="background: var(--primary); border: none; color: #fff; padding: 12px 25px; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i data-feather="rss" style="width: 16px;"></i> Subscribe Now
                    </button>
                    <div style="display: flex; align-items: center; gap: 5px; color: #fff; font-size: 13px; font-weight: 600;">
                        <i data-feather="users" style="width: 14px; color: #ff0000;"></i> 1.2k Watching
                    </div>
                </div>
            </div>
        </div>
        <style>
            @keyframes pulse {
                0% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.7; transform: scale(0.95); }
                100% { opacity: 1; transform: scale(1); }
            }
        </style>
    </section>
    <?php endif; ?>

    <!-- Top 10 Section -->
    <section style="margin-bottom: 50px; background: #f8f9fa; padding: 30px; border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="font-size: 20px; font-weight: 800; color: #1a1a1b; display:flex; align-items:center; gap:10px; text-transform:uppercase;">
                <span style="background:var(--primary); color:#fff; width:30px; height:30px; display:flex; align-items:center; justify-content:center; border-radius:4px; font-size:14px;">10</span>
                Top 10 Stories
            </h3>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; overflow-x: auto; padding-bottom: 10px;">
            <?php foreach ($top_10 as $index => $post): 
                $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
            ?>
            <div style="min-width: 184px;">
                <a href="<?php echo $post_url; ?>" style="text-decoration: none; color: inherit;">
                    <div style="position: relative; margin-bottom: 10px;">
                        <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>" alt="" style="width: 100%; aspect-ratio: 3/2; object-fit: cover; border-radius: 6px;">
                        <div style="position: absolute; top: 0; left: 0; background: var(--primary); color: #fff; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; border-bottom-right-radius: 8px; border-top-left-radius: 6px; box-shadow: 2px 2px 8px rgba(0,0,0,0.2);">
                            <?php echo $index + 1; ?>
                        </div>
                    </div>
                    <h4 style="font-size: 13px; font-weight: 700; line-height: 1.3; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                        <?php echo $post['title']; ?>
                    </h4>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Main Content with Sidebar -->
    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 40px;">
        <!-- Left: Latest News -->
        <section>
            <div style="border-top: 2px solid #333; padding-top: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 18px; font-weight: 800; color: #1a1a1b; text-transform:uppercase;">
                    LATEST NEWS
                </h3>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px;">
                <?php foreach ($latest_news as $post): 
                    $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
                ?>
                <article style="border-bottom: 1px solid #eee; padding-bottom: 20px;">
                    <a href="<?php echo $post_url; ?>">
                        <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>" alt="" style="width: 100%; aspect-ratio: 16/9; object-fit: cover; border-radius: 8px; margin-bottom: 12px;">
                        <h4 style="font-size: 17px; font-weight: 700; margin-bottom: 8px; line-height: 1.4; color: #003399;"><?php echo $post['title']; ?></h4>
                    </a>
                    <p style="font-size: 14px; color: #666; margin-bottom: 10px;"><?php echo get_excerpt($post['excerpt'], 20); ?></p>
                    <div style="font-size: 12px; color: #888; font-weight: 600;">
                        <?php 
                            $names = explode(',', $post['cat_names']);
                            $colors = explode(',', $post['cat_colors']);
                        ?>
                        <span style="color: <?php echo $colors[0]; ?>;"><?php echo strtoupper($names[0]); ?></span> | 
                        <span><?php echo format_date($post['created_at']); ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Right: Sidebar -->
        <aside>
            <!-- Ad in sidebar -->
            <div style="margin-bottom: 40px; text-align: center;">
                <?php echo display_ad('sidebar', $pdo); ?>
            </div>

            <!-- Popular News -->
            <div>
                <h4 style="border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 20px; font-size: 16px; font-weight: 800; text-transform: uppercase;">
                    MOST POPULAR
                </h4>
                <?php 
                    $popular = $pdo->query("SELECT * FROM posts WHERE status = 'published' ORDER BY views DESC LIMIT 5")->fetchAll();
                    foreach($popular as $tp):
                         $tp_url = ($tp['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $tp['id'] : BASE_URL . "article/" . $tp['slug'];
                ?>
                <a href="<?php echo $tp_url; ?>" style="display: flex; gap: 12px; text-decoration: none; color: inherit; margin-bottom: 20px; group">
                    <div style="width: 80px; height: 60px; flex-shrink: 0;">
                        <img src="<?php echo get_post_thumbnail($tp['featured_image']); ?>" style="width: 100%; height: 100%; border-radius: 6px; object-fit: cover;">
                    </div>
                    <div>
                        <h5 style="font-size: 13px; margin: 0 0 5px 0; line-height: 1.4; font-weight: 700;"><?php echo $tp['title']; ?></h5>
                        <p style="font-size: 11px; color: #666; margin-bottom: 5px; line-height: 1.4;"><?php echo get_excerpt($tp['excerpt'], 10); ?></p>
                        <div style="font-size: 10px; color: #888;">
                            <i data-feather="eye" style="width: 10px; height: 10px; vertical-align: middle;"></i> <?php echo number_format($tp['views']); ?> views
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>
</main>

<style>
    .tag-item:hover { background: #fff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .small-card:hover h3 { color: var(--primary); }
    .news-card:hover h4 { color: var(--primary); }
    aside a:hover { background: #fff !important; color: var(--primary) !important; transform: translateX(5px); box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    
    @media (max-width: 1024px) {
        div[style*="grid-template-columns: 1fr 300px"] {
            grid-template-columns: 1fr !important;
        }
        aside {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 40px;
        }
    }
    @media (max-width: 640px) {
        aside {
            grid-template-columns: 1fr;
        }
        div[style*="grid-template-columns: repeat(2, 1fr)"] {
            grid-template-columns: 1fr !important;
        }
        div[style*="grid-template-columns: repeat(5, 1fr)"] {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }
</style>

<?php include 'includes/public_footer.php'; ?>
