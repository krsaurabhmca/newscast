<?php
$page_title = "Dashboard";
include 'includes/header.php';

// ── Core Stats ────────────────────────────────────────────────────────────
$user_id = $_SESSION['user_id'] ?? 0;
$post_condition_simple = is_reporter() ? "user_id = " . (int) $user_id : "1=1";
$post_condition_join = is_reporter() ? "p.user_id = " . (int) $user_id : "1=1";

// Auto Cleanup Old Logs
cleanup_visitor_logs($pdo);

$total_posts = $pdo->query("SELECT COUNT(*) FROM posts WHERE $post_condition_simple")->fetchColumn();
$published_posts = $pdo->query("SELECT COUNT(*) FROM posts WHERE status='published' AND $post_condition_simple")->fetchColumn();
$draft_posts = $pdo->query("SELECT COUNT(*) FROM posts WHERE status='draft' AND $post_condition_simple")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories WHERE status='active'")->fetchColumn();
$total_views = $pdo->query("SELECT COALESCE(SUM(views),0) FROM posts WHERE $post_condition_simple")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$unread_msgs = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status='new'")->fetchColumn();
$today_posts = $pdo->query("SELECT COUNT(*) FROM posts WHERE DATE(created_at)=CURDATE() AND $post_condition_simple")->fetchColumn();
$active_polls = $pdo->query("SELECT COUNT(*) FROM polls WHERE status='active'")->fetchColumn();

try {
    $live_visitors = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM post_views_logs WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetchColumn();
} catch (Exception $e) {
    $live_visitors = 0;
}

// ── Pending comments ─────────────────────────────────────────────────────
try {
    $pending_comments = $pdo->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn();
} catch (Exception $e) {
    $pending_comments = 0;
}

// ── Ad stats ─────────────────────────────────────────────────────────────
$total_ad_views = $pdo->query("SELECT COALESCE(SUM(impressions),0) FROM ads")->fetchColumn();
$total_ad_clicks = $pdo->query("SELECT COALESCE(SUM(clicks),0) FROM ads")->fetchColumn();
$active_ads = $pdo->query("SELECT COUNT(*) FROM ads WHERE status=1")->fetchColumn();

try {
    $ad_clicks_today = $pdo->query("SELECT COUNT(*) FROM ad_click_logs WHERE DATE(clicked_at)=CURDATE()")->fetchColumn();
} catch (Exception $e) {
    $ad_clicks_today = 0;
}

// ── Top viewed posts ──────────────────────────────────────────────────────
$top_posts = $pdo->query("
    SELECT p.id, p.title, p.views, p.status, p.published_at, p.slug,
           GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') as cats,
           GROUP_CONCAT(c.color ORDER BY c.name SEPARATOR ',') as colors
    FROM posts p
    LEFT JOIN post_categories pc ON p.id=pc.post_id
    LEFT JOIN categories c ON pc.category_id=c.id
    WHERE p.status='published' AND $post_condition_join
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
    WHERE $post_condition_join
    GROUP BY p.id ORDER BY p.created_at DESC LIMIT 6
")->fetchAll();

// ── Category stats ────────────────────────────────────────────────────────
$cat_stats = $pdo->query("
    SELECT c.name, c.color, c.icon, COUNT(pc.post_id) as cnt
    FROM categories c
    LEFT JOIN post_categories pc ON c.id=pc.category_id
    LEFT JOIN posts p ON pc.post_id=p.id AND p.status='published'
    WHERE c.status='active'
    GROUP BY c.id ORDER BY cnt DESC LIMIT 6
")->fetchAll();
$max_cnt = max(array_column($cat_stats, 'cnt') ?: [1]);

// ── Top Ads ───────────────────────────────────────────────────────────────
$top_ads = $pdo->query("SELECT * FROM ads ORDER BY clicks DESC LIMIT 4")->fetchAll();

// ── Recent Feedback ───────────────────────────────────────────────────────
$recent_feedback = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 4")->fetchAll();

// ── Live Stream ───────────────────────────────────────────────────────────
$live_enabled = get_setting('live_youtube_enabled') === '1';
$live_url = get_setting('live_youtube_url');
$live_title = get_setting('live_stream_title', 'Watch Live');

if (isset($_GET['live_toggle'])) {
    $new_val = $_GET['live_toggle'] === 'on' ? '1' : '0';
    $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES ('live_youtube_enabled',?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$new_val, $new_val]);
    redirect('admin/dashboard.php', 'Live stream ' . ($new_val === '1' ? 'enabled' : 'disabled') . '!');
}

function dash_yt_id($url)
{
    if (!$url)
        return null;
    if (preg_match('/(?:v=|youtu\.be\/|embed\/|live\/)([a-zA-Z0-9_-]{11})/', $url, $m))
        return $m[1];
    return null;
}
$live_vid_id = dash_yt_id($live_url);

// ── Greeting ─────────────────────────────────────────────────────────────
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
$greeting_emoji = $hour < 12 ? '☀️' : ($hour < 17 ? '🌤️' : '🌙');
$admin_name = $_SESSION['username'] ?? 'Admin';

// ── What's New: load from changelog.json ─────────────────────────────────
$changelog_data = [];
$changelog_path = __DIR__ . '/changelog.json';
if (file_exists($changelog_path)) {
    $changelog_raw = json_decode(file_get_contents($changelog_path), true);
    if (is_array($changelog_raw)) {
        $changelog_data = array_slice($changelog_raw, 0, 3); // show top 3 versions
    }
}

// ── Current version ───────────────────────────────────────────────────────
$version_data = json_decode(file_get_contents(dirname(__DIR__) . '/version.json'), true);
$current_ver = $version_data['version'] ?? '2.2.0';
// ── Views & Clicks Trends Data ───────────────────────────────────────────
$trends_data = [
    'today' => ['labels' => [], 'views' => [], 'clicks' => []],
    '7days' => ['labels' => [], 'views' => [], 'clicks' => []],
    '30days' => ['labels' => [], 'views' => [], 'clicks' => []],
    '90days' => ['labels' => [], 'views' => [], 'clicks' => []]
];

// Today: hourly views & clicks
for ($h = 0; $h < 24; $h++) {
    $hour_str = sprintf('%02d:00', $h);
    $trends_data['today']['labels'][] = $hour_str;
    $trends_data['today']['views'][] = 0;
    $trends_data['today']['clicks'][] = 0;
}
try {
    $today_views = $pdo->query("SELECT HOUR(viewed_at) as hr, COUNT(*) as cnt FROM post_views_logs WHERE DATE(viewed_at) = CURDATE() GROUP BY HOUR(viewed_at)")->fetchAll();
    foreach ($today_views as $v) {
        $trends_data['today']['views'][$v['hr']] = (int) $v['cnt'];
    }
    $today_clicks = $pdo->query("SELECT HOUR(clicked_at) as hr, COUNT(*) as cnt FROM ad_click_logs WHERE DATE(clicked_at) = CURDATE() GROUP BY HOUR(clicked_at)")->fetchAll();
    foreach ($today_clicks as $c) {
        $trends_data['today']['clicks'][$c['hr']] = (int) $c['cnt'];
    }
} catch (Exception $e) {
}

// Helper for days trend
function get_days_trend($pdo, $days)
{
    $labels = [];
    $views = [];
    $clicks = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $lbl = date('M d', strtotime("-$i days"));
        $labels[] = $lbl;
        $views[$d] = 0;
        $clicks[$d] = 0;
    }

    try {
        $v_data = $pdo->query("SELECT DATE(viewed_at) as dt, COUNT(*) as cnt FROM post_views_logs WHERE viewed_at >= DATE_SUB(CURDATE(), INTERVAL $days DAY) GROUP BY DATE(viewed_at)")->fetchAll();
        foreach ($v_data as $row) {
            if (isset($views[$row['dt']]))
                $views[$row['dt']] = (int) $row['cnt'];
        }
        $c_data = $pdo->query("SELECT DATE(clicked_at) as dt, COUNT(*) as cnt FROM ad_click_logs WHERE clicked_at >= DATE_SUB(CURDATE(), INTERVAL $days DAY) GROUP BY DATE(clicked_at)")->fetchAll();
        foreach ($c_data as $row) {
            if (isset($clicks[$row['dt']]))
                $clicks[$row['dt']] = (int) $row['cnt'];
        }
    } catch (Exception $e) {
    }

    return [
        'labels' => $labels,
        'views' => array_values($views),
        'clicks' => array_values($clicks)
    ];
}

