<?php
if (!file_exists('includes/config.php')) {
    header("Location: install.php");
    exit;
}
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

// 3. Fetch Breaking News (latest 4)
$stmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(c.name) as cat_names, GROUP_CONCAT(c.color) as cat_colors 
                        FROM posts p 
                        JOIN post_categories pc ON p.id = pc.post_id 
                        JOIN categories c ON pc.category_id = c.id 
                        WHERE p.status = 'published' AND p.id != ? AND p.published_at <= NOW()
                        GROUP BY p.id ORDER BY p.published_at DESC LIMIT 4");
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

// Trending Tags
$categories_list = get_all_tags($pdo, 12);

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
            <a href="<?php echo BASE_URL; ?>tag/<?php echo $tag['slug']; ?>" class="tag-item">
                #<?php echo $tag['name']; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Bhaskar Home Hero (Lead Story Section) -->
    <section class="bhaskar-hero">
        <div class="main-feature">
            <?php if ($live_enabled && $live_vid_id): ?>
                <div style="border-bottom: 2px solid #ff0000; padding-bottom: 10px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                    <h3 style="font-size: 18px; font-weight: 800; color: #ff0000; margin:0; display: flex; align-items: center; gap: 8px;">
                        <span style="width: 10px; height: 10px; background: #ff0000; border-radius: 50%; animation: pulse 1s infinite;"></span>
                        LIVE BROADCAST
                    </h3>
                    <span style="font-size: 11px; font-weight: 700; color: #666; text-transform: uppercase;">Real-time Coverage</span>
                </div>
                <?php 
                    $stream_sound = get_setting('live_stream_sound', '0') === '1' ? '0' : '1'; 
                ?>
                <div style="background: #000; border-radius: 12px; overflow: hidden; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <div style="position: relative; padding-top: 56.25%;">
                        <iframe 
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
                            src="https://www.youtube.com/embed/<?php echo $live_vid_id; ?>?autoplay=1&mute=<?php echo $stream_sound; ?>&rel=0&modestbranding=1&controls=0&disablekb=1" 
                            title="Live Stream" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                        </iframe>
                        <!-- Transparent Overlay to block controls -->
                        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10; cursor: default; background: transparent;"></div>
                    </div>
                </div>
                <h2 style="font-size: 24px; font-weight: 800; line-height: 1.3; color: #1a1a1b; margin-top: 15px;"><?php echo htmlspecialchars($live_title); ?></h2>
                <p style="color: #666; font-size: 15px; line-height: 1.6; margin-top: 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">Stay updated with our direct live broadcast. Witness the news as it unfolds on the ground.</p>
            <?php else: ?>
                <div style="border-bottom: 2px solid var(--primary); padding-bottom: 10px; margin-bottom: 20px;">
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--primary); margin:0;">LEAD STORY</h3>
                </div>
                <?php if ($featured): 
                    $post_url = ($featured['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $featured['id'] : BASE_URL . "article/" . $featured['slug'];
                ?>
                <a href="<?php echo $post_url; ?>" <?php echo ($featured['external_type'] != 'none') ? 'target="_blank"' : ''; ?>>
                    <div style="position: relative;">
                        <img src="<?php echo get_post_thumbnail($featured['featured_image']); ?>" alt="" style="aspect-ratio: 16/9; object-fit: cover; border-radius: 8px;">
                        <?php if ($featured['video_url']): ?>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255, 60, 0, 0.85); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                <i data-feather="play" style="width: 30px; height: 30px; fill: white;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h2 style="margin-top:15px; font-size: 28px; line-height: 1.2; font-weight: 800;"><?php echo $featured['title']; ?></h2>
                </a>
                <p style="color: #666; font-size: 16px; margin-top: 10px; line-height: 1.6;"><?php echo get_excerpt($featured['excerpt'], 30); ?></p>
                <?php endif; ?>
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

    <style>
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.9); }
            100% { opacity: 1; transform: scale(1); }
        }
    </style>

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
            <!-- Today's Activity & Events Section -->
            <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #f1f5f9; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 30px;">
                <h4 style="border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; font-size: 15px; font-weight: 800; text-transform: uppercase; color: #1a1a1b; display: flex; align-items: center; gap: 8px;">
                    <i data-feather="calendar" style="width: 16px; color: var(--primary);"></i> 
                    TODAY'S TIMELINE
                </h4>
                
                <div style="position: relative; padding-left: 20px;">
                    <!-- Vertical Line -->
                    <div style="position: absolute; left: 4px; top: 0; bottom: 0; width: 2px; background: #f1f5f9;"></div>

                    <?php 
                    $timeline_stmt = $pdo->query("SELECT * FROM timeline ORDER BY event_time ASC");
                    $timeline_items = $timeline_stmt->fetchAll();
                    $now = date('H:i');
                    
                    if ($timeline_items):
                        foreach($timeline_items as $item):
                            // Automatic Status Logic
                            $color = '#f59e0b'; // Upcoming
                            if ($item['event_time'] < $now) {
                                $color = '#10b981'; // Completed
                            } elseif ($item['event_time'] == $now) {
                                $color = '#ef4444'; // Ongoing / Live
                            }
                    ?>
                    <!-- Timeline Item -->
                    <div style="position: relative; margin-bottom: 20px;">
                        <span style="position: absolute; left: -20px; top: 4px; width: 10px; height: 10px; background: <?php echo $color; ?>; border: 2px solid white; box-shadow: 0 0 0 4px <?php echo $color; ?>22; z-index: 1; <?php echo ($item['event_time'] == $now) ? 'animation: pulse 1s infinite;' : ''; ?>"></span>
                        <div style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;"><?php echo date("h:i A", strtotime($item['event_time'])); ?></div>
                        <div style="font-size: 13px; font-weight: 700; color: #1e293b; line-height: 1.4;"><?php echo $item['description']; ?></div>
                    </div>
                    <?php endforeach; else: ?>
                    <div style="text-align: center; padding: 20px 0;">
                        <i data-feather="clock" style="width: 24px; color: #cbd5e1; margin-bottom: 10px;"></i>
                        <p style="font-size: 12px; color: #94a3b8;">No updates for today yet.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <a href="#" style="display: block; text-align: center; font-size: 12px; font-weight: 700; color: var(--primary); margin-top: 10px; text-decoration: none;">View Full Calendar <i data-feather="chevron-right" style="width: 12px;"></i></a>
            </div>

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
