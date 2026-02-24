<?php
include 'includes/public_header.php';

// YouTube ID extractor
function yt_id($url) {
    $url = trim($url);
    if (preg_match('/(?:v=|youtu\.be\/|embed\/|live\/)([a-zA-Z0-9_-]{11})/', $url, $m)) return $m[1];
    return null;
}

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
    <?php
    // Compute live settings here (used to decide hero vs live)
    $live_enabled = get_setting('live_youtube_enabled') === '1';
    $live_url     = get_setting('live_youtube_url');
    $live_vid_id  = $live_url ? yt_id($live_url) : null;
    $live_title   = get_setting('live_stream_title', 'Watch Live');
    $show_live    = $live_enabled && $live_vid_id;
    ?>

    <section class="bhaskar-hero">

        <!-- ── LEFT: Live Player (replaces featured) OR Normal Hero ── -->
        <?php if ($show_live): ?>
        <div class="main-feature live-hero-slot">

            <!-- Live header bar -->
            <div class="live-section-header">
                <div class="live-badge-wrap">
                    <span class="live-pulse-ring"></span>
                    <span class="live-dot"></span>
                    <span class="live-label">LIVE</span>
                </div>
                <h2 class="live-title"><?php echo htmlspecialchars($live_title); ?></h2>
                <a href="<?php echo htmlspecialchars($live_url); ?>" target="_blank" rel="noopener" class="live-yt-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-2.75 12.21 12.21 0 0 0-7.64 0 4.83 4.83 0 0 1-3.77 2.75A13 13 0 0 0 2 12c0 4.42 2.53 8.24 6.41 9.31A4.83 4.83 0 0 1 12 24a4.83 4.83 0 0 1 3.59-2.69C19.47 20.24 22 16.42 22 12a13 13 0 0 0-2.41-5.31z"/><path fill="#fff" d="M10 15.5v-7l6 3.5-6 3.5z"/></svg>
                    Watch on YouTube
                </a>
            </div>

            <!-- YouTube embed (autoplay muted) -->
            <div class="live-player-wrap" style="border-radius:0; margin:0;">
                <iframe
                    src="https://www.youtube.com/embed/<?php echo htmlspecialchars($live_vid_id); ?>?autoplay=1&mute=1&loop=1&playlist=<?php echo htmlspecialchars($live_vid_id); ?>&rel=0&modestbranding=1&playsinline=1&controls=0&disablekb=1"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    title="<?php echo htmlspecialchars($live_title); ?>"
                ></iframe>
                <!-- Transparent click-blocker overlay -->
                <div class="live-shield"></div>
                <div class="live-corner-badge"><span class="live-dot-sm"></span>LIVE</div>
            </div>

            <!-- Breaking news ticker below player -->
            <?php if ($featured): ?>
            <div class="live-ticker">
                <span class="live-ticker-label">LATEST</span>
                <a href="<?php echo ($featured['external_type']!='none') ? BASE_URL.'click_tracker.php?post_id='.$featured['id'] : BASE_URL.'article/'.$featured['slug']; ?>" class="live-ticker-text">
                    <?php echo htmlspecialchars($featured['title']); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php else: ?>

        <!-- Normal featured hero -->
        <?php if ($featured): ?>
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
        <?php endif; ?>

        <?php endif; // end live/normal toggle ?>

        <!-- ── RIGHT: Sub-featured always shows ── -->
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

    <!-- Live section extra styles -->
    <?php if ($show_live): ?>
    <style>
        .live-hero-slot { padding: 0 !important; overflow: hidden; border-radius: 10px; display: flex; flex-direction: column; }
        .live-section-header {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 16px;
            background: linear-gradient(90deg,#0f172a 0%,#1e1b4b 50%,#0f172a 100%);
            flex-wrap: wrap;
        }
        .live-badge-wrap { position: relative; display: flex; align-items: center; gap: 6px; }
        .live-pulse-ring {
            position: absolute; left: -3px; top: -3px;
            width: 18px; height: 18px; border-radius: 50%;
            border: 2px solid #dc2626;
            animation: livePulse 1.4s ease-out infinite;
        }
        @keyframes livePulse {
            0%   { transform:scale(1);   opacity:.8; }
            70%  { transform:scale(1.9); opacity:0; }
            100% { transform:scale(1.9); opacity:0; }
        }
        .live-dot { width:10px; height:10px; border-radius:50%; background:#dc2626; animation:liveBlink 1.1s ease-in-out infinite; flex-shrink:0; }
        @keyframes liveBlink { 0%,100%{opacity:1;} 50%{opacity:.2;} }
        .live-label { font-size:11px; font-weight:900; color:#dc2626; letter-spacing:.1em; }
        .live-title { font-size:14px; font-weight:800; color:#f1f5f9; margin:0; flex:1; }
        .live-yt-link {
            display:flex; align-items:center; gap:6px;
            font-size:11px; font-weight:700; color:#fff;
            background:#dc2626; padding:6px 12px; border-radius:7px;
            text-decoration:none; transition:.18s; white-space:nowrap;
        }
        .live-yt-link:hover { background:#b91c1c; }
        .live-player-wrap { position:relative; width:100%; aspect-ratio:16/9; flex:1; }
        .live-player-wrap iframe { width:100%; height:100%; border:none; display:block; }
        .live-corner-badge {
            position:absolute; top:10px; left:10px;
            background:rgba(220,38,38,.9); color:#fff;
            font-size:10px; font-weight:900; padding:3px 8px; border-radius:5px;
            display:flex; align-items:center; gap:4px; pointer-events:none;
        }
        .live-dot-sm { width:6px; height:6px; border-radius:50%; background:#fff; animation:liveBlink 1s infinite; }
        /* Transparent control blocker */
        .live-shield {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            z-index: 2;
            cursor: default;
        }
        /* Keep LIVE badge above shield */
        .live-corner-badge { z-index: 3; }
        /* Ticker below player */
        .live-ticker {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 14px;
            background: #0f172a; border-top: 1px solid rgba(220,38,38,.3);
        }
        .live-ticker-label {
            background: var(--primary); color: #fff;
            font-size: 10px; font-weight: 900;
            padding: 2px 8px; border-radius: 4px;
            letter-spacing:.06em; white-space:nowrap;
        }
        .live-ticker-text {
            font-size: 13px; font-weight: 600; color: #e2e8f0;
            text-decoration: none;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .live-ticker-text:hover { color: #dc2626; }
    </style>
    <?php endif; ?>


    <!-- News Grid Selection -->
    <section>
        <div style="border-top: 2px solid var(--primary); padding-top: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 800; color: var(--primary); display:flex; align-items:center; gap:8px; text-transform:uppercase; letter-spacing:.05em;">
                <span style="display:inline-block;width:3px;height:18px;background:var(--primary);border-radius:2px;"></span>
                Top Stories
            </h3>
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
