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
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    Stop Live
                </a>
            <?php else: ?>
                <a href="?live_toggle=on" class="dlb-btn dlb-btn-on">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8" fill="currentColor" stroke="none"/></svg>
                    Go Live
                </a>
            <?php endif; ?>
            <a href="settings.php?tab=livestream" class="dlb-btn dlb-btn-settings">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Settings
                </a>
            <?php if ($live_enabled): ?>
                <a href="<?php echo BASE_URL; ?>" target="_blank" class="dlb-btn dlb-btn-view">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    View on Site
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
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Configure Live Stream
        </a>
    </div>
    <div class="dlb-preview" style="background:#f8fafc;display:flex;align-items:center;justify-content:center;">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
    </div>
</div>
<?php endif; ?>

<style>
.dash-live-banner {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 0;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    min-height: 160px;
}
.live-on  { background: linear-gradient(135deg,#0f172a 0%,#1e1b4b 60%,#0f172a 100%); border: 1.5px solid rgba(220,38,38,.35); }
.live-off { background: linear-gradient(135deg,#1e293b 0%,#0f172a 100%); border: 1.5px solid #334155; }
.live-empty { background: white; border: 1.5px dashed #e2e8f0; }
.live-empty .dlb-info { color: #334155; }

.dlb-info {
    padding: 22px 26px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 4px;
}
.dlb-badge-row {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 6px;
    position: relative;
}
/* Pulsing ring */
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
    letter-spacing: .12em;
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
    font-size: 18px; font-weight: 800;
    color: #f1f5f9;
    margin: 0 0 4px;
    line-height: 1.3;
}
.live-empty .dlb-title { color: #0f172a; }
.dlb-url { font-size: 11px; color: #64748b; margin: 0 0 14px; word-break: break-all; }
.dlb-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.dlb-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 8px;
    font-size: 12px; font-weight: 700;
    text-decoration: none;
    transition: .18s;
    white-space: nowrap;
}
.dlb-btn-on  { background:#dc2626; color:#fff; }
.dlb-btn-on:hover { background:#b91c1c; }
.dlb-btn-off { background:rgba(255,255,255,.1); color:#f1f5f9; border:1px solid rgba(255,255,255,.15); }
.dlb-btn-off:hover { background:rgba(255,255,255,.18); }
.dlb-btn-settings { background:rgba(255,255,255,.08); color:#94a3b8; border:1px solid rgba(255,255,255,.1); }
.dlb-btn-settings:hover { background:rgba(255,255,255,.15); color:#f1f5f9; }
.dlb-btn-view { background:#16a34a; color:#fff; }
.dlb-btn-view:hover { background:#15803d; }
/* Video preview panel */
.dlb-preview {
    position: relative;
    background: #000;
    min-height: 160px;
}
.dlb-preview iframe { display:block; }
.dlb-corner-live {
    position: absolute; top:10px; left:10px;
    background: rgba(220,38,38,.9);
    color: #fff;
    font-size: 10px; font-weight: 900;
    padding: 3px 8px;
    border-radius: 5px;
    display: flex; align-items: center; gap: 4px;
    pointer-events: none;
    letter-spacing:.06em;
}
.dlb-dot-sm {
    width:6px; height:6px; border-radius:50%;
    background:#fff;
    animation: dlbBlink 1s infinite;
}
@media (max-width:900px) {
    .dash-live-banner { grid-template-columns:1fr; }
    .dlb-preview { min-height: 200px; }
}
</style>

<?php
// Stats start below
?>

<!-- ═══════════════════════════ STATS ROW ═══════════════════════════ -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 28px;">

    <div class="stat-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <p style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px;">Published</p>
                <div style="font-size: 30px; font-weight: 900; color: #0f172a;"><?php echo number_format($published_posts); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;"><?php echo $draft_posts; ?> drafts pending</p>
            </div>
            <div style="background: rgba(99,102,241,.1); color: var(--primary); width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="file-text" style="width: 24px;"></i>
            </div>
        </div>
        <div style="margin-top: 15px; height: 4px; background: #f1f5f9; border-radius: 4px;">
            <div style="height: 4px; background: var(--primary); border-radius: 4px; width: <?php echo $total_posts > 0 ? round(($published_posts/$total_posts)*100) : 0; ?>%;"></div>
        </div>
    </div>

    <div class="stat-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <p style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px;">Total Reach</p>
                <div style="font-size: 30px; font-weight: 900; color: #0f172a;"><?php echo number_format($total_views); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;">Cumulative views</p>
            </div>
            <div style="background: rgba(16,185,129,.1); color: #10b981; width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="trending-up" style="width: 24px;"></i>
            </div>
        </div>
        <div style="margin-top: 15px; padding: 6px 10px; background: #ecfdf5; border-radius: 8px; display: inline-flex; align-items: center; gap: 5px;">
            <i data-feather="eye" style="width: 12px; color: #10b981;"></i>
            <span style="font-size: 12px; font-weight: 600; color: #059669;"><?php echo $today_posts; ?> new today</span>
        </div>
    </div>

    <div class="stat-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <p style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px;">Categories</p>
                <div style="font-size: 30px; font-weight: 900; color: #0f172a;"><?php echo number_format($total_categories); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;">Active sections</p>
            </div>
            <div style="background: rgba(245,158,11,.1); color: #f59e0b; width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="layers" style="width: 24px;"></i>
            </div>
        </div>
        <a href="categories.php" style="margin-top: 14px; display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; color: #f59e0b; text-decoration: none;">
            Manage <i data-feather="arrow-right" style="width: 12px;"></i>
        </a>
    </div>

    <div class="stat-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <p style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px;">Messages</p>
                <div style="font-size: 30px; font-weight: 900; color: #0f172a;"><?php echo number_format($unread_msgs); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;">Unread in inbox</p>
            </div>
            <div style="background: rgba(239,68,68,.1); color: #ef4444; width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="inbox" style="width: 24px;"></i>
            </div>
        </div>
        <a href="feedback.php" style="margin-top: 14px; display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; color: #ef4444; text-decoration: none;">
            View Inbox <i data-feather="arrow-right" style="width: 12px;"></i>
        </a>
    </div>

    <div class="stat-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <p style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px;">Contributors</p>
                <div style="font-size: 30px; font-weight: 900; color: #0f172a;"><?php echo number_format($total_users); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;">Journalists & Editors</p>
            </div>
            <div style="background: rgba(139,92,246,.1); color: #8b5cf6; width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="users" style="width: 24px;"></i>
            </div>
        </div>
        <a href="users.php" style="margin-top: 14px; display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; color: #8b5cf6; text-decoration: none;">
            Manage Team <i data-feather="arrow-right" style="width: 12px;"></i>
        </a>
    </div>
</div>

<!-- ═══════════════════════════ MAIN GRID ═══════════════════════════ -->
<div style="display: grid; grid-template-columns: 1fr 320px; gap: 25px; align-items: start;">

    <!-- LEFT COLUMN -->
    <div style="display: flex; flex-direction: column; gap: 25px;">

        <!-- Top Performing Articles -->
        <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;">
            <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; margin: 0;">🏆 Top Performing Articles</h3>
                    <p style="font-size: 12px; color: #94a3b8; margin: 3px 0 0;">By total views</p>
                </div>
                <a href="posts.php" class="btn btn-primary" style="font-size: 12px; padding: 7px 15px;">All Posts</a>
            </div>
            <table class="content-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Views</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_posts)): ?>
                        <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">No published posts yet.</td></tr>
                    <?php else: ?>
                    <?php foreach ($top_posts as $i => $post):
                        $cats = explode(',', $post['cats'] ?? '');
                        $colors = explode(',', $post['colors'] ?? '');
                    ?>
                    <tr>
                        <td style="font-size: 13px; font-weight: 800; color: <?php echo $i === 0 ? '#f59e0b' : ($i === 1 ? '#94a3b8' : ($i === 2 ? '#92400e' : '#cbd5e1')); ?>;">
                            <?php echo $i + 1; ?>
                        </td>
                        <td style="max-width: 250px;">
                            <a href="<?php echo BASE_URL; ?>article/<?php echo $post['slug']; ?>" target="_blank" style="font-weight: 600; color: #0f172a; text-decoration: none; font-size: 13px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo htmlspecialchars($post['title']); ?></a>
                        </td>
                        <td>
                            <span style="background: <?php echo $colors[0] ?? '#6366f1'; ?>18; color: <?php echo $colors[0] ?? '#6366f1'; ?>; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;"><?php echo htmlspecialchars($cats[0] ?? 'N/A'); ?></span>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="flex: 1; height: 6px; background: #f1f5f9; border-radius: 10px; max-width: 60px;">
                                    <div style="height: 6px; background: var(--primary); border-radius: 10px; width: <?php echo $top_posts[0]['views'] > 0 ? round(($post['views']/$top_posts[0]['views'])*100) : 0; ?>%;"></div>
                                </div>
                                <span style="font-weight: 700; font-size: 13px; color: #0f172a;"><?php echo number_format($post['views']); ?></span>
                            </div>
                        </td>
                        <td>
                            <a href="post_edit.php?id=<?php echo $post['id']; ?>" style="font-size: 12px; color: var(--primary); font-weight: 600; text-decoration: none;">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Recently Added -->
        <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;">
            <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; margin: 0;">🕒 Recently Added</h3>
                    <p style="font-size: 12px; color: #94a3b8; margin: 3px 0 0;">Latest editorial activity</p>
                </div>
                <a href="post_add.php" class="btn" style="font-size: 12px; padding: 7px 15px; background: #ecfdf5; color: #059669; font-weight: 700;">+ New Post</a>
            </div>
            <div style="padding: 5px 0;">
            <?php foreach ($recent_posts as $post):
                $cols = explode(',', $post['colors'] ?? '#6366f1');
                $cats = explode(',', $post['cats'] ?? 'Uncategorized');
            ?>
            <div style="padding: 14px 25px; display: flex; gap: 14px; align-items: center; border-bottom: 1px solid #f8fafc;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $cols[0]; ?>; flex-shrink: 0;"></div>
                <div style="flex: 1; min-width: 0;">
                    <a href="post_edit.php?id=<?php echo $post['id']; ?>" style="font-size: 14px; font-weight: 600; color: #0f172a; text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($post['title']); ?></a>
                    <span style="font-size: 11px; color: <?php echo $cols[0]; ?>; font-weight: 700;"><?php echo htmlspecialchars($cats[0]); ?></span>
                </div>
                <div style="text-align: right; flex-shrink: 0;">
                    <span class="badge badge-<?php echo $post['status']; ?>"><?php echo ucfirst($post['status']); ?></span>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;"><?php echo date('d M', strtotime($post['created_at'])); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div style="display: flex; flex-direction: column; gap: 25px;">

        <!-- Quick Actions -->
        <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 20px;">
            <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 15px;">⚡ Quick Actions</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="post_add.php" class="btn btn-primary" style="justify-content: center;">
                    <i data-feather="plus" style="width: 16px;"></i> New Article
                </a>
                <a href="categories.php" class="btn" style="background: #fdf4ff; color: #9333ea; font-weight: 700; justify-content: center; border-color: #f3e8ff;">
                    <i data-feather="layers" style="width: 16px;"></i> Add Category
                </a>
                <a href="<?php echo BASE_URL; ?>" target="_blank" class="btn" style="background: #f0fdf4; color: #059669; font-weight: 700; justify-content: center; border-color: #dcfce7;">
                    <i data-feather="external-link" style="width: 16px;"></i> View Website
                </a>
                <a href="settings.php" class="btn" style="background: #f8fafc; color: #475569; font-weight: 700; justify-content: center;">
                    <i data-feather="settings" style="width: 16px;"></i> Site Settings
                </a>
            </div>
        </div>

        <!-- Category Distribution -->
        <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 15px; font-weight: 700; margin: 0;">📊 Category Pulse</h3>
                <a href="categories.php" style="font-size: 12px; color: var(--primary); font-weight: 700; text-decoration: none;">Manage</a>
            </div>
            <?php foreach ($cat_stats as $cat): ?>
            <div style="margin-bottom: 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i data-feather="<?php echo $cat['icon']; ?>" style="width: 14px; color: <?php echo $cat['color']; ?>;"></i>
                        <span style="font-size: 13px; font-weight: 600; color: #334155;"><?php echo htmlspecialchars($cat['name']); ?></span>
                    </div>
                    <span style="font-size: 12px; font-weight: 700; color: #64748b;"><?php echo $cat['cnt']; ?></span>
                </div>
                <div style="height: 6px; background: #f1f5f9; border-radius: 10px;">
                    <div style="height: 6px; background: <?php echo $cat['color']; ?>; border-radius: 10px; width: <?php echo $max_cnt > 0 ? round(($cat['cnt']/$max_cnt)*100) : 0; ?>%; transition: width 1s;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Messages -->
        <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;">
            <div style="padding: 18px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 15px; font-weight: 700; margin: 0;">💬 Recent Messages</h3>
                <a href="feedback.php" style="font-size: 12px; color: var(--primary); font-weight: 700; text-decoration: none;">Inbox</a>
            </div>
            <?php if (empty($recent_feedback)): ?>
                <p style="padding: 20px; color: #94a3b8; font-size: 13px; text-align: center;">No messages yet.</p>
            <?php else: ?>
            <?php foreach ($recent_feedback as $msg): ?>
            <a href="feedback.php?view=<?php echo $msg['id']; ?>" style="display: flex; gap: 12px; padding: 14px 20px; border-bottom: 1px solid #f8fafc; text-decoration: none; transition: background .15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; flex-shrink: 0;">
                    <?php echo strtoupper(substr($msg['name'], 0, 1)); ?>
                </div>
                <div style="min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13px; font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($msg['name']); ?></span>
                        <?php if ($msg['status'] === 'new'): ?>
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #ef4444; display: inline-block;"></span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars(substr($msg['message'], 0, 45)); ?>...</div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
