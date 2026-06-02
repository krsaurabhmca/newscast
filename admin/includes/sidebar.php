<?php
$current_page = basename($_SERVER['PHP_SELF']);
$unread_count = 0;
try { $unread_count = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status='new'")->fetchColumn(); } catch(Exception $e){}
$v_json = @json_decode(@file_get_contents(__DIR__ . '/../../version.json'), true);
$app_v  = $v_json['version'] ?? '1.0.0';

// Helper: is page active?
function sb_active(string $page, array|string $pages = []): string {
    global $current_page;
    $pages = is_array($pages) ? $pages : [$pages];
    $pages[] = $page;
    return in_array($current_page, $pages) ? 'active' : '';
}
?>

<style>
/* ════════════════════════════════
   SIDEBAR — Premium Redesign
   ════════════════════════════════ */
.sidebar {
    width: 240px;
    height: 100vh;
    background: #0f172a;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    overflow: hidden;
    transition: width .25s cubic-bezier(.4,0,.2,1);
    position: fixed;
    z-index: 100;
}

/* ── Logo Bar ── */
.sb-logo {
    display: flex; align-items: center; gap: 11px;
    padding: 18px 16px 16px;
    border-bottom: 1px solid rgba(255,255,255,.06);
    text-decoration: none; flex-shrink: 0;
}
.sb-logo-mark {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg,#6366f1,#8b5cf6);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 900; font-size: 15px;
    box-shadow: 0 4px 12px rgba(99,102,241,.4);
}
.sb-logo-text .sb-site-name { font-size: 16px; font-weight: 800; color: #f8fafc; letter-spacing: -.2px; line-height: 1; }
.sb-logo-text .sb-tag       { font-size: 9px; font-weight: 700; color: #475569; letter-spacing: 1.5px; text-transform: uppercase; }

/* ── Quick New Article Button ── */
.sb-new-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    margin: 14px 14px 8px;
    padding: 10px;
    background: linear-gradient(135deg,#6366f1,#8b5cf6);
    color: #fff; border-radius: 10px; text-decoration: none;
    font-size: 13px; font-weight: 700;
    box-shadow: 0 4px 14px rgba(99,102,241,.35);
    transition: .2s; flex-shrink: 0;
}
.sb-new-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(99,102,241,.45); }
.sb-new-btn i { width: 16px; height: 16px; }

/* ── Nav Scroll Area ── */
.sb-nav {
    flex: 1; overflow-y: auto; overflow-x: hidden;
    padding: 4px 10px 10px;
    scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.08) transparent;
}
.sb-nav::-webkit-scrollbar { width: 3px; }
.sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 2px; }

/* ── Section Labels ── */
.sb-section-label {
    font-size: 9.5px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 1.2px; color: #334155;
    padding: 16px 8px 6px;
    display: flex; align-items: center; gap: 6px;
}
.sb-section-label::after {
    content: ''; flex: 1; height: 1px; background: rgba(255,255,255,.04);
}

/* ── Nav Items ── */
.sb-nav ul { list-style: none; margin: 0; padding: 0; }
.sb-nav ul li { margin-bottom: 1px; }

.sb-nav ul li a {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 10px; border-radius: 9px;
    color: #94a3b8; font-size: 13px; font-weight: 600;
    text-decoration: none; transition: .15s;
    position: relative; white-space: nowrap; overflow: hidden;
}
.sb-nav ul li a:hover {
    background: rgba(255,255,255,.06);
    color: #e2e8f0;
}
.sb-nav ul li a.active {
    background: rgba(99,102,241,.18);
    color: #a5b4fc;
    font-weight: 700;
}
.sb-nav ul li a.active::before {
    content: '';
    position: absolute; left: 0; top: 20%; bottom: 20%;
    width: 3px; border-radius: 0 3px 3px 0;
    background: #6366f1;
}

