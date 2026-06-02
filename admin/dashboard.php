<?php
$page_title = "Dashboard";
include 'includes/header.php';

// ── Core Stats ────────────────────────────────────────────────────────────
$total_posts      = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$published_posts  = $pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$draft_posts      = $pdo->query("SELECT COUNT(*) FROM posts WHERE status='draft'")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories WHERE status='active'")->fetchColumn();
$total_views      = $pdo->query("SELECT COALESCE(SUM(views),0) FROM posts")->fetchColumn();
$total_users      = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$unread_msgs      = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status='new'")->fetchColumn();
$today_posts      = $pdo->query("SELECT COUNT(*) FROM posts WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$active_polls     = $pdo->query("SELECT COUNT(*) FROM polls WHERE status='active'")->fetchColumn();

// ── Ad stats ─────────────────────────────────────────────────────────────
$total_ad_views   = $pdo->query("SELECT COALESCE(SUM(impressions),0) FROM ads")->fetchColumn();
$total_ad_clicks  = $pdo->query("SELECT COALESCE(SUM(clicks),0) FROM ads")->fetchColumn();
$active_ads       = $pdo->query("SELECT COUNT(*) FROM ads WHERE status=1")->fetchColumn();

// ── Ad clicks today (from log table if it exists) ────────────────────────
try {
    $ad_clicks_today = $pdo->query("SELECT COUNT(*) FROM ad_click_logs WHERE DATE(clicked_at)=CURDATE()")->fetchColumn();
} catch(Exception $e) { $ad_clicks_today = 0; }

// ── Views today ───────────────────────────────────────────────────────────
$views_today = $pdo->query("SELECT COALESCE(SUM(views),0) FROM posts WHERE DATE(updated_at)=CURDATE()")->fetchColumn();

// ── Top viewed posts ──────────────────────────────────────────────────────
$top_posts = $pdo->query("
    SELECT p.id, p.title, p.views, p.status, p.published_at, p.slug,
           GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') as cats,
           GROUP_CONCAT(c.color ORDER BY c.name SEPARATOR ',') as colors
    FROM posts p
    LEFT JOIN post_categories pc ON p.id=pc.post_id
    LEFT JOIN categories c ON pc.category_id=c.id
    WHERE p.status='published'
    GROUP BY p.id ORDER BY p.views DESC LIMIT 5
")->fetchAll();

// ── Recent Posts ──────────────────────────────────────────────────────────
$recent_posts = $pdo->query("
    SELECT p.id, p.title, p.status, p.created_at, p.views, p.slug, p.featured_image,
           GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') as cats,
           GROUP_CONCAT(c.color ORDER BY c.name SEPARATOR ',') as colors
    FROM posts p
    LEFT JOIN post_categories pc ON p.id=pc.post_id
    LEFT JOIN categories c ON pc.category_id=c.id
    GROUP BY p.id ORDER BY p.created_at DESC LIMIT 7
")->fetchAll();

// ── Category stats ────────────────────────────────────────────────────────
$cat_stats = $pdo->query("
    SELECT c.name, c.color, c.icon, COUNT(pc.post_id) as cnt
    FROM categories c
    LEFT JOIN post_categories pc ON c.id=pc.category_id
    LEFT JOIN posts p ON pc.post_id=p.id AND p.status='published'
    WHERE c.status='active'
    GROUP BY c.id ORDER BY cnt DESC LIMIT 7
")->fetchAll();
$max_cnt = max(array_column($cat_stats,'cnt') ?: [1]);

// ── Top Ads ───────────────────────────────────────────────────────────────
$top_ads = $pdo->query("SELECT * FROM ads ORDER BY clicks DESC LIMIT 5")->fetchAll();

// ── Recent Feedback ───────────────────────────────────────────────────────
$recent_feedback = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 5")->fetchAll();

// ── Live Stream ───────────────────────────────────────────────────────────
$live_enabled = get_setting('live_youtube_enabled') === '1';
$live_url     = get_setting('live_youtube_url');
$live_title   = get_setting('live_stream_title', 'Watch Live');

if (isset($_GET['live_toggle'])) {
    $new_val = $_GET['live_toggle'] === 'on' ? '1' : '0';
    $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES ('live_youtube_enabled',?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$new_val,$new_val]);
    redirect('admin/dashboard.php', 'Live stream '.($new_val==='1'?'enabled':'disabled').'!');
}

function dash_yt_id($url) {
    if (!$url) return null;
    if (preg_match('/(?:v=|youtu\.be\/|embed\/|live\/)([a-zA-Z0-9_-]{11})/', $url, $m)) return $m[1];
    return null;
}
$live_vid_id = dash_yt_id($live_url);

// ── Greeting ─────────────────────────────────────────────────────────────
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
$admin_name = $_SESSION['username'] ?? 'Admin';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

/* ── Root tokens ── */
:root {
    --d-primary:  #6366f1;
    --d-emerald:  #10b981;
    --d-amber:    #f59e0b;
    --d-rose:     #f43f5e;
    --d-sky:      #0ea5e9;
    --d-violet:   #8b5cf6;
    --d-surface:  #ffffff;
    --d-bg:       #f8fafc;
    --d-border:   #f1f5f9;
    --d-text:     #0f172a;
    --d-muted:    #94a3b8;
    --d-subtle:   #64748b;
    --d-shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
    --d-shadow:    0 4px 16px rgba(0,0,0,.06);
    --d-shadow-lg: 0 10px 30px rgba(0,0,0,.08);
    --d-radius:   16px;
}

/* ── Base ── */
.db-wrap { font-family:'Inter',system-ui,sans-serif; }

/* ── Greeting Banner ── */
.db-greeting {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
    border-radius: var(--d-radius); padding: 28px 32px;
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 26px; overflow: hidden; position: relative;
    box-shadow: var(--d-shadow-lg);
}
.db-greeting::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:280px; height:280px; border-radius:50%;
    background: radial-gradient(circle, rgba(99,102,241,.25), transparent 70%);
    pointer-events:none;
}
.db-greeting::after {
    content:''; position:absolute; bottom:-80px; left:30%;
    width:200px; height:200px; border-radius:50%;
    background: radial-gradient(circle, rgba(139,92,246,.15), transparent 70%);
    pointer-events:none;
}
.db-greeting-text h2 { margin:0 0 5px; font-size:22px; font-weight:900; color:#f8fafc; letter-spacing:-.3px; }
.db-greeting-text p  { margin:0; font-size:13px; color:#94a3b8; font-weight:500; }
.db-greeting-actions { display:flex; gap:10px; flex-wrap:wrap; position:relative; z-index:2; }
.db-greet-btn {
    display:inline-flex; align-items:center; gap:7px; padding:10px 18px;
    border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; transition:.2s;
}
.db-greet-btn-primary { background:var(--d-primary); color:#fff; box-shadow:0 4px 12px rgba(99,102,241,.4); }
.db-greet-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(99,102,241,.5); }
.db-greet-btn-ghost   { background:rgba(255,255,255,.08); color:#e2e8f0; border:1px solid rgba(255,255,255,.1); }
.db-greet-btn-ghost:hover { background:rgba(255,255,255,.15); }

/* ── KPI Grid ── */
.db-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px; margin-bottom: 24px;
}
@media(max-width:1200px){.db-kpi-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:800px) {.db-kpi-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:480px) {.db-kpi-grid{grid-template-columns:1fr;}}

.db-kpi {
    background: var(--d-surface); border-radius: var(--d-radius);
    padding: 22px 20px; box-shadow: var(--d-shadow-sm);
    border: 1px solid var(--d-border);
    display: flex; flex-direction: column; gap: 14px;
    transition: .25s; position: relative; overflow: hidden;
}
.db-kpi:hover { transform:translateY(-3px); box-shadow:var(--d-shadow); }
.db-kpi::after {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background: var(--kpi-color); border-radius: var(--d-radius) var(--d-radius) 0 0;
}
.db-kpi-top { display:flex; justify-content:space-between; align-items:flex-start; }
.db-kpi-icon {
    width:44px; height:44px; border-radius:12px;
    background:var(--kpi-bg); color:var(--kpi-color);
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.db-kpi-val { font-size:30px; font-weight:900; color:var(--d-text); line-height:1; }
.db-kpi-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; color:var(--d-muted); }
.db-kpi-sub { display:flex; align-items:center; gap:5px; font-size:12px; font-weight:600; color:var(--d-subtle); }
.db-kpi-sub .up   { color: var(--d-emerald); }
.db-kpi-sub .down { color: var(--d-rose); }
.db-kpi-bar { height:4px; background:#f1f5f9; border-radius:4px; overflow:hidden; }
.db-kpi-bar-fill { height:4px; background:var(--kpi-color); border-radius:4px; box-shadow:0 0 8px var(--kpi-glow); }

/* ── Live Banner ── */
.dash-live-banner {
    display: grid; grid-template-columns: 1fr 320px;
    border-radius: var(--d-radius); overflow: hidden; margin-bottom: 24px;
    box-shadow: 0 10px 30px -10px rgba(0,0,0,.15); min-height: 170px;
}
.live-on  { background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 60%,#0f172a 100%); border:1px solid rgba(220,38,38,.2); }
.live-off { background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%); border:1px solid #334155; }
.live-empty { background:var(--d-surface); border:1px dashed #e2e8f0; }
.dlb-info { padding:28px 30px; display:flex; flex-direction:column; justify-content:center; gap:4px; }
.dlb-badge-row { display:flex; align-items:center; gap:7px; margin-bottom:8px; position:relative; }
.dlb-pulse-ring { position:absolute; left:-3px; top:-3px; width:18px; height:18px; border-radius:50%; border:2px solid #dc2626; animation:dlbPulse 1.4s ease-out infinite; }
@keyframes dlbPulse { 0%{transform:scale(1);opacity:.9} 70%,100%{transform:scale(2);opacity:0} }
.dlb-dot { width:10px; height:10px; border-radius:50%; background:#dc2626; animation:dlbBlink 1s ease-in-out infinite; flex-shrink:0; }
@keyframes dlbBlink { 0%,100%{opacity:1} 50%{opacity:.2} }
.dlb-live-text { font-size:11px; font-weight:900; color:#dc2626; letter-spacing:.15em; }
.dlb-off-dot  { width:8px; height:8px; border-radius:50%; background:#475569; flex-shrink:0; }
.dlb-off-text { font-size:11px; font-weight:700; color:#64748b; letter-spacing:.08em; }
.dlb-title { font-size:21px; font-weight:800; color:#f1f5f9; margin:0 0 5px; line-height:1.2; }
.live-empty .dlb-title { color:#0f172a; }
.dlb-url { font-size:12px; color:#94a3b8; margin:0 0 16px; word-break:break-all; font-family:monospace; }
.dlb-actions { display:flex; gap:10px; flex-wrap:wrap; }
.dlb-btn { display:inline-flex; align-items:center; gap:7px; padding:8px 16px; border-radius:9px; font-size:13px; font-weight:700; text-decoration:none; transition:.2s; white-space:nowrap; }
.dlb-btn-on  { background:#dc2626; color:#fff; box-shadow:0 4px 12px rgba(220,38,38,.3); }
.dlb-btn-on:hover { background:#b91c1c; transform:translateY(-1px); }
.dlb-btn-off { background:rgba(255,255,255,.1); color:#f1f5f9; border:1px solid rgba(255,255,255,.15); }
.dlb-btn-off:hover { background:rgba(255,255,255,.18); }
.dlb-btn-settings { background:rgba(255,255,255,.05); color:#94a3b8; border:1px solid rgba(255,255,255,.08); }
.dlb-btn-settings:hover { background:rgba(255,255,255,.12); color:#f1f5f9; }
.dlb-btn-view { background:#16a34a; color:#fff; }
.dlb-btn-view:hover { background:#15803d; transform:translateY(-1px); }
.dlb-preview { position:relative; background:#000; min-height:170px; }
.dlb-corner-live { position:absolute; top:10px; left:10px; background:rgba(220,38,38,.9); color:#fff; font-size:10px; font-weight:900; padding:4px 10px; border-radius:6px; display:flex; align-items:center; gap:5px; pointer-events:none; z-index:3; }
.dlb-dot-sm { width:6px; height:6px; border-radius:50%; background:#fff; animation:dlbBlink 1s infinite; }
@media(max-width:1024px){ .dash-live-banner{grid-template-columns:1fr} .dlb-preview{min-height:200px} }

/* ── Main Layout ── */
.db-main { display:grid; grid-template-columns:1fr 340px; gap:22px; }
@media(max-width:1100px){ .db-main{grid-template-columns:1fr;} }

/* ── Section Cards ── */
.db-card {
    background:var(--d-surface); border-radius:var(--d-radius);
    border:1px solid var(--d-border); box-shadow:var(--d-shadow-sm);
    overflow:hidden;
}
.db-card-head {
    display:flex; justify-content:space-between; align-items:center;
    padding:18px 22px; border-bottom:1px solid var(--d-border); background:#fcfcfd;
}
.db-card-head h3 { margin:0; font-size:15px; font-weight:800; color:var(--d-text); display:flex; align-items:center; gap:9px; }
.db-card-head h3 .hic { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; }
.db-card-link { font-size:12px; font-weight:700; text-decoration:none; padding:6px 12px; border-radius:8px; background:#f1f5f9; color:#475569; transition:.15s; }
.db-card-link:hover { background:#e2e8f0; }

/* ── Top Posts Table ── */
.db-post-table { width:100%; border-collapse:collapse; }
.db-post-table thead th { padding:11px 16px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; color:var(--d-muted); background:#f8fafc; text-align:left; }
.db-post-table tbody tr { border-bottom:1px solid #f8fafc; transition:.15s; }
.db-post-table tbody tr:hover { background:#fafbff; }
.db-post-table tbody tr:last-child { border-bottom:none; }
.db-post-table td { padding:13px 16px; vertical-align:middle; }

.rank-badge { width:26px; height:26px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:900; }
.rank-1 { background:#fef3c7; color:#d97706; }
.rank-2 { background:#f1f5f9; color:#64748b; }
.rank-3 { background:#fff7ed; color:#c2410c; }
.rank-n { background:#f8fafc; color:#cbd5e1; }

.views-bar { display:flex; align-items:center; gap:10px; }
.views-bar-track { flex:1; height:5px; background:#f1f5f9; border-radius:5px; max-width:60px; }
.views-bar-fill  { height:5px; background:var(--d-primary); border-radius:5px; }

/* ── Recent Activity ── */
.db-activity-item {
    display:flex; gap:13px; align-items:center;
    padding:13px 20px; border-bottom:1px solid #f8fafc; transition:.15s;
}
.db-activity-item:hover { background:#fafbff; }
.db-activity-item:last-child { border-bottom:none; }
.db-act-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; box-shadow:0 0 0 3px var(--dot-color)22; background:var(--dot-color); }
.db-act-title { font-size:13px; font-weight:700; color:var(--d-text); display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:200px; text-decoration:none; }
.db-act-title:hover { color:var(--d-primary); }
.db-act-meta  { display:flex; align-items:center; gap:7px; margin-top:2px; }
.badge-published { background:#d1fae5; color:#065f46; padding:2px 8px; border-radius:6px; font-size:10px; font-weight:800; text-transform:uppercase; }
.badge-draft     { background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:6px; font-size:10px; font-weight:800; text-transform:uppercase; }

/* ── Ad Pulse rows ── */
.db-ad-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:13px 20px; border-bottom:1px solid #f8fafc; transition:.15s;
}
.db-ad-row:hover { background:#fafbff; }
.db-ad-row:last-child { border-bottom:none; }
.ctr-ring {
    width:42px; height:42px; border-radius:50%;
    background: conic-gradient(var(--d-primary) var(--pct), #f1f5f9 0);
    display:flex; align-items:center; justify-content:center; position:relative; flex-shrink:0;
}
.ctr-ring::before { content:''; position:absolute; inset:5px; background:#fff; border-radius:50%; }
.ctr-ring span { position:relative; z-index:1; font-size:9px; font-weight:900; color:var(--d-text); }

/* ── Category bars ── */
.db-cat-row { margin-bottom:16px; }
.db-cat-row:last-child { margin-bottom:0; }
.db-cat-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
.db-cat-name { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:var(--d-text); }
.db-cat-icon { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; }
.db-cat-cnt { font-size:12px; font-weight:800; color:var(--d-subtle); }
.db-cat-track { height:5px; background:#f1f5f9; border-radius:5px; }
.db-cat-fill  { height:5px; border-radius:5px; }

/* ── Quick Actions ── */
.db-quick-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; padding:18px; }
.db-qa {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:8px; padding:16px 10px; border-radius:12px; text-decoration:none;
    font-size:12px; font-weight:800; transition:.2s; text-align:center;
    border:1px solid transparent;
}
.db-qa:hover { transform:translateY(-2px); }
.db-qa-primary { background:var(--d-primary); color:#fff; box-shadow:0 4px 12px rgba(99,102,241,.35); }
.db-qa-primary:hover { box-shadow:0 6px 18px rgba(99,102,241,.45); }
.db-qa-dark    { background:#1e293b; color:#e2e8f0; }
.db-qa-dark:hover { background:#0f172a; }
.db-qa-emerald { background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }
.db-qa-emerald:hover { background:#d1fae5; }
.db-qa-amber   { background:#fffbeb; color:#92400e; border-color:#fde68a; }
.db-qa-amber:hover { background:#fef3c7; }
.db-qa-rose    { background:#fff1f2; color:#881337; border-color:#fecdd3; }
.db-qa-rose:hover { background:#ffe4e6; }
.db-qa-sky     { background:#f0f9ff; color:#075985; border-color:#bae6fd; }
.db-qa-sky:hover { background:#e0f2fe; }
.db-qa-icon { width:32px; height:32px; border-radius:9px; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; }
.db-qa-green-site { display:flex; align-items:center; justify-content:center; gap:8px; margin:0 18px 18px; padding:11px; background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; border-radius:10px; text-decoration:none; font-size:13px; font-weight:700; transition:.2s; }
.db-qa-green-site:hover { background:#d1fae5; }

/* ── Feedback rows ── */
.db-msg-row {
    display:flex; gap:14px; padding:14px 20px; border-bottom:1px solid #f8fafc;
    text-decoration:none; transition:.15s;
}
.db-msg-row:hover { background:#fafbff; }
.db-msg-row:last-child { border-bottom:none; }
.db-msg-avatar { width:38px; height:38px; border-radius:11px; background:var(--d-primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:15px; flex-shrink:0; box-shadow:0 4px 10px rgba(99,102,241,.2); }
.db-msg-name { font-size:13px; font-weight:800; color:var(--d-text); display:flex; align-items:center; gap:6px; }
.db-msg-new  { width:7px; height:7px; border-radius:50%; background:var(--d-rose); display:inline-block; }
.db-msg-preview { font-size:12px; color:var(--d-subtle); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; max-width:200px; }

/* ── Section divider ── */
.db-section-gap { display:flex; flex-direction:column; gap:20px; }
</style>

<div class="db-wrap">

<!-- ── Greeting Banner ── -->
<div class="db-greeting">
    <div class="db-greeting-text">
        <h2><?php echo $greeting; ?>, <?php echo htmlspecialchars($admin_name); ?> 👋</h2>
        <p><?php echo date('l, F j, Y'); ?> &nbsp;·&nbsp; <?php echo $today_posts; ?> article<?php echo $today_posts!=1?'s':''; ?> published today &nbsp;·&nbsp; <?php echo number_format($total_views); ?> total views</p>
    </div>
    <div class="db-greeting-actions">
        <a href="post_add.php" class="db-greet-btn db-greet-btn-primary">
            <i data-feather="plus" style="width:15px;"></i> New Article
        </a>
        <a href="<?php echo BASE_URL; ?>" target="_blank" class="db-greet-btn db-greet-btn-ghost">
            <i data-feather="external-link" style="width:15px;"></i> View Site
        </a>
    </div>
</div>

<!-- ── KPI Cards ── -->
<div class="db-kpi-grid">

    <div class="db-kpi" style="--kpi-color:#6366f1;--kpi-bg:#eff6ff;--kpi-glow:rgba(99,102,241,.3);">
        <div class="db-kpi-top">
            <div>
                <div class="db-kpi-label">Published</div>
                <div class="db-kpi-val"><?php echo number_format($published_posts); ?></div>
            </div>
            <div class="db-kpi-icon"><i data-feather="file-text" style="width:22px;"></i></div>
        </div>
        <div class="db-kpi-sub">
            <span><?php echo $draft_posts; ?> drafts pending</span>
        </div>
        <div class="db-kpi-bar">
            <div class="db-kpi-bar-fill" style="width:<?php echo $total_posts>0?round(($published_posts/$total_posts)*100):0; ?>%;"></div>
        </div>
    </div>

    <div class="db-kpi" style="--kpi-color:#10b981;--kpi-bg:#d1fae5;--kpi-glow:rgba(16,185,129,.3);">
        <div class="db-kpi-top">
            <div>
                <div class="db-kpi-label">Total Views</div>
                <div class="db-kpi-val"><?php echo $total_views>=1000?round($total_views/1000,1).'K':number_format($total_views); ?></div>
            </div>
            <div class="db-kpi-icon"><i data-feather="trending-up" style="width:22px;"></i></div>
        </div>
        <div class="db-kpi-sub">
            <i data-feather="arrow-up-right" style="width:13px;" class="up"></i>
            <span class="up"><?php echo $today_posts; ?> posts today</span>
        </div>
    </div>

    <div class="db-kpi" style="--kpi-color:#f59e0b;--kpi-bg:#fef3c7;--kpi-glow:rgba(245,158,11,.3);">
        <div class="db-kpi-top">
            <div>
                <div class="db-kpi-label">Ad Clicks</div>
                <div class="db-kpi-val"><?php echo number_format($total_ad_clicks); ?></div>
            </div>
            <div class="db-kpi-icon"><i data-feather="mouse-pointer" style="width:22px;"></i></div>
        </div>
        <div class="db-kpi-sub">
            <span><?php echo number_format($total_ad_views); ?> impressions</span>
        </div>
    </div>

    <div class="db-kpi" style="--kpi-color:#f43f5e;--kpi-bg:#fff1f2;--kpi-glow:rgba(244,63,94,.3);">
        <div class="db-kpi-top">
            <div>
                <div class="db-kpi-label">Inbox</div>
                <div class="db-kpi-val"><?php echo number_format($unread_msgs); ?></div>
            </div>
            <div class="db-kpi-icon"><i data-feather="mail" style="width:22px;"></i></div>
        </div>
        <div class="db-kpi-sub">
            <?php if($unread_msgs > 0): ?>
                <span class="down"><i data-feather="alert-circle" style="width:12px;"></i> Unread messages</span>
            <?php else: ?>
                <span>Clean inbox 🎉</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="db-kpi" style="--kpi-color:#8b5cf6;--kpi-bg:#ede9fe;--kpi-glow:rgba(139,92,246,.3);">
        <div class="db-kpi-top">
            <div>
                <div class="db-kpi-label">Categories</div>
                <div class="db-kpi-val"><?php echo number_format($total_categories); ?></div>
            </div>
            <div class="db-kpi-icon"><i data-feather="layers" style="width:22px;"></i></div>
        </div>
        <div class="db-kpi-sub"><span>Active sections</span></div>
    </div>

    <div class="db-kpi" style="--kpi-color:#0ea5e9;--kpi-bg:#e0f2fe;--kpi-glow:rgba(14,165,233,.3);">
        <div class="db-kpi-top">
            <div>
                <div class="db-kpi-label">Active Polls</div>
                <div class="db-kpi-val"><?php echo number_format($active_polls); ?></div>
            </div>
            <div class="db-kpi-icon"><i data-feather="pie-chart" style="width:22px;"></i></div>
        </div>
        <div class="db-kpi-sub"><span>Running now</span></div>
    </div>

    <div class="db-kpi" style="--kpi-color:#6366f1;--kpi-bg:#eff6ff;--kpi-glow:rgba(99,102,241,.3);">
        <div class="db-kpi-top">
            <div>
                <div class="db-kpi-label">Staff / Users</div>
                <div class="db-kpi-val"><?php echo number_format($total_users); ?></div>
            </div>
            <div class="db-kpi-icon"><i data-feather="users" style="width:22px;"></i></div>
        </div>
        <div class="db-kpi-sub"><span>Team members</span></div>
    </div>

    <div class="db-kpi" style="--kpi-color:#10b981;--kpi-bg:#d1fae5;--kpi-glow:rgba(16,185,129,.3);">
        <div class="db-kpi-top">
            <div>
                <div class="db-kpi-label">Active Ads</div>
                <div class="db-kpi-val"><?php echo number_format($active_ads); ?></div>
            </div>
            <div class="db-kpi-icon"><i data-feather="target" style="width:22px;"></i></div>
        </div>
        <div class="db-kpi-sub">
            <span><?php echo $ad_clicks_today; ?> clicks today</span>
        </div>
    </div>

</div>

<!-- ── Live Stream Banner ── -->
<?php if ($live_url): ?>
<div class="dash-live-banner <?php echo $live_enabled ? 'live-on' : 'live-off'; ?>">
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
        <p class="dlb-url"><?php echo htmlspecialchars(substr($live_url,0,60)).(strlen($live_url)>60?'…':''); ?></p>
        <div class="dlb-actions">
            <?php if ($live_enabled): ?>
                <a href="?live_toggle=off" class="dlb-btn dlb-btn-off"><i data-feather="stop-circle" style="width:14px;"></i> Stop Live</a>
            <?php else: ?>
                <a href="?live_toggle=on" class="dlb-btn dlb-btn-on"><i data-feather="play-circle" style="width:14px;"></i> Go Live</a>
            <?php endif; ?>
            <a href="settings.php?tab=livestream" class="dlb-btn dlb-btn-settings"><i data-feather="settings" style="width:14px;"></i> Configure</a>
            <?php if ($live_enabled): ?>
                <a href="<?php echo BASE_URL; ?>" target="_blank" class="dlb-btn dlb-btn-view"><i data-feather="external-link" style="width:14px;"></i> View Site</a>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($live_vid_id): ?>
    <div class="dlb-preview">
        <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($live_vid_id); ?>?autoplay=0&mute=1&rel=0&modestbranding=1&controls=0&disablekb=1" style="width:100%;height:100%;border:none;" title="Live Preview"></iframe>
        <div style="position:absolute;inset:0;background:transparent;z-index:2;"></div>
        <?php if ($live_enabled): ?><div class="dlb-corner-live"><span class="dlb-dot-sm"></span>LIVE</div><?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="dash-live-banner live-empty">
    <div class="dlb-info">
        <div class="dlb-badge-row"><span class="dlb-off-dot"></span><span class="dlb-off-text">NO STREAM CONFIGURED</span></div>
        <h2 class="dlb-title">YouTube Live Stream</h2>
        <p style="color:#94a3b8;font-size:13px;margin:4px 0 14px;">Broadcast live on your homepage with an animated Live badge.</p>
        <a href="settings.php" class="dlb-btn dlb-btn-on"><i data-feather="plus-circle" style="width:14px;"></i> Configure</a>
    </div>
    <div class="dlb-preview" style="background:#f8fafc;display:flex;align-items:center;justify-content:center;">
        <i data-feather="video-off" style="width:56px;height:56px;color:#cbd5e1;stroke-width:1;"></i>
    </div>
</div>
<?php endif; ?>

<!-- ── Main 2-Column Layout ── -->
<div class="db-main">

    <!-- LEFT col -->
    <div class="db-section-gap">

        <!-- Top Performing Articles -->
        <div class="db-card">
            <div class="db-card-head">
                <h3>
                    <span class="hic" style="background:#fef3c7;color:#d97706;"><i data-feather="award" style="width:16px;"></i></span>
                    Top Performing Articles
                </h3>
                <a href="posts.php" class="db-card-link">View All</a>
            </div>
            <div style="overflow-x:auto;">
                <table class="db-post-table">
                    <thead><tr>
                        <th style="width:44px;text-align:center;">#</th>
                        <th>Article</th>
                        <th>Category</th>
                        <th>Views</th>
                        <th style="text-align:right;">Edit</th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($top_posts)): ?>
                        <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:50px;font-size:13px;">No published articles yet.</td></tr>
                    <?php else: ?>
                    <?php foreach ($top_posts as $i => $post):
                        $cats   = explode(',', $post['cats']   ?? '');
                        $colors = explode(',', $post['colors'] ?? '#6366f1');
                        $rankClass = ['rank-1','rank-2','rank-3'][$i] ?? 'rank-n';
                    ?>
                    <tr>
                        <td style="text-align:center;">
                            <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $i+1; ?></span>
                        </td>
                        <td style="max-width:280px;">
                            <a href="<?php echo BASE_URL; ?>article/<?php echo $post['slug']; ?>" target="_blank"
                               style="font-weight:700;color:var(--d-text);text-decoration:none;font-size:13px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:3px;">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                            <span style="font-size:11px;color:var(--d-muted);"><?php echo date('M j, Y', strtotime($post['published_at'])); ?></span>
                        </td>
                        <td>
                            <span style="background:<?php echo ($colors[0]??'#6366f1'); ?>18;color:<?php echo $colors[0]??'#6366f1'; ?>;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap;">
                                <?php echo htmlspecialchars($cats[0]??'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <div class="views-bar">
                                <div class="views-bar-track">
                                    <div class="views-bar-fill" style="width:<?php echo $top_posts[0]['views']>0?round(($post['views']/$top_posts[0]['views'])*100):0; ?>%;"></div>
                                </div>
                                <span style="font-weight:800;font-size:13px;color:var(--d-text);white-space:nowrap;"><?php echo number_format($post['views']); ?></span>
                            </div>
                        </td>
                        <td style="text-align:right;">
                            <a href="post_edit.php?id=<?php echo $post['id']; ?>" style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;background:#eff6ff;color:#3b82f6;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;">
                                <i data-feather="edit-2" style="width:12px;"></i> Edit
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2-col row: Recent + Ad Pulse -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

            <!-- Recent Activity -->
            <div class="db-card">
                <div class="db-card-head">
                    <h3>
                        <span class="hic" style="background:#eff6ff;color:#6366f1;"><i data-feather="clock" style="width:15px;"></i></span>
                        Recent Activity
                    </h3>
                    <a href="post_add.php" class="db-card-link" style="background:#eff6ff;color:var(--d-primary);">+ New</a>
                </div>
                <div>
                <?php foreach ($recent_posts as $post):
                    $cols = explode(',', $post['colors'] ?? '#6366f1');
                ?>
                <div class="db-activity-item">
                    <div class="db-act-dot" style="--dot-color:<?php echo $cols[0]; ?>;"></div>
                    <div style="flex:1;min-width:0;">
                        <a href="post_edit.php?id=<?php echo $post['id']; ?>" class="db-act-title">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </a>
                        <div class="db-act-meta">
                            <span class="badge-<?php echo $post['status']; ?>"><?php echo $post['status']; ?></span>
                            <span style="font-size:11px;color:var(--d-muted);"><?php echo date('M j', strtotime($post['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>

            <!-- Ad Pulse -->
            <div class="db-card">
                <div class="db-card-head">
                    <h3>
                        <span class="hic" style="background:#fff1f2;color:#f43f5e;"><i data-feather="bar-chart" style="width:15px;"></i></span>
                        Ad Pulse
                    </h3>
                    <a href="ad_click_history.php" class="db-card-link" style="background:#fff1f2;color:#f43f5e;">History</a>
                </div>
                <div>
                <?php foreach ($top_ads as $ad):
                    $ctr = $ad['impressions']>0 ? round(($ad['clicks']/$ad['impressions'])*100,1) : 0;
                    $pct = min($ctr*5, 100); // scale for ring
                ?>
                <div class="db-ad-row">
                    <div style="min-width:0;flex:1;margin-right:12px;">
                        <div style="font-weight:700;color:var(--d-text);font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?php echo htmlspecialchars($ad['name']); ?>
                        </div>
                        <div style="font-size:10px;color:var(--d-muted);text-transform:uppercase;font-weight:700;margin-top:2px;">
                            <?php echo str_replace('_',' ',$ad['location']); ?>
                        </div>
                        <div style="font-size:11px;color:var(--d-subtle);margin-top:3px;">
                            <?php echo number_format($ad['clicks']); ?> clicks &nbsp;·&nbsp; <?php echo number_format($ad['impressions']); ?> views
                        </div>
                    </div>
                    <div style="text-align:center;flex-shrink:0;">
                        <div style="font-size:15px;font-weight:900;color:var(--d-text);"><?php echo $ctr; ?>%</div>
                        <div style="font-size:9px;font-weight:800;text-transform:uppercase;color:var(--d-muted);">CTR</div>
                    </div>
                </div>
                <?php endforeach;
                if (empty($top_ads)): ?>
                <p style="text-align:center;padding:40px;color:var(--d-muted);font-size:13px;">No ads created yet.</p>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT col -->
    <div class="db-section-gap">

        <!-- Quick Actions -->
        <div class="db-card">
            <div class="db-card-head">
                <h3>
                    <span class="hic" style="background:#fef3c7;color:#d97706;"><i data-feather="zap" style="width:15px;"></i></span>
                    Quick Actions
                </h3>
            </div>
            <div class="db-quick-grid">
                <a href="post_add.php"   class="db-qa db-qa-primary">
                    <div class="db-qa-icon"><i data-feather="plus" style="width:17px;"></i></div>
                    <span>New Article</span>
                </a>
                <a href="categories.php" class="db-qa db-qa-dark">
                    <div class="db-qa-icon"><i data-feather="layers" style="width:17px;"></i></div>
                    <span>Categories</span>
                </a>
                <a href="polls.php"      class="db-qa db-qa-emerald">
                    <div class="db-qa-icon" style="background:rgba(16,185,129,.15);"><i data-feather="pie-chart" style="width:17px;"></i></div>
                    <span>Polls</span>
                </a>
                <a href="ads.php"        class="db-qa db-qa-amber">
                    <div class="db-qa-icon" style="background:rgba(245,158,11,.15);"><i data-feather="target" style="width:17px;"></i></div>
                    <span>Ads</span>
                </a>
                <a href="users.php"      class="db-qa db-qa-sky">
                    <div class="db-qa-icon" style="background:rgba(14,165,233,.15);"><i data-feather="users" style="width:17px;"></i></div>
                    <span>Staff</span>
                </a>
                <a href="settings.php"   class="db-qa db-qa-rose">
                    <div class="db-qa-icon" style="background:rgba(244,63,94,.15);"><i data-feather="settings" style="width:17px;"></i></div>
                    <span>Settings</span>
                </a>
            </div>
            <a href="<?php echo BASE_URL; ?>" target="_blank" class="db-qa-green-site">
                <i data-feather="external-link" style="width:16px;"></i> Open Main Website
            </a>
        </div>

        <!-- Section Pulse -->
        <div class="db-card">
            <div class="db-card-head">
                <h3>
                    <span class="hic" style="background:#ede9fe;color:#7c3aed;"><i data-feather="bar-chart-2" style="width:15px;"></i></span>
                    Section Pulse
                </h3>
                <a href="categories.php" class="db-card-link">Manage</a>
            </div>
            <div style="padding:20px;">
            <?php foreach ($cat_stats as $cat): ?>
            <div class="db-cat-row">
                <div class="db-cat-top">
                    <div class="db-cat-name">
                        <div class="db-cat-icon" style="background:<?php echo $cat['color']; ?>18;">
                            <i data-feather="<?php echo $cat['icon']; ?>" style="width:14px;color:<?php echo $cat['color']; ?>;"></i>
                        </div>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </div>
                    <span class="db-cat-cnt"><?php echo $cat['cnt']; ?></span>
                </div>
                <div class="db-cat-track">
                    <div class="db-cat-fill" style="width:<?php echo $max_cnt>0?round(($cat['cnt']/$max_cnt)*100):0; ?>%;background:<?php echo $cat['color']; ?>;box-shadow:0 0 8px <?php echo $cat['color']; ?>44;"></div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>

        <!-- Feedback Inbox -->
        <div class="db-card">
            <div class="db-card-head">
                <h3>
                    <span class="hic" style="background:#fff1f2;color:#f43f5e;"><i data-feather="message-circle" style="width:15px;"></i></span>
                    Feedback Inbox
                    <?php if ($unread_msgs > 0): ?>
                        <span style="background:#f43f5e;color:#fff;font-size:10px;font-weight:900;padding:2px 8px;border-radius:20px;margin-left:4px;"><?php echo $unread_msgs; ?></span>
                    <?php endif; ?>
                </h3>
                <a href="feedback.php" class="db-card-link">Open All</a>
            </div>
            <div>
            <?php if (empty($recent_feedback)): ?>
                <p style="text-align:center;padding:40px 20px;color:var(--d-muted);font-size:13px;">✅ Clean inbox! No messages.</p>
            <?php else: ?>
            <?php foreach ($recent_feedback as $msg): ?>
            <a href="feedback.php?view=<?php echo $msg['id']; ?>" class="db-msg-row">
                <div class="db-msg-avatar"><?php echo strtoupper(substr($msg['name']??'?',0,1)); ?></div>
                <div style="min-width:0;">
                    <div class="db-msg-name">
                        <?php echo htmlspecialchars($msg['name']); ?>
                        <?php if ($msg['status']==='new'): ?><span class="db-msg-new"></span><?php endif; ?>
                    </div>
                    <div class="db-msg-preview"><?php echo htmlspecialchars(substr($msg['message']??'',0,55)); ?>…</div>
                    <div style="font-size:10px;color:var(--d-muted);margin-top:2px;"><?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?></div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </div>

    </div><!-- right col -->
</div><!-- db-main -->

</div><!-- db-wrap -->

<?php include 'includes/footer.php'; ?>
