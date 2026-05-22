<?php
$page_title = "Dashboard";
include 'includes/header.php';

// ── Core Stats ───────────────────────────────────────────────
$total_posts      = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$published_posts  = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
$draft_posts      = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories WHERE status = 'active'")->fetchColumn();
$total_views      = $pdo->query("SELECT COALESCE(SUM(views),0) FROM posts")->fetchColumn();
$total_users      = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$unread_msgs      = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'new'")->fetchColumn();
$today_posts      = $pdo->query("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$active_polls     = $pdo->query("SELECT COUNT(*) FROM polls WHERE status = 'active'")->fetchColumn();

// ── Advertisement stats ───────────────────────────────────────
$total_ad_views   = $pdo->query("SELECT COALESCE(SUM(impressions),0) FROM ads")->fetchColumn();
$total_ad_clicks  = $pdo->query("SELECT COALESCE(SUM(clicks),0) FROM ads")->fetchColumn();
$top_ads          = $pdo->query("SELECT * FROM ads ORDER BY impressions DESC LIMIT 5")->fetchAll();

// ── Top viewed posts ──────────────────────────────────────────
$top_posts = $pdo->query("
    SELECT p.id, p.title, p.views, p.status, p.published_at, p.slug,
           GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') as cats,
           GROUP_CONCAT(c.color ORDER BY c.name SEPARATOR ',') as colors
    FROM posts p
    LEFT JOIN post_categories pc ON p.id = pc.post_id
    LEFT JOIN categories c ON pc.category_id = c.id
    WHERE p.status = 'published'
    GROUP BY p.id
    ORDER BY p.views DESC LIMIT 5
")->fetchAll();

// ── Recent Posts ──────────────────────────────────────────────
$recent_posts = $pdo->query("
    SELECT p.id, p.title, p.status, p.created_at, p.views, p.slug,
           GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') as cats,
           GROUP_CONCAT(c.color ORDER BY c.name SEPARATOR ',') as colors
    FROM posts p
    LEFT JOIN post_categories pc ON p.id = pc.post_id
    LEFT JOIN categories c ON pc.category_id = c.id
    GROUP BY p.id
    ORDER BY p.created_at DESC LIMIT 6
")->fetchAll();

// ── Categories with post counts ───────────────────────────────
$cat_stats = $pdo->query("
    SELECT c.name, c.color, c.icon, COUNT(pc.post_id) as cnt
    FROM categories c
    LEFT JOIN post_categories pc ON c.id = pc.category_id
    LEFT JOIN posts p ON pc.post_id = p.id AND p.status = 'published'
    WHERE c.status = 'active'
    GROUP BY c.id ORDER BY cnt DESC LIMIT 6
")->fetchAll();
$max_cnt = max(array_column($cat_stats, 'cnt') ?: [1]);

// ── Recent Feedback ───────────────────────────────────────────
$recent_feedback = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 4")->fetchAll();

// ── Live Stream Status ───────────────────────────────────────
$live_enabled    = get_setting('live_youtube_enabled') === '1';
$live_url        = get_setting('live_youtube_url');
$live_title      = get_setting('live_stream_title', 'Watch Live');

// Quick toggle from dashboard
if (isset($_GET['live_toggle'])) {
    $new_val = $_GET['live_toggle'] === 'on' ? '1' : '0';
    $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES ('live_youtube_enabled',?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$new_val,$new_val]);
    redirect('admin/dashboard.php', 'Live stream ' . ($new_val === '1' ? 'enabled' : 'disabled') . '!');
}

// Helper
function dash_yt_id($url) {
    if (!$url) return null;
    if (preg_match('/(?:v=|youtu\.be\/|embed\/|live\/)([a-zA-Z0-9_-]{11})/', $url, $m)) return $m[1];
    return null;
}
$live_vid_id = dash_yt_id($live_url);
?>

<?php
// re-open php for PHP output already closed by include header
?>

<!-- ══════════════════ LIVE STREAM BANNER ══════════════════ -->
<?php if ($live_url): ?>
<div class="dash-live-banner <?php echo $live_enabled ? 'live-on' : 'live-off'; ?>">

    <!-- Left: status + info -->
    <div class="dlb-info">
        <div class="dlb-badge-row">
            <?php if ($live_enabled): ?>
                <span class="dlb-pulse-ring"></span>
                <span class="dlb-dot"></span>
                <span class="dlb-live-text">LIVE NOW</span>
            <?php else: ?>
                <span class="dlb-off-dot"></span>
                <span class="dlb-off-text">STREAM OFF</span>
            <?php endif; ?>
        </div>
        <h2 class="dlb-title"><?php echo htmlspecialchars($live_title); ?></h2>
        <p class="dlb-url"><?php echo htmlspecialchars(substr($live_url, 0, 60)) . (strlen($live_url) > 60 ? '…' : ''); ?></p>

        <div class="dlb-actions">
            <?php if ($live_enabled): ?>
                <a href="?live_toggle=off" class="dlb-btn dlb-btn-off">
                    <i data-feather="stop-circle" style="width: 14px;"></i> Stop Live
                </a>
            <?php else: ?>
                <a href="?live_toggle=on" class="dlb-btn dlb-btn-on">
                    <i data-feather="play-circle" style="width: 14px;"></i> Go Live
                </a>
            <?php endif; ?>
            <a href="settings.php?tab=livestream" class="dlb-btn dlb-btn-settings">
                <i data-feather="settings" style="width: 14px;"></i> Configuration
            </a>
            <?php if ($live_enabled): ?>
                <a href="<?php echo BASE_URL; ?>" target="_blank" class="dlb-btn dlb-btn-view">
                    <i data-feather="external-link" style="width: 14px;"></i> View Site
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: mini preview -->
    <?php if ($live_vid_id): ?>
    <div class="dlb-preview">
        <iframe
            src="https://www.youtube.com/embed/<?php echo htmlspecialchars($live_vid_id); ?>?autoplay=0&mute=1&rel=0&modestbranding=1&controls=0&disablekb=1"
            style="width:100%;height:100%;border:none;"
            title="Live Preview"
        ></iframe>
        <!-- Transparent click-blocker -->
        <div style="position:absolute;inset:0;width:100%;height:100%;background:transparent;z-index:2;cursor:default;"></div>
        <?php if ($live_enabled): ?>
        <div class="dlb-corner-live" style="z-index:3;"><span class="dlb-dot-sm"></span>LIVE</div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="dash-live-banner live-empty">
    <div class="dlb-info">
        <div class="dlb-badge-row"><span class="dlb-off-dot"></span><span class="dlb-off-text">NO STREAM CONFIGURED</span></div>
        <h2 class="dlb-title">YouTube Live Stream</h2>
        <p style="color:#94a3b8;font-size:13px;margin:6px 0 14px;">Set up a live stream URL to broadcast directly on your homepage with an animated Live badge.</p>
        <a href="settings.php" onclick="setTimeout(()=>showTab&&showTab('livestream'),500)" class="dlb-btn dlb-btn-on">
            <i data-feather="plus-circle" style="width: 14px;"></i> Configure Live Stream
        </a>
    </div>
    <div class="dlb-preview" style="background:#f8fafc;display:flex;align-items:center;justify-content:center;">
        <i data-feather="video-off" style="width: 64px; height: 64px; color: #cbd5e1; stroke-width: 1;"></i>
    </div>
</div>
<?php endif; ?>

<style>
.dash-live-banner {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 0;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1);
    min-height: 180px;
}
.live-on  { background: linear-gradient(135deg,#0f172a 0%,#1e1b4b 60%,#0f172a 100%); border: 1px solid rgba(220,38,38,.2); }
.live-off { background: linear-gradient(135deg,#1e293b 0%,#0f172a 100%); border: 1px solid #334155; }
.live-empty { background: white; border: 1px dashed #e2e8f0; }
.live-empty .dlb-info { color: #334155; }

.dlb-info {
    padding: 30px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 4px;
}
.dlb-badge-row {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 8px;
    position: relative;
}
.dlb-pulse-ring {
    position: absolute;
    left: -3px; top: -3px;
    width: 18px; height: 18px;
    border-radius: 50%;
    border: 2px solid #dc2626;
    animation: dlbPulse 1.4s ease-out infinite;
}
@keyframes dlbPulse {
    0%   { transform:scale(1);   opacity:.9; }
    70%  { transform:scale(2);   opacity:0; }
    100% { transform:scale(2);   opacity:0; }
}
.dlb-dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: #dc2626;
    animation: dlbBlink 1s ease-in-out infinite;
    flex-shrink: 0;
}
@keyframes dlbBlink {
    0%,100% { opacity:1; }
    50%     { opacity:.2; }
}
.dlb-live-text {
    font-size: 11px; font-weight: 900;
    color: #dc2626;
    letter-spacing: .15em;
}
.dlb-off-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #475569;
    flex-shrink: 0;
}
.dlb-off-text {
    font-size: 11px; font-weight: 700;
    color: #64748b;
    letter-spacing: .08em;
}
.dlb-title {
    font-size: 22px; font-weight: 800;
    color: #f1f5f9;
    margin: 0 0 6px;
    line-height: 1.2;
}
.live-empty .dlb-title { color: #0f172a; }
.dlb-url { font-size: 12px; color: #94a3b8; margin: 0 0 18px; word-break: break-all; font-family: monospace; }
.dlb-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.dlb-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 16px; border-radius: 10px;
    font-size: 13px; font-weight: 700;
    text-decoration: none;
    transition: all 0.2s;
    white-space: nowrap;
}
.dlb-btn-on  { background:#dc2626; color:#fff; box-shadow: 0 4px 12px rgba(220,38,38,0.3); }
.dlb-btn-on:hover { background:#b91c1c; transform: translateY(-1px); }
.dlb-btn-off { background:rgba(255,255,255,.1); color:#f1f5f9; border:1px solid rgba(255,255,255,.15); }
.dlb-btn-off:hover { background:rgba(255,255,255,.18); transform: translateY(-1px); }
.dlb-btn-settings { background:rgba(255,255,255,.05); color:#94a3b8; border:1px solid rgba(255,255,255,.08); }
.dlb-btn-settings:hover { background:rgba(255,255,255,.12); color:#f1f5f9; }
.dlb-btn-view { background:#16a34a; color:#fff; }
.dlb-btn-view:hover { background:#15803d; transform: translateY(-1px); }

.dlb-preview {
    position: relative;
    background: #000;
    min-height: 180px;
}
.dlb-corner-live {
    position: absolute; top:12px; left:12px;
    background: rgba(220,38,38,.9);
    color: #fff;
    font-size: 10px; font-weight: 900;
    padding: 4px 10px;
    border-radius: 6px;
    display: flex; align-items: center; gap: 5px;
    pointer-events: none;
}
.dlb-dot-sm {
    width:6px; height:6px; border-radius:50%;
    background:#fff;
    animation: dlbBlink 1s infinite;
}
@media (max-width:1024px) {
    .dash-live-banner { grid-template-columns:1fr; }
    .dlb-preview { min-height: 220px; }
}
</style>

<?php
// Stats start below
?>

<!-- ═══════════════════════════ STATS ROW ═══════════════════════════ -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 28px;">

    <div class="stat-card" style="position: relative; overflow: hidden;">
        <div style="display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 2;">
            <div>
                <p style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Articles</p>
                <div style="font-size: 32px; font-weight: 900; color: #0f172a; line-height: 1;"><?php echo number_format($published_posts); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 8px; font-weight: 500;"><?php echo $draft_posts; ?> drafts pending</p>
            </div>
            <div style="background: rgba(99,102,241,.1); color: var(--primary); width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="file-text" style="width: 26px;"></i>
            </div>
        </div>
        <div style="margin-top: 15px; height: 5px; background: #f1f5f9; border-radius: 10px; position: relative; z-index: 2;">
            <div style="height: 5px; background: var(--primary); border-radius: 10px; width: <?php echo $total_posts > 0 ? round(($published_posts/$total_posts)*100) : 0; ?>%; box-shadow: 0 0 10px rgba(99,102,241,0.3);"></div>
        </div>
    </div>

    <div class="stat-card" style="position: relative; overflow: hidden;">
        <div style="display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 2;">
            <div>
                <p style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Total Views</p>
                <div style="font-size: 32px; font-weight: 900; color: #0f172a; line-height: 1;"><?php echo number_format($total_views); ?></div>
                <p style="font-size: 12px; color: #10b981; margin-top: 8px; font-weight: 700; display: flex; align-items: center; gap: 4px;">
                    <i data-feather="arrow-up-right" style="width: 12px;"></i> <?php echo $today_posts; ?> today
                </p>
            </div>
            <div style="background: rgba(16,185,129,.1); color: #10b981; width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="trending-up" style="width: 26px;"></i>
            </div>
        </div>
    </div>

    <div class="stat-card" style="position: relative; overflow: hidden;">
        <div style="display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 2;">
            <div>
                <p style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Categories</p>
                <div style="font-size: 32px; font-weight: 900; color: #0f172a; line-height: 1;"><?php echo number_format($total_categories); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 8px; font-weight: 500;">Active sections</p>
            </div>
            <div style="background: rgba(245,158,11,.1); color: #f59e0b; width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="layers" style="width: 26px;"></i>
            </div>
        </div>
    </div>

    <div class="stat-card" style="position: relative; overflow: hidden;">
        <div style="display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 2;">
            <div>
                <p style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Inbox</p>
                <div style="font-size: 32px; font-weight: 900; color: #0f172a; line-height: 1;"><?php echo number_format($unread_msgs); ?></div>
                <p style="font-size: 12px; color: #ef4444; margin-top: 8px; font-weight: 700;">New messages</p>
            </div>
            <div style="background: rgba(239,68,68,.1); color: #ef4444; width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="mail" style="width: 26px;"></i>
            </div>
        </div>
    </div>

    <div class="stat-card" style="position: relative; overflow: hidden;">
        <div style="display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 2;">
            <div>
                <p style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Ad Impressions</p>
                <div style="font-size: 32px; font-weight: 900; color: #0f172a; line-height: 1;"><?php echo number_format($total_ad_views); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 8px; font-weight: 500;"><?php echo $total_ad_clicks; ?> clicks</p>
            </div>
            <div style="background: rgba(99,102,241,.1); color: var(--primary); width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="bar-chart-2" style="width: 26px;"></i>
            </div>
        </div>
    </div>

    <div class="stat-card" style="position: relative; overflow: hidden;">
        <div style="display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 2;">
            <div>
                <p style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Active Polls</p>
                <div style="font-size: 32px; font-weight: 900; color: #0f172a; line-height: 1;"><?php echo number_format($active_polls); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 8px; font-weight: 500;">Currently running</p>
            </div>
            <div style="background: rgba(16,185,129,.1); color: #10b981; width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="pie-chart" style="width: 26px;"></i>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════ MAIN GRID ═══════════════════════════ -->
<div class="admin-grid">

    <!-- LEFT COLUMN -->
    <div class="admin-main-col" style="display: flex; flex-direction: column; gap: 30px;">

        <!-- Top Performing Articles -->
        <div class="stat-card" style="padding: 0; overflow: hidden; border: 1px solid var(--border);">
            <div style="padding: 22px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fcfcfd;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 10px;">
                        <i data-feather="award" style="width: 18px; color: #f59e0b;"></i>
                        Top Performing Articles
                    </h3>
                </div>
                <a href="posts.php" class="btn" style="font-size: 12px; padding: 6px 12px; background: #f1f5f9; color: #475569;">All Reports</a>
            </div>
            <div style="overflow-x: auto;">
                <table class="content-table" style="margin-top: 0;">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">#</th>
                            <th>Article Title</th>
                            <th>Category</th>
                            <th>Engagement</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_posts)): ?>
                            <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 50px;">No published articles yet.</td></tr>
                        <?php else: ?>
                        <?php foreach ($top_posts as $i => $post):
                            $cats = explode(',', $post['cats'] ?? '');
                            $colors = explode(',', $post['colors'] ?? '');
                        ?>
                        <tr onmouseover="this.style.background='#fbfcfe'" onmouseout="this.style.background='transparent'" style="transition: background 0.2s;">
                            <td style="text-align: center; font-weight: 800; color: <?php echo $i === 0 ? '#f59e0b' : ($i === 1 ? '#94a3b8' : ($i === 2 ? '#92400e' : '#cbd5e1')); ?>; font-size: 15px;">
                                <?php echo $i + 1; ?>
                            </td>
                            <td style="max-width: 300px;">
                                <a href="<?php echo BASE_URL; ?>article/<?php echo $post['slug']; ?>" target="_blank" style="font-weight: 700; color: var(--text-main); text-decoration: none; font-size: 14px; display: block; margin-bottom: 4px;"><?php echo htmlspecialchars($post['title']); ?></a>
                                <span style="font-size: 11px; color: #94a3b8;">Published: <?php echo date('M d, Y', strtotime($post['published_at'])); ?></span>
                            </td>
                            <td>
                                <span style="background: <?php echo ($colors[0] ?? '#6366f1'); ?>15; color: <?php echo $colors[0] ?? '#6366f1'; ?>; padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo htmlspecialchars($cats[0] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; height: 6px; background: #f1f5f9; border-radius: 10px; max-width: 70px;">
                                        <div style="height: 6px; background: var(--primary); border-radius: 10px; width: <?php echo $top_posts[0]['views'] > 0 ? round(($post['views']/$top_posts[0]['views'])*100) : 0; ?>%; box-shadow: 0 0 8px rgba(99,102,241,0.2);"></div>
                                    </div>
                                    <span style="font-weight: 800; font-size: 14px; color: var(--text-main);"><?php echo number_format($post['views']); ?></span>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <a href="post_edit.php?id=<?php echo $post['id']; ?>" class="btn" style="padding: 5px 10px; background: #eff6ff; color: #2563eb; font-size: 12px;">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Two Column Secondary Row -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
            <!-- Recently Added -->
            <div class="stat-card" style="padding: 0; overflow: hidden; border: 1px solid var(--border);">
                <div style="padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 15px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i data-feather="clock" style="width: 16px; color: #6366f1;"></i>
                        Recent Activity
                    </h3>
                    <a href="post_add.php" style="font-size: 12px; color: var(--primary); font-weight: 700; text-decoration: none;">+ New</a>
                </div>
                <div style="padding: 5px 0;">
                <?php foreach ($recent_posts as $post):
                    $cols = explode(',', $post['colors'] ?? '#6366f1');
                ?>
                <div style="padding: 15px 20px; display: flex; gap: 15px; align-items: center; border-bottom: 1px solid #f8fafc; transition: background 0.2s;" onmouseover="this.style.background='#fcfcfd'" onmouseout="this.style.background='transparent'">
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: <?php echo $cols[0]; ?>; flex-shrink: 0; box-shadow: 0 0 0 3px <?php echo $cols[0]; ?>20;"></div>
                    <div style="flex: 1; min-width: 0;">
                        <a href="post_edit.php?id=<?php echo $post['id']; ?>" style="font-size: 13px; font-weight: 700; color: var(--text-main); text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px;"><?php echo htmlspecialchars($post['title']); ?></a>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="badge-<?php echo $post['status']; ?>" style="font-size: 10px; font-weight: 800; text-transform: uppercase;"><?php echo $post['status']; ?></span>
                            <span style="font-size: 11px; color: #94a3b8;"><?php echo date('M d', strtotime($post['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>

            <!-- Ad Performance -->
            <div class="stat-card" style="padding: 0; overflow: hidden; border: 1px solid var(--border);">
                <div style="padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 15px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i data-feather="bar-chart" style="width: 16px; color: #ef4444;"></i>
                        Ad Pulse
                    </h3>
                    <a href="ads.php" style="font-size: 12px; color: #ef4444; font-weight: 700; text-decoration: none;">Stats</a>
                </div>
                <div style="padding: 5px 0;">
                <?php foreach ($top_ads as $ad): 
                    $ctr = $ad['impressions'] > 0 ? round(($ad['clicks']/$ad['impressions'])*100, 1) : 0;
                ?>
                <div style="padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f8fafc;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-weight: 700; color: var(--text-main); font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($ad['name']); ?></div>
                        <span style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 700;"><?php echo str_replace('_', ' ', $ad['location']); ?></span>
                    </div>
                    <div style="text-align: right; flex-shrink: 0;">
                        <div style="font-weight: 800; color: #0f172a; font-size: 14px;"><?php echo $ctr; ?>% <span style="font-size: 10px; color: #94a3b8; font-weight: 500;">CTR</span></div>
                        <div style="font-size: 11px; color: #64748b;"><?php echo number_format($ad['impressions']); ?> views</div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="admin-sidebar-col" style="display: flex; flex-direction: column; gap: 30px;">

        <!-- Quick Actions -->
        <div class="stat-card" style="background: #0f172a; color: white; border: none; padding: 25px;">
            <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i data-feather="zap" style="width: 18px; color: #f59e0b;"></i>
                Quick Command
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <a href="post_add.php" class="btn btn-primary" style="justify-content: center; flex-direction: column; height: 80px; gap: 8px;">
                    <i data-feather="plus" style="width: 20px;"></i> <span>Article</span>
                </a>
                <a href="categories.php" class="btn" style="background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); justify-content: center; flex-direction: column; height: 80px; gap: 8px;">
                    <i data-feather="layers" style="width: 20px;"></i> <span>Category</span>
                </a>
                <a href="polls.php" class="btn" style="background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); justify-content: center; flex-direction: column; height: 80px; gap: 8px;">
                    <i data-feather="pie-chart" style="width: 20px;"></i> <span>Polls</span>
                </a>
                <a href="settings.php" class="btn" style="background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); justify-content: center; flex-direction: column; height: 80px; gap: 8px;">
                    <i data-feather="settings" style="width: 20px;"></i> <span>Settings</span>
                </a>
            </div>
            <a href="<?php echo BASE_URL; ?>" target="_blank" class="btn" style="width: 100%; margin-top: 15px; background: #16a34a; color: white; justify-content: center;">
                <i data-feather="external-link" style="width: 16px;"></i> View Main Website
            </a>
        </div>

        <!-- Category Distribution -->
        <div class="stat-card" style="padding: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px;">
                <h3 style="font-size: 16px; font-weight: 800; margin: 0; color: var(--text-main);">📊 Section Pulse</h3>
            </div>
            <?php foreach ($cat_stats as $cat): ?>
            <div style="margin-bottom: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 30px; height: 30px; border-radius: 8px; background: <?php echo $cat['color']; ?>10; display: flex; align-items: center; justify-content: center;">
                            <i data-feather="<?php echo $cat['icon']; ?>" style="width: 14px; color: <?php echo $cat['color']; ?>;"></i>
                        </div>
                        <span style="font-size: 14px; font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($cat['name']); ?></span>
                    </div>
                    <span style="font-size: 13px; font-weight: 800; color: #64748b;"><?php echo $cat['cnt']; ?></span>
                </div>
                <div style="height: 6px; background: #f1f5f9; border-radius: 10px;">
                    <div style="height: 6px; background: <?php echo $cat['color']; ?>; border-radius: 10px; width: <?php echo $max_cnt > 0 ? round(($cat['cnt']/$max_cnt)*100) : 0; ?>%; box-shadow: 0 0 8px <?php echo $cat['color']; ?>30;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Messages -->
        <div class="stat-card" style="padding: 0; overflow: hidden; border: 1px solid var(--border);">
            <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: var(--text-main);">💬 Feedback</h3>
                <a href="feedback.php" style="font-size: 12px; color: var(--primary); font-weight: 700; text-decoration: none;">Open Inbox</a>
            </div>
            <div style="max-height: 350px; overflow-y: auto;">
                <?php if (empty($recent_feedback)): ?>
                    <p style="padding: 30px; color: #94a3b8; font-size: 13px; text-align: center;">Clean inbox! No messages.</p>
                <?php else: ?>
                <?php foreach ($recent_feedback as $msg): ?>
                <a href="feedback.php?view=<?php echo $msg['id']; ?>" style="display: flex; gap: 15px; padding: 18px 25px; border-bottom: 1px solid #f8fafc; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#fbfcfe'" onmouseout="this.style.background='transparent'">
                    <div style="width: 40px; height: 40px; border-radius: 12px; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 16px; flex-shrink: 0; box-shadow: 0 4px 10px rgba(99,102,241,0.2);">
                        <?php echo strtoupper(substr($msg['name'], 0, 1)); ?>
                    </div>
                    <div style="min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
                            <span style="font-size: 14px; font-weight: 800; color: var(--text-main);"><?php echo htmlspecialchars($msg['name']); ?></span>
                            <?php if ($msg['status'] === 'new'): ?>
                                <span style="background: #ef4444; width: 6px; height: 6px; border-radius: 50%;"></span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.4;"><?php echo htmlspecialchars(substr($msg['message'], 0, 50)); ?>...</div>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
