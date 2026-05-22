<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.07);">
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="logo" style="margin-bottom: 0; padding: 0; gap: 12px;">
            <div style="background: var(--primary); background: linear-gradient(135deg, var(--primary) 0%, #818cf8 100%); color: #fff; width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 17px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(99,102,241,0.4);">NC</div>
            <div style="display: flex; flex-direction: column; gap: 3px;">
                <span style="font-size: 17px; font-weight: 800; color: #fff; letter-spacing: -0.3px; line-height: 1;">NewsCast</span>
                <span style="font-size: 9px; font-weight: 700; color: #475569; letter-spacing: 2px; text-transform: uppercase; line-height: 1;">Admin Panel</span>
            </div>
        </a>
    </div>

    <div style="padding: 0 15px;">
        <p style="font-size: 11px; font-weight: 800; color: #475569; letter-spacing: 1px; text-transform: uppercase; margin: 20px 0 10px 10px;">DASHBOARD</p>
        <ul class="nav-links">
            <li>
                <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i data-feather="grid" style="width: 18px;"></i> Overview
                </a>
            </li>
            <li>
                <a href="timeline.php" class="<?php echo $current_page == 'timeline.php' ? 'active' : ''; ?>">
                    <i data-feather="calendar" style="width: 18px;"></i> Event Timeline
                </a>
            </li>
            <li>
                <?php $unread_count = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'new'")->fetchColumn(); ?>
                <a href="feedback.php" class="<?php echo $current_page == 'feedback.php' ? 'active' : ''; ?>" style="position: relative;">
                    <i data-feather="mail" style="width: 18px;"></i> Inbox / Feedback
                    <?php if ($unread_count > 0): ?>
                        <span style="background: #ef4444; color: white; font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 20px; margin-left: auto;"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <p style="font-size: 11px; font-weight: 800; color: #475569; letter-spacing: 1px; text-transform: uppercase; margin: 25px 0 10px 10px;">PUBLISHING</p>
        <ul class="nav-links">
            <li class="has-submenu">
                <a href="posts.php" class="<?php echo($current_page == 'posts.php' || $current_page == 'post_add.php' || $current_page == 'post_edit.php') ? 'active' : ''; ?>">
                    <i data-feather="edit-3" style="width: 18px;"></i> Manage Articles
                </a>
            </li>
            <li>
                <a href="ai_news.php" class="<?php echo $current_page == 'ai_news.php' ? 'active' : ''; ?>" style="color: #9333ea; font-weight: 700;">
                    <i data-feather="cpu" style="width: 18px; color: #9333ea;"></i> AI News Generator
                </a>
            </li>
            <li>
                <a href="categories.php" class="<?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
                    <i data-feather="layers" style="width: 18px;"></i> Categories
                </a>
            </li>
            <li>
                <a href="tags.php" class="<?php echo $current_page == 'tags.php' ? 'active' : ''; ?>">
                    <i data-feather="tag" style="width: 18px;"></i> Keyword Tags
                </a>
            </li>
            <li>
                <a href="polls.php" class="<?php echo $current_page == 'polls.php' ? 'active' : ''; ?>">
                    <i data-feather="pie-chart" style="width: 18px;"></i> Manage Polls
                </a>
            </li>
        </ul>

        <?php if (get_setting('ebook_magazine_enabled', 'yes') == 'yes'): ?>
        <p style="font-size: 11px; font-weight: 800; color: #475569; letter-spacing: 1px; text-transform: uppercase; margin: 25px 0 10px 10px;">DIGITAL MEDIA</p>
        <ul class="nav-links">

            <li>
                <a href="magazines.php" class="<?php echo $current_page == 'magazines.php' ? 'active' : ''; ?>">
                    <i data-feather="book-open" style="width: 18px;"></i> Magazines
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <?php if (is_admin()): ?>
        <p style="font-size: 11px; font-weight: 800; color: #475569; letter-spacing: 1px; text-transform: uppercase; margin: 25px 0 10px 10px;">GROWTH & PR</p>
        <ul class="nav-links">
            <li>
                <a href="social_share.php" class="<?php echo $current_page == 'social_share.php' ? 'active' : ''; ?>">
                    <i data-feather="share-2" style="width: 18px;"></i> Auto Share
                </a>
            </li>
            <li>
                <a href="ads.php" class="<?php echo $current_page == 'ads.php' ? 'active' : ''; ?>">
                    <i data-feather="target" style="width: 18px;"></i> Ad Campaigns
                </a>
            </li>
        </ul>

        <p style="font-size: 11px; font-weight: 800; color: #475569; letter-spacing: 1px; text-transform: uppercase; margin: 25px 0 10px 10px;">TEAM</p>
        <ul class="nav-links">
            <li>
                <a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                    <i data-feather="users" style="width: 18px;"></i> Manage Staff
                </a>
            </li>
            <li>
                <a href="reporter_idcard.php" class="<?php echo $current_page == 'reporter_idcard.php' ? 'active' : ''; ?>">
                    <i data-feather="credit-card" style="width: 18px;"></i> ID Cards
                </a>
            </li>
            <li>
                <a href="reporter_letter.php" class="<?php echo $current_page == 'reporter_letter.php' ? 'active' : ''; ?>">
                    <i data-feather="file-text" style="width: 18px;"></i> Joining Letter
                </a>
            </li>

        </ul>

        <p style="font-size: 11px; font-weight: 800; color: #475569; letter-spacing: 1px; text-transform: uppercase; margin: 25px 0 10px 10px;">SYSTEM</p>
        <ul class="nav-links">
            <li>
                <a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <i data-feather="settings" style="width: 18px;"></i> Site Settings
                </a>
            </li>
            <li>
                <a href="change_password.php" class="<?php echo $current_page == 'change_password.php' ? 'active' : ''; ?>">
                    <i data-feather="shield" style="width: 18px;"></i> Security
                </a>
            </li>
            <li>
                <a href="page_about.php" class="<?php echo $current_page == 'page_about.php' ? 'active' : ''; ?>">
                    <i data-feather="info" style="width: 18px;"></i> About Page
                </a>
            </li>
            <li>
                <a href="system_update.php" class="<?php echo $current_page == 'system_update.php' ? 'active' : ''; ?>">
                    <i data-feather="download-cloud" style="width: 18px; color: #3b82f6;"></i>
                    <span style="color: #3b82f6; font-weight: 700;">System Update</span>
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <p style="font-size: 11px; font-weight: 800; color: #475569; letter-spacing: 1px; text-transform: uppercase; margin: 25px 0 10px 10px;">ACCOUNT</p>
        <ul class="nav-links">
            <li>
                <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <i data-feather="user" style="width: 18px;"></i> My Profile
                </a>
            </li>
            <li>
                <a href="help.php" class="<?php echo $current_page == 'help.php' ? 'active' : ''; ?>">
                    <i data-feather="help-circle" style="width: 18px;"></i> Help & Tutorials
                </a>
            </li>
        </ul>

        <ul class="nav-links" style="margin-top: 30px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05);">
            <li>
                <a href="<?php echo BASE_URL; ?>logout.php" style="color: #f87171; background: rgba(248, 113, 113, 0.05); border: 1px solid rgba(248, 113, 113, 0.1);">
                    <i data-feather="log-out" style="width: 18px;"></i> Logout Session
                </a>
            </li>
        </ul>
    </div>
    <!-- Minimal Version Indicator -->
    <div style="padding: 15px; text-align: center; border-top: 1px solid rgba(255,255,255,0.03);">
        <?php 
            $v_json = @json_decode(@file_get_contents(__DIR__ . '/../../version.json'), true);
            $app_v = $v_json['version'] ?? '1.0.0';
        ?>
        <div style="font-size: 10px; color: #475569; letter-spacing: 0.5px;">v<?php echo $app_v; ?></div>
    </div>
</div>