/* Icon wrapper */
.sb-nav ul li a .sbi {
    width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,.04);
    transition: .15s;
}
.sb-nav ul li a:hover .sbi { background: rgba(255,255,255,.08); }
.sb-nav ul li a.active .sbi { background: rgba(99,102,241,.25); }
.sb-nav ul li a .sbi svg { width: 15px; height: 15px; }

/* Badges */
.sb-badge {
    margin-left: auto; flex-shrink: 0;
    background: #ef4444; color: #fff;
    font-size: 9px; font-weight: 900;
    padding: 2px 6px; border-radius: 20px;
    line-height: 1.4;
}
.sb-badge-new {
    margin-left: auto; flex-shrink: 0;
    background: rgba(16,185,129,.15); color: #10b981;
    font-size: 9px; font-weight: 800;
    padding: 2px 6px; border-radius: 20px;
    border: 1px solid rgba(16,185,129,.2);
}

/* ── Divider ── */
.sb-divider { height: 1px; background: rgba(255,255,255,.04); margin: 6px 8px; }

/* ── Version pill ── */
.sb-version {
    text-align: center; padding: 8px;
    font-size: 9px; color: #1e293b; letter-spacing: .5px; font-weight: 700;
}

/* ── Collapsed state ── */
.sidebar-collapsed .sidebar { width: 58px; }
.sidebar-collapsed .sb-logo-text,
.sidebar-collapsed .sb-new-btn span,
.sidebar-collapsed .sb-section-label,
.sidebar-collapsed .sb-nav ul li a span,
.sidebar-collapsed .sb-badge,
.sidebar-collapsed .sb-badge-new,
.sidebar-collapsed .sb-version { display: none; }
.sidebar-collapsed .sb-new-btn { padding: 10px; justify-content: center; }
.sidebar-collapsed .sb-nav ul li a { justify-content: center; padding: 9px 0; }
.sidebar-collapsed .sb-nav ul li a::before { display: none; }

/* Tooltip on collapsed */
.sidebar-collapsed .sb-nav ul li { position: relative; }
.sidebar-collapsed .sb-nav ul li a .sbi { margin: 0; }
</style>