$trends_data['7days'] = get_days_trend($pdo, 7);
$trends_data['30days'] = get_days_trend($pdo, 30);
$trends_data['90days'] = get_days_trend($pdo, 90);

// ── Save Photo of the Day from Dashboard ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_photo_of_day'])) {
    if (is_demo_account()) {
        redirect('admin/dashboard.php', 'Action restricted: Demo accounts cannot save settings.', 'danger');
        exit;
    }

    $title = clean($_POST['photo_of_day_title'] ?? '');
    $caption = clean($_POST['photo_of_day_caption'] ?? '');

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('photo_of_day_title', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$title, $title]);

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('photo_of_day_caption', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$caption, $caption]);

        if (isset($_FILES['photo_of_day_image']) && $_FILES['photo_of_day_image']['error'] === 0) {
            $uploaded_file = upload_and_optimize_image($_FILES['photo_of_day_image'], "../assets/images/", "photo_of_day_", 1000, 90);
            if ($uploaded_file) {
                $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('photo_of_day_image', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$uploaded_file, $uploaded_file]);
            }
        } elseif (!empty($_POST['photo_of_day_ai_url'])) {
            $ai_url = $_POST['photo_of_day_ai_url'];
            $image_data = @file_get_contents($ai_url);
            if ($image_data) {
                $new_filename = uniqid("photo_of_day_ai_") . '_' . time() . '.jpg';
                $destination = "../assets/images/" . $new_filename;
                if (file_put_contents($destination, $image_data)) {
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('photo_of_day_image', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$new_filename, $new_filename]);
                }
            }
        }

        $pdo->commit();
        redirect('admin/dashboard.php', 'Photo of the Day updated successfully!');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            try {
                $pdo->rollBack();
            } catch (Exception $rb_e) {
            }
        }
        $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
        redirect('admin/dashboard.php');
    }
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

    /* ── Root tokens ── */
    :root {
        --d-primary: #6366f1;
        --d-emerald: #10b981;
        --d-amber: #f59e0b;
        --d-rose: #f43f5e;
        --d-sky: #0ea5e9;
        --d-violet: #8b5cf6;
        --d-surface: #ffffff;
        --d-bg: #f1f5f9;
        --d-border: #e2e8f0;
        --d-text: #0f172a;
        --d-muted: #94a3b8;
        --d-subtle: #64748b;
        --d-shadow-sm: 0 1px 3px rgba(0, 0, 0, .05), 0 1px 2px rgba(0, 0, 0, .03);
        --d-shadow: 0 4px 16px rgba(0, 0, 0, .06);
        --d-shadow-lg: 0 10px 30px rgba(0, 0, 0, .08);
        --d-radius: 14px;
    }

    .db-wrap {
        font-family: 'Inter', system-ui, sans-serif;
        background: var(--d-bg);
        margin: -20px;
        padding: 24px;
        min-height: 100vh;
    }

    /* ── Greeting Banner ── */
    .db-greeting {
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%);
        border-radius: var(--d-radius);
        padding: 26px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 22px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 4px 20px rgba(99, 102, 241, .25);
    }

    .db-greeting::before {
        content: '';
        position: absolute;
        top: -80px;
        right: -60px;
        width: 300px;
        height: 300px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(99, 102, 241, .3), transparent 70%);
        pointer-events: none;
    }

    .db-greeting::after {
        content: '';
        position: absolute;
        bottom: -80px;
        left: 30%;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(139, 92, 246, .18), transparent 70%);
        pointer-events: none;
    }

    .db-greeting-left {
        position: relative;
        z-index: 2;
    }

    .db-greeting-left h2 {
        margin: 0 0 4px;
        font-size: 21px;
        font-weight: 800;
        color: #f8fafc;
        letter-spacing: -.3px;
    }

    .db-greeting-left p {
        margin: 0;
        font-size: 13px;
        color: #94a3b8;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .db-greeting-left p span.dot {
        color: #475569;
    }

    .db-greeting-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        position: relative;
        z-index: 2;
    }

    .db-greet-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 17px;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: .2s;
    }

    .db-greet-btn-primary {
        background: var(--d-primary);
        color: #fff;
        box-shadow: 0 4px 12px rgba(99, 102, 241, .4);
    }

    .db-greet-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(99, 102, 241, .5);
        color: #fff;
    }

    .db-greet-btn-ghost {
        background: rgba(255, 255, 255, .08);
        color: #e2e8f0;
        border: 1px solid rgba(255, 255, 255, .12);
    }

    .db-greet-btn-ghost:hover {
        background: rgba(255, 255, 255, .15);
        color: #fff;
    }

    .db-greet-version {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(255, 255, 255, .07);
        border: 1px solid rgba(255, 255, 255, .1);
        color: #a5b4fc;
        font-size: 11px;
        font-weight: 800;
        padding: 4px 12px;
        border-radius: 20px;
        letter-spacing: .5px;
    }

    /* ── KPI Grid ── */
    .db-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
        margin-bottom: 22px;
    }

    .db-kpi {
        background: var(--d-surface);
        border-radius: var(--d-radius);
        padding: 18px 18px 16px;
        box-shadow: var(--d-shadow-sm);
        border: 1px solid var(--d-border);
        display: flex;
        flex-direction: column;
        gap: 10px;
        transition: .2s;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
    }

    .db-kpi:hover {
        transform: translateY(-3px);
        box-shadow: var(--d-shadow);
        border-color: transparent;
    }

    .db-kpi::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--kpi-color);
        border-radius: var(--d-radius) var(--d-radius) 0 0;
    }

    .db-kpi-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .db-kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--kpi-bg);
        color: var(--kpi-color);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .db-kpi-val {
        font-size: 28px;
        font-weight: 900;
        color: var(--d-text);
        line-height: 1;
        letter-spacing: -1px;
    }

    .db-kpi-label {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: var(--d-muted);
        margin-bottom: 2px;
    }

    .db-kpi-sub {
        font-size: 11px;
        font-weight: 600;
        color: var(--d-subtle);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .db-kpi-sub .up {
        color: var(--d-emerald);
    }

    .db-kpi-sub .down {
        color: var(--d-rose);
    }

    /* ── Main 3-col layout ── */
    .db-main {
        display: grid;
        grid-template-columns: 1fr 310px;
        gap: 20px;
    }

    @media(max-width:1100px) {
        .db-main {
            grid-template-columns: 1fr;
        }
    }

    /* ── What's New Panel — Compact Strip ── */
    .db-whats-new {
        background: linear-gradient(90deg, #0f172a 0%, #1e1b4b 100%);
        border-radius: var(--d-radius);
        padding: 12px 20px;
        border: 1px solid rgba(99, 102, 241, .2);
        position: relative;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .db-wn-inner {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }

    .db-wn-left {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .db-wn-title-txt {
        font-size: 12px;
        font-weight: 800;
        color: #f8fafc;
        white-space: nowrap;
    }

    .db-wn-badge {
        background: var(--d-primary);
        color: #fff;
        font-size: 9px;
        font-weight: 900;
        padding: 2px 8px;
        border-radius: 20px;
        letter-spacing: .5px;
        white-space: nowrap;
    }

    .db-wn-ver-latest {
        background: #22c55e;
        color: #fff;
        font-size: 9px;
        font-weight: 900;
        padding: 2px 7px;
        border-radius: 6px;
        letter-spacing: .5px;
    }

    .db-wn-divider {
        width: 1px;
        height: 20px;
        background: rgba(255, 255, 255, .1);
        flex-shrink: 0;
    }

    .db-wn-bullets {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 16px;
        flex: 1;
    }

    .db-wn-bullet {
        font-size: 11px;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 5px;
        line-height: 1.4;
    }

    .db-wn-bullet::before {
        content: '→';
        color: #6366f1;
        font-weight: 900;
        font-size: 10px;
        flex-shrink: 0;
    }

    .db-wn-link {
        font-size: 11px;
        font-weight: 700;
        color: #818cf8;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 3px;
        flex-shrink: 0;
        white-space: nowrap;
        margin-left: auto;
    }

    .db-wn-link:hover {
        color: #a5b4fc;
    }

    /* ── Live Banner ── */
    .dash-live-banner {
        display: grid;
        grid-template-columns: 1fr 280px;
        border-radius: var(--d-radius);
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, .1);
        min-height: 150px;
    }

    .live-on {
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #0f172a 100%);
        border: 1px solid rgba(220, 38, 38, .2);
    }

    .live-off {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border: 1px solid #334155;
    }

    .live-empty {
        background: var(--d-surface);
        border: 1px dashed #e2e8f0;
    }

    .dlb-info {
        padding: 22px 24px;
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

    .dlb-pulse-ring {
        position: absolute;
        left: -3px;
        top: -3px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        border: 2px solid #dc2626;
        animation: dlbPulse 1.4s ease-out infinite;
    }

    @keyframes dlbPulse {
        0% {
            transform: scale(1);
            opacity: .9
        }

        70%,
        100% {
            transform: scale(2);
            opacity: 0
        }
    }

    .dlb-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #dc2626;
        animation: dlbBlink 1s ease-in-out infinite;
        flex-shrink: 0;
    }

    @keyframes dlbBlink {

        0%,
        100% {
            opacity: 1
        }

        50% {
            opacity: .2
        }
    }

    .dlb-live-text {
        font-size: 11px;
        font-weight: 900;
        color: #dc2626;
        letter-spacing: .15em;
    }

    .dlb-off-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #475569;
        flex-shrink: 0;
    }

    .dlb-off-text {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        letter-spacing: .08em;
    }

    .dlb-title {
        font-size: 18px;
        font-weight: 800;
        color: #f1f5f9;
        margin: 0 0 4px;
        line-height: 1.2;
    }

    .live-empty .dlb-title {
        color: #0f172a;
    }

    .dlb-url {
        font-size: 11px;
        color: #94a3b8;
        margin: 0 0 12px;
        word-break: break-all;
        font-family: monospace;
    }

    .dlb-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .dlb-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: .2s;
        white-space: nowrap;
    }

    .dlb-btn-on {
        background: #dc2626;
        color: #fff;
        box-shadow: 0 4px 12px rgba(220, 38, 38, .3);
    }

    .dlb-btn-on:hover {
        background: #b91c1c;
        transform: translateY(-1px);
        color: #fff;
    }

    .dlb-btn-off {
        background: rgba(255, 255, 255, .1);
        color: #f1f5f9;
        border: 1px solid rgba(255, 255, 255, .15);
    }

    .dlb-btn-off:hover {
        background: rgba(255, 255, 255, .18);
        color: #fff;
    }

    .dlb-btn-settings {
        background: rgba(255, 255, 255, .05);
        color: #94a3b8;
        border: 1px solid rgba(255, 255, 255, .08);
    }

    .dlb-btn-settings:hover {
        background: rgba(255, 255, 255, .12);
        color: #f1f5f9;
    }

    .dlb-btn-view {
        background: #16a34a;
        color: #fff;
    }

    .dlb-btn-view:hover {
        background: #15803d;
        transform: translateY(-1px);
        color: #fff;
    }

    .dlb-preview {
        position: relative;
        background: #000;
        min-height: 150px;
    }

    .dlb-corner-live {
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(220, 38, 38, .9);
        color: #fff;
        font-size: 10px;
        font-weight: 900;
        padding: 4px 10px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 5px;
        pointer-events: none;
        z-index: 3;
    }

    .dlb-dot-sm {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #fff;
        animation: dlbBlink 1s infinite;
    }

    @media(max-width:800px) {
        .dash-live-banner {
            grid-template-columns: 1fr
        }

        .dlb-preview {
            min-height: 180px
        }
    }

    /* ── Section Cards ── */
    .db-card {
        background: var(--d-surface);
        border-radius: var(--d-radius);
        border: 1px solid var(--d-border);
        box-shadow: var(--d-shadow-sm);
        overflow: hidden;
    }

    .db-card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 18px;
        border-bottom: 1px solid var(--d-border);
        background: #fafafa;
    }

    .db-card-head h3 {
        margin: 0;
        font-size: 14px;
        font-weight: 800;
        color: var(--d-text);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .db-card-head h3 .hic {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .db-card-link {
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        padding: 5px 11px;
        border-radius: 7px;
        background: #f1f5f9;
        color: #475569;
        transition: .15s;
        white-space: nowrap;
    }

    .db-card-link:hover {
        background: #e2e8f0;
        color: var(--d-text);
    }

    /* ── Top Posts Table ── */
    .db-post-table {
        width: 100%;
        border-collapse: collapse;
    }

    .db-post-table thead th {
        padding: 9px 14px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: var(--d-muted);
        background: #f8fafc;
        text-align: left;
        border-bottom: 1px solid var(--d-border);
    }

    .db-post-table tbody tr {
        border-bottom: 1px solid #f8fafc;
        transition: .15s;
    }

    .db-post-table tbody tr:hover {
        background: #fafbff;
    }

    .db-post-table tbody tr:last-child {
        border-bottom: none;
    }

    .db-post-table td {
        padding: 11px 14px;
        vertical-align: middle;
    }

    .rank-badge {
        width: 24px;
        height: 24px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 900;
    }

    .rank-1 {
        background: #fef3c7;
        color: #d97706;
    }

    .rank-2 {
        background: #f1f5f9;
        color: #64748b;
    }

    .rank-3 {
        background: #fff7ed;
        color: #c2410c;
    }

    .rank-n {
        background: #f8fafc;
        color: #cbd5e1;
    }

    .views-bar {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .views-bar-track {
        flex: 1;
        height: 4px;
        background: #f1f5f9;
        border-radius: 5px;
        max-width: 55px;
    }

    .views-bar-fill {
        height: 4px;
        background: var(--d-primary);
        border-radius: 5px;
    }

    /* ── Recent Activity ── */
    .db-activity-item {
        display: flex;
        gap: 12px;
        align-items: center;
        padding: 10px 16px;
        border-bottom: 1px solid #f8fafc;
        transition: .15s;
    }

    .db-activity-item:hover {
        background: #fafbff;
    }

    .db-activity-item:last-child {
        border-bottom: none;
    }

    .db-act-thumb {
        width: 44px;
        height: 36px;
        border-radius: 6px;
        object-fit: cover;
        flex-shrink: 0;
        background: #f1f5f9;
    }

    .db-act-title {
        font-size: 12px;
        font-weight: 700;
        color: var(--d-text);
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 180px;
        text-decoration: none;
    }

    .db-act-title:hover {
        color: var(--d-primary);
    }

    .db-act-meta {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 2px;
    }

    .badge-published {
        background: #d1fae5;
        color: #065f46;
        padding: 2px 7px;
        border-radius: 5px;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .badge-draft {
        background: #fef3c7;
        color: #92400e;
        padding: 2px 7px;
        border-radius: 5px;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
    }

    /* ── Quick Actions ── */
    .db-quick-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 8px;
        padding: 14px;
    }

    .db-qa {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: flex-start;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
        transition: .2s;
        border: 1px solid transparent;
    }

    .db-qa:hover {
        transform: translateX(2px);
    }

    .db-qa-icon {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .db-qa-primary {
        background: var(--d-primary);
        color: #fff;
        box-shadow: 0 4px 10px rgba(99, 102, 241, .3);
    }

    .db-qa-primary:hover {
        box-shadow: 0 6px 16px rgba(99, 102, 241, .4);
        color: #fff;
    }

    .db-qa-dark {
        background: #1e293b;
        color: #e2e8f0;
    }

    .db-qa-dark:hover {
        background: #0f172a;
        color: #fff;
    }

    .db-qa-emerald {
        background: #ecfdf5;
        color: #065f46;
        border-color: #a7f3d0;
    }

    .db-qa-emerald:hover {
        background: #d1fae5;
    }

    .db-qa-amber {
        background: #fffbeb;
        color: #92400e;
        border-color: #fde68a;
    }

    .db-qa-amber:hover {
        background: #fef3c7;
    }

    .db-qa-rose {
        background: #fff1f2;
        color: #881337;
        border-color: #fecdd3;
    }

    .db-qa-rose:hover {
        background: #ffe4e6;
    }

    .db-qa-sky {
        background: #f0f9ff;
        color: #075985;
        border-color: #bae6fd;
    }

    .db-qa-sky:hover {
        background: #e0f2fe;
    }

    .db-qa-violet {
        background: #f5f3ff;
        color: #5b21b6;
        border-color: #ddd6fe;
    }

    .db-qa-violet:hover {
        background: #ede9fe;
    }

    .db-qa-orange {
        background: #fff7ed;
        color: #9a3412;
        border-color: #fed7aa;
    }

    .db-qa-orange:hover {
        background: #ffedd5;
    }

    /* ── Category bars ── */
    .db-cat-row {
        margin-bottom: 12px;
    }

    .db-cat-row:last-child {
        margin-bottom: 0;
    }

    .db-cat-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
    }

    .db-cat-name {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 12px;
        font-weight: 700;
        color: var(--d-text);
    }

    .db-cat-icon {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .db-cat-cnt {
        font-size: 11px;
        font-weight: 800;
        color: var(--d-subtle);
    }

    .db-cat-track {
        height: 4px;
        background: #f1f5f9;
        border-radius: 4px;
    }

    .db-cat-fill {
        height: 4px;
        border-radius: 4px;
    }

    /* ── Feedback rows ── */
    .db-msg-row {
        display: flex;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid #f8fafc;
        text-decoration: none;
        transition: .15s;
        color: inherit;
    }

    .db-msg-row:hover {
        background: #fafbff;
    }

    .db-msg-row:last-child {
        border-bottom: none;
    }

    .db-msg-avatar {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: var(--d-primary);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 13px;
        flex-shrink: 0;
    }

    .db-msg-name {
        font-size: 12px;
        font-weight: 800;
        color: var(--d-text);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .db-msg-new {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--d-rose);
        display: inline-block;
    }

    .db-msg-preview {
        font-size: 11px;
        color: var(--d-subtle);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: 1px;
        max-width: 190px;
    }

    /* ── Section gap ── */
    .db-section-gap {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    /* ── 2-col inner ── */
    .db-inner-2col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    @media(max-width:700px) {
        .db-inner-2col {
            grid-template-columns: 1fr;
        }
    }

    @media(max-width:640px) {
        .db-wrap {
            margin: -12px;
            padding: 12px;
        }

        .db-greeting {
            flex-direction: column;
            gap: 14px;
            padding: 20px;
        }

        .db-greeting-actions {
            width: 100%;
        }
    }
</style>

<div class="db-wrap">

    <!-- ── Greeting Banner ── -->
    <div class="db-greeting">
        <div class="db-greeting-left">
            <h2><?php echo $greeting_emoji; ?> <?php echo $greeting; ?>, <?php echo htmlspecialchars($admin_name); ?>!
            </h2>
            <p>
                <span><?php echo date('l, F j, Y'); ?></span>
                <span class="dot">·</span>
                <span><?php echo $today_posts; ?> article<?php echo $today_posts != 1 ? 's' : ''; ?> today</span>
                <span class="dot">·</span>
                <span><?php echo number_format($total_views); ?> total views</span>
                <span class="dot">·</span>
                <span class="db-greet-version">v<?php echo $current_ver; ?></span>
            </p>
        </div>
        <div class="db-greeting-actions">
            <a href="post_add.php" class="db-greet-btn db-greet-btn-primary">
                <i data-feather="plus" style="width:14px;"></i> New Article
            </a>
            <a href="<?php echo BASE_URL; ?>" target="_blank" class="db-greet-btn db-greet-btn-ghost">
                <i data-feather="external-link" style="width:14px;"></i> View Site
            </a>
            <?php if (is_admin()): ?>
                <a href="system_update.php" class="db-greet-btn db-greet-btn-ghost">
                    <i data-feather="refresh-cw" style="width:14px;"></i> Updates
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── KPI Cards ── -->
    <div class="db-kpi-grid">
        <a href="javascript:void(0)" class="db-kpi" style="--kpi-color:#dc2626;--kpi-bg:#fee2e2;">
            <div class="db-kpi-top">
                <div>
                    <div class="db-kpi-label" style="display:flex;align-items:center;gap:5px;">
                        <span style="width:6px;height:6px;border-radius:50%;background:#dc2626;animation:dlbPulse 1.4s ease-out infinite;"></span>
                        Live Visitors
                    </div>
                    <div class="db-kpi-val"><?php echo number_format($live_visitors); ?></div>
                </div>
                <div class="db-kpi-icon"><i data-feather="activity" style="width:20px;"></i></div>
            </div>
            <div class="db-kpi-sub">Active in last 5 mins</div>
        </a>

        <a href="posts.php?status=published" class="db-kpi" style="--kpi-color:#6366f1;--kpi-bg:#eff6ff;">
            <div class="db-kpi-top">
                <div>
                    <div class="db-kpi-label">Published</div>
                    <div class="db-kpi-val"><?php echo number_format($published_posts); ?></div>
                </div>
                <div class="db-kpi-icon"><i data-feather="file-text" style="width:20px;"></i></div>
            </div>
            <div class="db-kpi-sub"><?php echo $draft_posts; ?> drafts · <?php echo $today_posts; ?> today</div>
        </a>

        <a href="posts.php" class="db-kpi" style="--kpi-color:#10b981;--kpi-bg:#d1fae5;">
            <div class="db-kpi-top">
                <div>
                    <div class="db-kpi-label">Total Views</div>
                    <div class="db-kpi-val">
                        <?php echo $total_views >= 1000 ? round($total_views / 1000, 1) . 'K' : number_format($total_views); ?>
                    </div>
                </div>
                <div class="db-kpi-icon"><i data-feather="trending-up" style="width:20px;"></i></div>
            </div>
            <div class="db-kpi-sub"><span class="up">↑ Growing</span></div>
        </a>

        <?php if (is_admin()): ?>
            <a href="ads.php" class="db-kpi" style="--kpi-color:#f59e0b;--kpi-bg:#fef3c7;">
                <div class="db-kpi-top">
                    <div>
                        <div class="db-kpi-label">Ad Clicks</div>
                        <div class="db-kpi-val"><?php echo number_format($total_ad_clicks); ?></div>
                    </div>
                    <div class="db-kpi-icon"><i data-feather="mouse-pointer" style="width:20px;"></i></div>
                </div>
                <div class="db-kpi-sub"><?php echo number_format($total_ad_views); ?> impressions</div>
            </a>

        <?php endif; ?>

        <?php if (!is_reporter()): ?>
            <a href="categories.php" class="db-kpi" style="--kpi-color:#8b5cf6;--kpi-bg:#ede9fe;">
                <div class="db-kpi-top">
                    <div>
                        <div class="db-kpi-label">Categories</div>
                        <div class="db-kpi-val"><?php echo number_format($total_categories); ?></div>
                    </div>
                    <div class="db-kpi-icon"><i data-feather="layers" style="width:20px;"></i></div>
                </div>
                <div class="db-kpi-sub">Active sections</div>
            </a>

            <a href="polls.php" class="db-kpi" style="--kpi-color:#0ea5e9;--kpi-bg:#e0f2fe;">
                <div class="db-kpi-top">
                    <div>
                        <div class="db-kpi-label">Active Polls</div>
                        <div class="db-kpi-val"><?php echo number_format($active_polls); ?></div>
                    </div>
                    <div class="db-kpi-icon"><i data-feather="pie-chart" style="width:20px;"></i></div>
                </div>
                <div class="db-kpi-sub">Running now</div>
            </a>

            <a href="users.php" class="db-kpi" style="--kpi-color:#6366f1;--kpi-bg:#eff6ff;">
                <div class="db-kpi-top">
                    <div>
                        <div class="db-kpi-label">Team / Users</div>
                        <div class="db-kpi-val"><?php echo number_format($total_users); ?></div>
                    </div>
                    <div class="db-kpi-icon"><i data-feather="users" style="width:20px;"></i></div>
                </div>
                <div class="db-kpi-sub">Staff members</div>
            </a>
        <?php endif; ?>

        <?php if ($pending_comments > 0): ?>
            <a href="comments.php" class="db-kpi" style="--kpi-color:#f43f5e;--kpi-bg:#fff1f2;">
                <div class="db-kpi-top">
                    <div>
                        <div class="db-kpi-label">Comments</div>
                        <div class="db-kpi-val"><?php echo number_format($pending_comments); ?></div>
                    </div>
                    <div class="db-kpi-icon"><i data-feather="message-square" style="width:20px;"></i></div>
                </div>
                <div class="db-kpi-sub"><span class="down">Awaiting approval</span></div>
            </a>
        <?php else: ?>
            <a href="ads.php" class="db-kpi" style="--kpi-color:#10b981;--kpi-bg:#d1fae5;">
                <div class="db-kpi-top">
                    <div>
                        <div class="db-kpi-label">Active Ads</div>
                        <div class="db-kpi-val"><?php echo number_format($active_ads); ?></div>
                    </div>
                    <div class="db-kpi-icon"><i data-feather="target" style="width:20px;"></i></div>
                </div>
                <div class="db-kpi-sub"><?php echo $ad_clicks_today; ?> clicks today</div>
            </a>
        <?php endif; ?>

    </div>

    <!-- ── Live Stream Banner ── -->
    <?php if (is_admin() && $live_url): ?>
        <div class="dash-live-banner <?php echo $live_enabled ? 'live-on' : 'live-off'; ?>" style="margin-bottom:20px;">
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
                <p class="dlb-url">
                    <?php echo htmlspecialchars(substr($live_url, 0, 55)) . (strlen($live_url) > 55 ? '…' : ''); ?>
                </p>
                <div class="dlb-actions">
                    <?php if ($live_enabled): ?>
                        <a href="?live_toggle=off" class="dlb-btn dlb-btn-off"><i data-feather="stop-circle"
                                style="width:13px;"></i> Stop Live</a>
                    <?php else: ?>
                        <a href="?live_toggle=on" class="dlb-btn dlb-btn-on"><i data-feather="play-circle"
                                style="width:13px;"></i> Go Live</a>
                    <?php endif; ?>
                    <a href="settings.php?tab=livestream" class="dlb-btn dlb-btn-settings"><i data-feather="settings"
                            style="width:13px;"></i> Configure</a>
                    <?php if ($live_enabled): ?>
                        <a href="<?php echo BASE_URL; ?>" target="_blank" class="dlb-btn dlb-btn-view"><i
                                data-feather="external-link" style="width:13px;"></i> View Site</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($live_vid_id): ?>
                <div class="dlb-preview">
                    <iframe
                        src="https://www.youtube.com/embed/<?php echo htmlspecialchars($live_vid_id); ?>?autoplay=0&mute=1&rel=0&modestbranding=1&controls=0&disablekb=1"
                        style="width:100%;height:100%;border:none;" title="Live Preview"></iframe>
                    <div style="position:absolute;inset:0;background:transparent;z-index:2;"></div>
                    <?php if ($live_enabled): ?>
                        <div class="dlb-corner-live"><span class="dlb-dot-sm"></span>LIVE</div><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ── What's New — Compact Strip ── -->
    <?php if (is_admin() && !empty($changelog_data)):
        $latest = $changelog_data[0];
        $bullets = array_slice($latest['changes'], 0, 3);
        ?>
        <div class="db-whats-new">
            <div class="db-wn-inner">
                <div class="db-wn-left">
                    <div
                        style="width:24px;height:24px;background:rgba(99,102,241,.25);border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-feather="star" style="width:12px;color:#a5b4fc;"></i>
                    </div>
                    <span class="db-wn-title-txt">What's New</span>
                    <span class="db-wn-badge">v<?php echo htmlspecialchars($latest['version']); ?></span>
                    <span class="db-wn-ver-latest">LATEST</span>
                </div>
                <div class="db-wn-divider"></div>
                <div class="db-wn-bullets">
                    <?php foreach ($bullets as $change): ?>
                        <span class="db-wn-bullet"><?php echo htmlspecialchars($change); ?></span>
                    <?php endforeach; ?>
                    <?php if (count($latest['changes']) > 3): ?>
                        <span class="db-wn-bullet" style="color:#6366f1;">+<?php echo count($latest['changes']) - 3; ?>
                            more</span>
                    <?php endif; ?>
                </div>
                <a href="system_update.php" class="db-wn-link">
                    Full log <i data-feather="arrow-right" style="width:11px;"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- ── Trends & Section Pulse Row ── -->
    <?php
    $trends_grid_cols = !is_reporter() ? 'grid-template-columns: 2fr 1fr;' : 'grid-template-columns: 1fr;';
    ?>
    <div class="db-trends-pulse-row"
        style="display: grid; <?php echo $trends_grid_cols; ?> gap: 20px; margin-bottom: 24px;">

        <!-- LEFT: Trends Chart -->
        <div class="db-card"
            style="padding: 25px; border-radius: var(--d-radius); background: var(--d-surface); box-shadow: var(--d-shadow); margin-bottom: 0;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h3
                        style="margin: 0; font-size: 17px; font-weight: 800; color: var(--d-text); display: flex; align-items: center; gap: 8px;">
                        <i data-feather="activity" style="color: var(--d-primary); width: 18px; height: 18px;"></i>
                        Traffic & Engagement Trends
                    </h3>
                    <p style="margin: 4px 0 0; font-size: 13px; color: var(--d-subtle);">Monitor real-time page views
                        and ad clicks over time</p>
                </div>
                <div style="display: flex; background: #f1f5f9; padding: 4px; border-radius: 8px; gap: 4px;">
                    <button onclick="switchTrendTimeframe('today')" id="btn-trend-today"
                        class="trend-timeframe-btn">Today</button>
                    <button onclick="switchTrendTimeframe('7days')" id="btn-trend-7days"
                        class="trend-timeframe-btn active">7 Days</button>
                    <button onclick="switchTrendTimeframe('30days')" id="btn-trend-30days"
                        class="trend-timeframe-btn">30 Days</button>
                </div>
            </div>

            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="trendsChart"></canvas>
            </div>
        </div>

        <!-- RIGHT: Section Pulse -->
        <?php if (!is_reporter()): ?>
            <div class="db-card"
                style="padding: 25px; border-radius: var(--d-radius); background: var(--d-surface); box-shadow: var(--d-shadow); margin-bottom: 0; display: flex; flex-direction: column;">
                <div class="db-card-head" style="margin-bottom: 20px;">
                    <h3>
                        <span class="hic" style="background:#ede9fe;color:#7c3aed;"><i data-feather="bar-chart-2"
                                style="width:14px;"></i></span>
                        Section Pulse
                    </h3>
                    <a href="categories.php" class="db-card-link">Manage</a>
                </div>
                <div style="flex: 1; display: flex; flex-direction: column; justify-content: center; gap: 12px;">
                    <?php foreach ($cat_stats as $cat): ?>
                        <div class="db-cat-row" style="margin-bottom: 0;">
                            <div class="db-cat-top">
                                <div class="db-cat-name">
                                    <div class="db-cat-icon" style="background:<?php echo $cat['color']; ?>18;">
                                        <i data-feather="<?php echo $cat['icon']; ?>"
                                            style="width:13px;color:<?php echo $cat['color']; ?>;"></i>
                                    </div>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </div>
                                <span class="db-cat-cnt"><?php echo $cat['cnt']; ?></span>
                            </div>
                            <div class="db-cat-track">
                                <div class="db-cat-fill"
                                    style="width:<?php echo $max_cnt > 0 ? round(($cat['cnt'] / $max_cnt) * 100) : 0; ?>%;background:<?php echo $cat['color']; ?>;box-shadow:0 0 6px <?php echo $cat['color']; ?>44;">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <style>
        .trend-timeframe-btn {
            border: none;
            background: transparent;
            padding: 6px 12px;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--d-subtle);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .trend-timeframe-btn:hover {
            color: var(--d-text);
        }

        .trend-timeframe-btn.active {
            background: white;
            color: var(--d-primary);
            box-shadow: var(--d-shadow-sm);
        }

        @media(max-width: 1024px) {
            .db-trends-pulse-row {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const trendsData = <?php echo json_encode($trends_data); ?>;
        let trendsChart = null;

        function renderTrendsChart(timeframe) {
            const ctx = document.getElementById('trendsChart').getContext('2d');
            const dataObj = trendsData[timeframe];

            if (trendsChart) {
                trendsChart.destroy();
            }

            trendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dataObj.labels,
                    datasets: [
                        {
                            label: 'Page Views',
                            data: dataObj.views,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.05)',
                            borderWidth: 3,
                            tension: 0.35,
                            fill: true,
                            pointBackgroundColor: '#6366f1',
                            pointHoverRadius: 7
                        },
                        {
                            label: 'Ad Clicks',
                            data: dataObj.clicks,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.05)',
                            borderWidth: 3,
                            tension: 0.35,
                            fill: true,
                            pointBackgroundColor: '#10b981',
                            pointHoverRadius: 7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'Inter, sans-serif',
                                    weight: '600',
                                    size: 12
                                },
                                boxWidth: 15,
                                padding: 15
                            }
                        },
                        tooltip: {
                            padding: 12,
                            bodyFont: { family: 'Inter, sans-serif', size: 13 },
                            titleFont: { family: 'Inter, sans-serif', weight: '700', size: 13 },
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { family: 'Inter, sans-serif', weight: '500', size: 11 },
                                color: '#64748b'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                font: { family: 'Inter, sans-serif', weight: '500', size: 11 },
                                color: '#64748b',
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        function switchTrendTimeframe(timeframe) {
            document.querySelectorAll('.trend-timeframe-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById('btn-trend-' + timeframe).classList.add('active');
            renderTrendsChart(timeframe);
        }

        document.addEventListener('DOMContentLoaded', function () {
            renderTrendsChart('7days'); // Default to 7 days view
            switchTrendTimeframe('7days');
        });

        function clearPhotoOfDayAiInput() {
            document.getElementById('photo_of_day_ai_url').value = '';
            const file = document.getElementById('photo_of_day_file').files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    let img = document.getElementById('photoOfDayPreviewImg');
                    if (!img) {
                        img = document.createElement('img');
                        img.id = 'photoOfDayPreviewImg';
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = 'cover';
                        const container = document.getElementById('photoOfDayPreviewContainer');
                        container.innerHTML = '';
                        container.appendChild(img);
                    }
                    img.src = e.target.result;
                    const placeholder = document.getElementById('photoOfDayPlaceholder');
                    if (placeholder) placeholder.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        }

        async function generatePhotoOfDayAI() {
            const titleInput = document.querySelector('input[name="photo_of_day_title"]');
            const titleVal = titleInput ? titleInput.value.trim() : '';

            if (!titleVal) {
                alert("Please write a Photo Title first. It will be used as the prompt to generate the AI image.");
                if (titleInput) titleInput.focus();
                return;
            }

            const loader = document.getElementById('photoOfDayAiLoader');
            const previewImg = document.getElementById('photoOfDayPreviewImg');
            const placeholder = document.getElementById('photoOfDayPlaceholder');
            const aiUrlInput = document.getElementById('photo_of_day_ai_url');
            const aiBtn = document.getElementById('photoOfDayAiBtn');

            if (loader) loader.style.display = 'flex';
            if (aiBtn) {
                aiBtn.disabled = true;
                aiBtn.innerHTML = '<i data-feather="loader" style="animation: spin 1s linear infinite; width: 11px; height: 11px;"></i> Generating...';
                feather.replace();
            }

            try {
                const promptEncoded = encodeURIComponent(titleVal);
                const randomSeed = Math.floor(Math.random() * 1000000);
                const imageUrl = `https://image.pollinations.ai/prompt/${promptEncoded}?width=1000&height=650&nologo=true&seed=${randomSeed}`;

                let imgEl = document.getElementById('photoOfDayPreviewImg');
                if (!imgEl) {
                    imgEl = document.createElement('img');
                    imgEl.id = 'photoOfDayPreviewImg';
                    imgEl.style.width = '100%';
                    imgEl.style.height = '100%';
                    imgEl.style.objectFit = 'cover';
                    const container = document.getElementById('photoOfDayPreviewContainer');
                    container.innerHTML = '';
                    container.appendChild(imgEl);
                    container.appendChild(loader);
                }
                if (placeholder) placeholder.style.display = 'none';

                aiUrlInput.value = imageUrl;

                imgEl.onload = function () {
                    if (loader) loader.style.display = 'none';
                    if (aiBtn) {
                        aiBtn.disabled = false;
                        aiBtn.innerHTML = '<i data-feather="cpu" style="width: 11px; height: 11px;"></i> Use AI';
                        feather.replace();
                    }
                };
                imgEl.onerror = function () {
                    if (loader) loader.style.display = 'none';
                    if (aiBtn) {
                        aiBtn.disabled = false;
                        aiBtn.innerHTML = '<i data-feather="cpu" style="width: 11px; height: 11px;"></i> Use AI';
                        feather.replace();
                    }
                    alert('Failed to load generated image.');
                };

                imgEl.src = imageUrl;
                imgEl.style.display = 'block';
            } catch (err) {
                console.error(err);
                alert('Failed to generate AI Photo of the Day.');
                if (loader) loader.style.display = 'none';
                if (aiBtn) {
                    aiBtn.disabled = false;
                    aiBtn.innerHTML = '<i data-feather="cpu" style="width: 11px; height: 11px;"></i> Use AI';
                    feather.replace();
                }
            }
        }
    </script>

    <!-- ── Main 2-Column Layout ── -->
    <div class="db-main">

        <!-- LEFT col -->
        <div class="db-section-gap">

            <!-- Top Performing Articles -->
            <div class="db-card">
                <div class="db-card-head">
                    <h3>
                        <span class="hic" style="background:#fef3c7;color:#d97706;"><i data-feather="award"
                                style="width:15px;"></i></span>
                        Top Performing Articles
                    </h3>
                    <a href="posts.php" class="db-card-link">View All →</a>
                </div>
                <div style="overflow-x:auto;">
                    <table class="db-post-table">
                        <thead>
                            <tr>
                                <th style="width:38px;text-align:center;">#</th>
                                <th>Article</th>
                                <th>Category</th>
                                <th>Views</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($top_posts)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;color:#94a3b8;padding:40px;font-size:13px;">No
                                        published articles yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($top_posts as $i => $post):
                                    $cats = explode(',', $post['cats'] ?? '');
                                    $colors = explode(',', $post['colors'] ?? '#6366f1');
                                    $rankClass = ['rank-1', 'rank-2', 'rank-3'][$i] ?? 'rank-n';
                                    ?>
                                    <tr>
                                        <td style="text-align:center;">
                                            <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $i + 1; ?></span>
                                        </td>
                                        <td style="max-width:260px;">
                                            <a href="<?php echo BASE_URL; ?>article/<?php echo $post['slug']; ?>"
                                                target="_blank"
                                                style="font-weight:700;color:var(--d-text);text-decoration:none;font-size:12px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:2px;">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                            <span
                                                style="font-size:10px;color:var(--d-muted);"><?php echo date('M j, Y', strtotime($post['published_at'])); ?></span>
                                        </td>
                                        <td>
                                            <span
                                                style="background:<?php echo ($colors[0] ?? '#6366f1'); ?>18;color:<?php echo $colors[0] ?? '#6366f1'; ?>;padding:2px 9px;border-radius:20px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap;">
                                                <?php echo htmlspecialchars($cats[0] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="views-bar">
                                                <div class="views-bar-track">
                                                    <div class="views-bar-fill"
                                                        style="width:<?php echo $top_posts[0]['views'] > 0 ? round(($post['views'] / $top_posts[0]['views']) * 100) : 0; ?>;">
                                                    </div>
                                                </div>
                                                <span
                                                    style="font-weight:800;font-size:12px;color:var(--d-text);white-space:nowrap;"><?php echo number_format($post['views']); ?></span>
                                            </div>
                                        </td>
                                        <td style="text-align:right;">
                                            <a href="post_edit.php?id=<?php echo $post['id']; ?>"
                                                style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#eff6ff;color:#3b82f6;border-radius:6px;font-size:10px;font-weight:700;text-decoration:none;">
                                                <i data-feather="edit-2" style="width:11px;"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2-col row: Recent Activity + Ad Pulse -->
            <div class="db-inner-2col">

                <!-- Recent Activity -->
                <div class="db-card">
                    <div class="db-card-head">
                        <h3>
                            <span class="hic" style="background:#eff6ff;color:#6366f1;"><i data-feather="clock"
                                    style="width:14px;"></i></span>
                            Recent Posts
                        </h3>
                        <a href="post_add.php" class="db-card-link" style="background:#eff6ff;color:var(--d-primary);">+
                            New</a>
                    </div>
                    <div>
                        <?php foreach ($recent_posts as $post):
                            $cols = explode(',', $post['colors'] ?? '#6366f1');
                            $thumb = get_post_thumbnail($post['featured_image'] ?? null);
                            ?>
                            <div class="db-activity-item">
                                <img src="<?php echo $thumb; ?>" class="db-act-thumb" alt="">
                                <div style="flex:1;min-width:0;">
                                    <a href="post_edit.php?id=<?php echo $post['id']; ?>" class="db-act-title">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                    <div class="db-act-meta">
                                        <span
                                            class="badge-<?php echo $post['status']; ?>"><?php echo $post['status']; ?></span>
                                        <span
                                            style="font-size:10px;color:var(--d-muted);"><?php echo date('M j', strtotime($post['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (is_admin()): ?>
                    <!-- Ad Pulse -->
                    <div class="db-card">
                        <div class="db-card-head">
                            <h3>
                                <span class="hic" style="background:#fff1f2;color:#f43f5e;"><i data-feather="bar-chart"
                                        style="width:14px;"></i></span>
                                Ad Pulse
                            </h3>
                            <a href="ad_click_history.php" class="db-card-link"
                                style="background:#fff1f2;color:#f43f5e;">History</a>
                        </div>
                        <div>
                            <?php foreach ($top_ads as $ad):
                                $ctr = $ad['impressions'] > 0 ? round(($ad['clicks'] / $ad['impressions']) * 100, 1) : 0;
                                ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:11px 16px;border-bottom:1px solid #f8fafc;transition:.15s;"
                                    onmouseover="this.style.background='#fafbff'"
                                    onmouseout="this.style.background='transparent'">
                                    <div style="min-width:0;flex:1;margin-right:10px;">
                                        <div
                                            style="font-weight:700;color:var(--d-text);font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            <?php echo htmlspecialchars($ad['name']); ?>
                                        </div>
                                        <div style="font-size:10px;color:var(--d-muted);font-weight:700;margin-top:1px;">
                                            <?php echo str_replace('_', ' ', $ad['location']); ?>
                                        </div>
                                        <div style="font-size:10px;color:var(--d-subtle);margin-top:2px;">
                                            <?php echo number_format($ad['clicks']); ?> clicks ·
                                            <?php echo number_format($ad['impressions']); ?> views
                                        </div>
                                    </div>
                                    <div style="text-align:center;flex-shrink:0;">
                                        <div style="font-size:14px;font-weight:900;color:var(--d-text);"><?php echo $ctr; ?>%
                                        </div>
                                        <div
                                            style="font-size:9px;font-weight:800;text-transform:uppercase;color:var(--d-muted);">
                                            CTR</div>
                                    </div>
                                </div>
                            <?php endforeach;
                            if (empty($top_ads)): ?>
                                <p style="text-align:center;padding:35px;color:var(--d-muted);font-size:12px;">No ads created
                                    yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT col -->
        <div class="db-section-gap">

            <!-- Quick Actions -->
            <div class="db-card">
                <div class="db-card-head">
                    <h3>
                        <span class="hic" style="background:#fef3c7;color:#d97706;"><i data-feather="zap"
                                style="width:14px;"></i></span>
                        Quick Actions
                    </h3>
                </div>
                <div class="db-quick-grid">
                    <a href="post_add.php" class="db-qa db-qa-primary">
                        <div class="db-qa-icon" style="background:rgba(255,255,255,.2);"><i data-feather="plus"
                                style="width:15px;"></i></div>
                        <span>New Article</span>
                    </a>

                    <?php if (!is_reporter()): ?>
                        <a href="categories.php" class="db-qa db-qa-dark">
                            <div class="db-qa-icon" style="background:rgba(255,255,255,.1);"><i data-feather="layers"
                                    style="width:15px;"></i></div>
                            <span>Categories</span>
                        </a>
                        <a href="polls.php" class="db-qa db-qa-emerald">
                            <div class="db-qa-icon" style="background:rgba(16,185,129,.15);"><i data-feather="pie-chart"
                                    style="width:15px;"></i></div>
                            <span>Polls</span>
                        </a>
                    <?php endif; ?>

                    <?php if (is_admin()): ?>
                        <a href="ads.php" class="db-qa db-qa-amber">
                            <div class="db-qa-icon" style="background:rgba(245,158,11,.15);"><i data-feather="target"
                                    style="width:15px;"></i></div>
                            <span>Ads</span>
                        </a>
                        <a href="users.php" class="db-qa db-qa-sky">
                            <div class="db-qa-icon" style="background:rgba(14,165,233,.15);"><i data-feather="users"
                                    style="width:15px;"></i></div>
                            <span>Staff</span>
                        </a>
                        <a href="settings.php" class="db-qa db-qa-rose">
                            <div class="db-qa-icon" style="background:rgba(244,63,94,.15);"><i data-feather="settings"
                                    style="width:15px;"></i></div>
                            <span>Settings</span>
                        </a>
                        <a href="settings.php?tab=appearance" class="db-qa db-qa-violet">
                            <div class="db-qa-icon" style="background:rgba(139,92,246,.15);"><i data-feather="layout"
                                    style="width:15px;"></i></div>
                            <span>Themes</span>
                        </a>
                    <?php endif; ?>

                    <?php if (!is_reporter()): ?>
                        <a href="comments.php" class="db-qa db-qa-orange">
                            <div class="db-qa-icon" style="background:rgba(249,115,22,.15);"><i
                                    data-feather="message-square" style="width:15px;"></i></div>
                            <span>Comments<?php if ($pending_comments > 0): ?> <span
                                        style="background:#f43f5e;color:#fff;font-size:9px;padding:1px 5px;border-radius:10px;margin-left:2px;"><?php echo $pending_comments; ?></span><?php endif; ?></span>
                        </a>
                    <?php endif; ?>
                </div>
                <div style="margin: 0 14px 14px; display:flex; gap:8px;">
                    <a href="<?php echo BASE_URL; ?>" target="_blank"
                        style="flex:1;display:flex;align-items:center;justify-content:center;gap:7px;padding:10px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:8px;text-decoration:none;font-size:12px;font-weight:700;transition:.2s;"
                        onmouseover="this.style.background='#d1fae5'" onmouseout="this.style.background='#ecfdf5'">
                        <i data-feather="globe" style="width:15px;"></i> Open Website
                    </a>
                    <?php if (is_admin()): ?>
                        <a href="system_update.php"
                            style="flex:1;display:flex;align-items:center;justify-content:center;gap:7px;padding:10px;background:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;border-radius:8px;text-decoration:none;font-size:12px;font-weight:700;transition:.2s;"
                            onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                            <i data-feather="refresh-cw" style="width:15px;"></i> Check Updates
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Photo of the Day Management Card -->
            <?php if (is_admin()): ?>
                <div class="db-card" style="padding: 20px; margin-bottom: 20px;">
                    <div class="db-card-head"
                        style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <h3
                            style="font-size: 14.5px; font-weight: 800; display: flex; align-items: center; gap: 8px; margin: 0;">
                            <span class="hic"
                                style="background:#fff7ed;color:#ea580c;width:24px;height:24px;border-radius:6px;display:flex;align-items:center;justify-content:center;"><i
                                    data-feather="image" style="width:13px;height:13px;"></i></span>
                            Photo of the Day
                        </h3>
                        <button type="button" id="photoOfDayAiBtn" onclick="generatePhotoOfDayAI()"
                            style="background: #eef2ff; color: #6366f1; border: none; padding: 6px 10px; font-size: 11px; font-weight: 800; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                            <i data-feather="cpu" style="width: 11px; height: 11px;"></i> Use AI
                        </button>
                    </div>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="photo_of_day_ai_url" id="photo_of_day_ai_url">
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div>
                                <label
                                    style="font-size: 11px; font-weight: 800; color: #475569; display: block; margin-bottom: 5px; text-transform: uppercase;">Photo
                                    Title</label>
                                <input type="text" name="photo_of_day_title" class="form-control"
                                    style="width:100%; font-size:12px; padding: 6px 10px; border-radius:6px; border:1px solid #cbd5e1;"
                                    placeholder="e.g. Panchayat Sunset"
                                    value="<?php echo htmlspecialchars(get_setting('photo_of_day_title')); ?>">
                            </div>

                            <div>
                                <label
                                    style="font-size: 11px; font-weight: 800; color: #475569; display: block; margin-bottom: 5px; text-transform: uppercase;">Caption
                                    / Story</label>
                                <textarea name="photo_of_day_caption" class="form-control" rows="2"
                                    style="width:100%; font-size:12px; padding: 6px 10px; border-radius:6px; border:1px solid #cbd5e1; resize:none;"
                                    placeholder="Write a short description..."><?php echo htmlspecialchars(get_setting('photo_of_day_caption')); ?></textarea>
                            </div>

                            <div>
                                <label
                                    style="font-size: 11px; font-weight: 800; color: #475569; display: block; margin-bottom: 5px; text-transform: uppercase;">Image
                                    Preview & Upload</label>
                                <div id="photoOfDayPreviewContainer"
                                    style="position: relative; margin-bottom: 8px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; height: 120px; background: #f8fafc; display: flex; align-items: center; justify-content: center;">
                                    <?php if (get_setting('photo_of_day_image')): ?>
                                        <img id="photoOfDayPreviewImg"
                                            src="<?php echo BASE_URL; ?>assets/images/<?php echo get_setting('photo_of_day_image'); ?>"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <span id="photoOfDayPlaceholder"
                                            style="font-size: 11px; color: #94a3b8; font-weight: 600;">No photo uploaded
                                            yet.</span>
                                    <?php endif; ?>
                                    <div id="photoOfDayAiLoader"
                                        style="display: none; position: absolute; inset: 0; background: rgba(255,255,255,0.85); flex-direction: column; align-items: center; justify-content: center; gap: 8px; font-size: 11px; font-weight: 800; color: #6366f1; z-index: 10;">
                                        <i data-feather="loader"
                                            style="animation: spin 1s linear infinite; width: 18px; height: 18px;"></i>
                                        Generating with AI...
                                    </div>
                                </div>
                                <input type="file" name="photo_of_day_image" id="photo_of_day_file" accept="image/*"
                                    style="font-size: 11px; width: 100%;" onchange="clearPhotoOfDayAiInput()">
                            </div>

                            <button type="submit" name="save_photo_of_day" class="btn btn-primary"
                                style="width: 100%; padding: 8px; font-size: 12px; font-weight: 800; border-radius: 6px; cursor: pointer;">
                                Upload Image and Save
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>





        </div><!-- right col -->
    </div><!-- db-main -->

</div><!-- db-wrap -->

<?php include 'includes/footer.php'; ?>