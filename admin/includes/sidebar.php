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
        <p style="font-size: 11px; font-weight: 800; color: #475569; letter-spacing: 1px; text-transform: uppercase; margin: 20px 0 10px 10px;">Main Menu</p>
        <ul class="nav-links">
            <li>
                <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i data-feather="grid" style="width: 18px;"></i>
                    Dashboard
                </a>
            </li>
            <li class="has-submenu">
                <a href="posts.php" class="<?php echo ($current_page == 'posts.php' || $current_page == 'post_add.php' || $current_page == 'post_edit.php') ? 'active' : ''; ?>">
                    <i data-feather="file-text" style="width: 18px;"></i>
                    Articles Management
                </a>
            </li>
            <li>
                <a href="categories.php" class="<?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
                    <i data-feather="layers" style="width: 18px;"></i>
                    Categories
                </a>
            </li>
            <li>
                <a href="tags.php" class="<?php echo $current_page == 'tags.php' ? 'active' : ''; ?>">
                    <i data-feather="tag" style="width: 18px;"></i>
                    Tags Management
                </a>
            </li>
            <li>
                <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <i data-feather="user" style="width: 18px;"></i>
                    Account Profile
                </a>
            </li>
            <li>
                <?php $unread_count = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'new'")->fetchColumn(); ?>
                <a href="feedback.php" class="<?php echo $current_page == 'feedback.php' ? 'active' : ''; ?>" style="position: relative;">
                    <i data-feather="inbox" style="width: 18px;"></i>
                    Messages
                    <?php if ($unread_count > 0): ?>
                        <span style="background: #ef4444; color: white; font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 20px; margin-left: auto;"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <p style="font-size: 11px; font-weight: 800; color: #475569; letter-spacing: 1px; text-transform: uppercase; margin: 30px 0 10px 10px;">Platform</p>
        <ul class="nav-links">
            <?php if (is_admin()): ?>
            <li>
                <a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                    <i data-feather="users" style="width: 18px;"></i>
                    Manage Users
                </a>
            </li>
            <li>
                <a href="epapers.php" class="<?php echo $current_page == 'epapers.php' ? 'active' : ''; ?>">
                    <i data-feather="file-text" style="width: 18px;"></i>
                    Digital Papers
                </a>
            </li>
            <li>
                <a href="magazines.php" class="<?php echo $current_page == 'magazines.php' ? 'active' : ''; ?>">
                    <i data-feather="book-open" style="width: 18px;"></i>
                    Magazine
                </a>
            </li>
            <li>
                <a href="ads.php" class="<?php echo $current_page == 'ads.php' ? 'active' : ''; ?>">
                    <i data-feather="pie-chart" style="width: 18px;"></i>
                    Ad Campaigns
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <i data-feather="settings" style="width: 18px;"></i>
                    Site Settings
                </a>
            </li>
            <li>
                <a href="change_password.php" class="<?php echo $current_page == 'change_password.php' ? 'active' : ''; ?>">
                    <i data-feather="lock" style="width: 18px;"></i>
                    Security
                </a>
            </li>
            <li>
                <a href="help.php" class="<?php echo $current_page == 'help.php' ? 'active' : ''; ?>">
                    <i data-feather="help-circle" style="width: 18px;"></i>
                    Help & Guide
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <ul class="nav-links" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05);">
            <li>
                <a href="<?php echo BASE_URL; ?>logout.php" style="color: #f87171; background: rgba(248, 113, 113, 0.05); border: 1px solid rgba(248, 113, 113, 0.1);">
                    <i data-feather="log-out" style="width: 18px;"></i>
                    Logout Session
                </a>
            </li>
        </ul>
    </div>
    <!-- Developer Credit -->
    <div style="padding: 12px 15px; border-top: 1px solid rgba(255,255,255,0.06);">
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 12px; padding: 14px; text-align: center;">
            <div style="font-size: 11px; font-weight: 700; color: #94a3b8; margin-bottom: 2px;">NewsCast &mdash; Digital News CMS</div>
            <div style="font-size: 10px; color: #475569; margin-bottom: 10px;">v1.0 &bull; All Rights Reserved</div>
            <div style="font-size: 10px; color: #64748b; margin-bottom: 6px;">Developed &amp; Powered by</div>
            <a href="https://offerplant.com" target="_blank" style="display: inline-block; font-size: 12px; font-weight: 800; color: #818cf8; text-decoration: none; letter-spacing: 0.3px; margin-bottom: 10px;">OfferPlant.com</a>
            <div style="display: flex; gap: 8px; justify-content: center;">
                <a href="tel:9431426600" style="display: flex; align-items: center; gap: 5px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); color: #94a3b8; text-decoration: none; font-size: 10px; font-weight: 600; padding: 5px 10px; border-radius: 8px; transition: .2s;"
                   onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.37 2 2 0 0 1 3.61 1h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.1a16 16 0 0 0 6 6l.86-.86a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    9431426600
                </a>
                <a href="https://wa.me/919431426600" target="_blank" style="display: flex; align-items: center; gap: 5px; background: rgba(37,211,102,0.1); border: 1px solid rgba(37,211,102,0.2); color: #25d366; text-decoration: none; font-size: 10px; font-weight: 600; padding: 5px 10px; border-radius: 8px; transition: .2s;"
                   onmouseover="this.style.background='rgba(37,211,102,0.2)'" onmouseout="this.style.background='rgba(37,211,102,0.1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                    WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>