<div class="sidebar">

    <!-- Logo -->
    <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="sb-logo">
        <div class="sb-logo-mark">NC</div>
        <div class="sb-logo-text">
            <div class="sb-site-name">NewsCast</div>
            <div class="sb-tag">Admin Panel</div>
        </div>
    </a>

    <!-- Quick New Article -->
    <a href="post_add.php" class="sb-new-btn">
        <i data-feather="plus"></i>
        <span>New Article</span>
    </a>

    <!-- Scrollable Nav -->
    <nav class="sb-nav">

        <!-- ── OVERVIEW ── -->
        <div class="sb-section-label">Overview</div>
        <ul>
            <li>
                <a href="dashboard.php" class="<?php echo sb_active('dashboard.php'); ?>">
                    <span class="sbi"><i data-feather="grid"></i></span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="feedback.php" class="<?php echo sb_active('feedback.php'); ?>">
                    <span class="sbi"><i data-feather="mail"></i></span>
                    <span>Inbox</span>
                    <?php if ($unread_count > 0): ?>
                        <span class="sb-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="timeline.php" class="<?php echo sb_active('timeline.php'); ?>">
                    <span class="sbi"><i data-feather="calendar"></i></span>
                    <span>Event Timeline</span>
                </a>
            </li>
        </ul>

        <!-- ── CONTENT ── -->
        <div class="sb-section-label">Content</div>
        <ul>
            <li>
                <a href="posts.php" class="<?php echo sb_active('posts.php', ['post_add.php','post_edit.php']); ?>">
                    <span class="sbi"><i data-feather="edit-3"></i></span>
                    <span>Articles</span>
                </a>
            </li>
            <li>
                <a href="categories.php" class="<?php echo sb_active('categories.php'); ?>">
                    <span class="sbi"><i data-feather="layers"></i></span>
                    <span>Categories</span>
                </a>
            </li>
            <li>
                <a href="tags.php" class="<?php echo sb_active('tags.php'); ?>">
                    <span class="sbi"><i data-feather="tag"></i></span>
                    <span>Tags</span>
                </a>
            </li>
            <li>
                <a href="polls.php" class="<?php echo sb_active('polls.php'); ?>">
                    <span class="sbi"><i data-feather="pie-chart"></i></span>
                    <span>Polls</span>
                </a>
            </li>
            <?php if (get_setting('ebook_magazine_enabled', 'yes') == 'yes'): ?>
            <li>
                <a href="magazines.php" class="<?php echo sb_active('magazines.php'); ?>">
                    <span class="sbi"><i data-feather="book-open"></i></span>
                    <span>Magazines</span>
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="wp_auto_import.php" class="<?php echo sb_active('wp_auto_import.php'); ?>">
                    <span class="sbi"><i data-feather="download-cloud"></i></span>
                    <span>WP Import</span>
                </a>
            </li>
        </ul>

        <?php if (is_admin()): ?>
        <!-- ── ADS ── -->
        <div class="sb-section-label">Advertising</div>
        <ul>
            <li>
                <a href="ads.php" class="<?php echo sb_active('ads.php'); ?>">
                    <span class="sbi"><i data-feather="target"></i></span>
                    <span>Ad Campaigns</span>
                </a>
            </li>
            <li>
                <a href="ad_click_history.php" class="<?php echo sb_active('ad_click_history.php'); ?>">
                    <span class="sbi"><i data-feather="mouse-pointer"></i></span>
                    <span>Click History</span>
                </a>
            </li>
            <li>
                <a href="social_share.php" class="<?php echo sb_active('social_share.php'); ?>">
                    <span class="sbi"><i data-feather="share-2"></i></span>
                    <span>Auto Share</span>
                </a>
            </li>
        </ul>

        <!-- ── TEAM ── -->
        <div class="sb-section-label">Team</div>
        <ul>
            <li>
                <a href="users.php" class="<?php echo sb_active('users.php'); ?>">
                    <span class="sbi"><i data-feather="users"></i></span>
                    <span>Staff Members</span>
                </a>
            </li>
            <li>
                <a href="reporter_idcard.php" class="<?php echo sb_active('reporter_idcard.php'); ?>">
                    <span class="sbi"><i data-feather="credit-card"></i></span>
                    <span>ID Cards</span>
                </a>
            </li>
            <li>
                <a href="reporter_letter.php" class="<?php echo sb_active('reporter_letter.php'); ?>">
                    <span class="sbi"><i data-feather="file-text"></i></span>
                    <span>Joining Letter</span>
                </a>
            </li>
        </ul>

        <!-- ── SYSTEM ── -->
        <div class="sb-section-label">System</div>
        <ul>
            <li>
                <a href="settings.php" class="<?php echo sb_active('settings.php'); ?>">
                    <span class="sbi"><i data-feather="settings"></i></span>
                    <span>Site Settings</span>
                </a>
            </li>
            <li>
                <a href="page_about.php" class="<?php echo sb_active('page_about.php'); ?>">
                    <span class="sbi"><i data-feather="info"></i></span>
                    <span>About Page</span>
                </a>
            </li>
            <li>
                <a href="change_password.php" class="<?php echo sb_active('change_password.php'); ?>">
                    <span class="sbi"><i data-feather="shield"></i></span>
                    <span>Security</span>
                </a>
            </li>
            <li>
                <a href="system_update.php" class="<?php echo sb_active('system_update.php'); ?>">
                    <span class="sbi" style="background:rgba(59,130,246,.15);"><i data-feather="download-cloud" style="color:#3b82f6;"></i></span>
                    <span style="color:#60a5fa;font-weight:700;">System Update</span>
                    <span class="sb-badge-new">v<?php echo $app_v; ?></span>
                </a>
            </li>
        </ul>
        <?php endif; ?>

    </nav><!-- /sb-nav -->



    <div class="sb-version">v<?php echo $app_v; ?></div>

</div><!-- /sidebar -->
