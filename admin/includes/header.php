<?php
ob_start();
if (!file_exists(__DIR__ . '/../../includes/config.php')) {
    header("Location: ../install.php");
    exit;
}
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once __DIR__ . '/../../includes/run_migrations.php';


if (!is_logged_in()) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

if (isset($_GET['revert_admin']) && isset($_SESSION['admin_user_id'])) {
    $_SESSION['user_id'] = $_SESSION['admin_user_id'];
    $_SESSION['username'] = $_SESSION['admin_username'];
    $_SESSION['role'] = $_SESSION['admin_role'];
    $_SESSION['profile_image'] = $_SESSION['admin_profile_image'];
    
    unset($_SESSION['admin_user_id'], $_SESSION['admin_username'], $_SESSION['admin_role'], $_SESSION['admin_profile_image']);
    
    header("Location: dashboard.php");
    exit;
}

// Check for system updates periodically
if (is_admin()) {
    check_system_updates_cached($pdo);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " | NewsPro Admin" : "NewsPro Admin"; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/admin.css'); ?>">
    <link rel="stylesheet" href="../assets/css/admin_responsive.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/admin_responsive.css'); ?>">
    <!-- Rich Text Editor - Quill -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary: <?php echo get_setting('theme_color', '#ff3c00'); ?>;
        }
    </style>
</head>
<body>
    <script>
        // Use database setting as fallback if localStorage isn't set
        const defaultCollapsed = <?php echo (get_setting('collapse_sidebar', 'no') == 'yes') ? 'true' : 'false'; ?>;
        const lsValue = localStorage.getItem('sidebar-collapsed');
        
        if (lsValue === 'true' || (lsValue === null && defaultCollapsed)) {
            if (window.innerWidth > 1024) {
                document.body.classList.add('sidebar-collapsed');
            }
        }
    </script>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <header style="background: white; border-bottom: 1px solid var(--border); padding: 15px 30px; margin-bottom: 30px; position: sticky; top: 0; z-index: 90; display: flex; justify-content: space-between; align-items: center; border-radius: 0 0 12px 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button id="sidebarToggle" style="background: #f1f5f9; border: none; cursor: pointer; color: #1e293b; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: background .15s;">
                        <i data-feather="menu" style="width: 20px;"></i>
                    </button>
                    <div class="page-info">
                        <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0;"><?php echo isset($page_title) ? $page_title : "Dashboard"; ?></h2>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 20px;">
                    <?php if (isset($_SESSION['admin_user_id'])): ?>
                    <a href="?revert_admin=1" class="desktop-only" style="color: white; padding: 6px 12px; border-radius: 8px; background: #ef4444; transition: .2s; display: flex; align-items: center; justify-content: center; gap: 6px; font-weight: 700; font-size: 13px; text-decoration: none;" title="Back to Own Account">
                        <i data-feather="corner-up-left" style="width: 16px; height: 16px;"></i> Return to Admin
                    </a>
                    <?php endif; ?>

                    <!-- Custom Language Switcher -->
                    <div class="lang-switch desktop-only" style="display: flex; background: #f1f5f9; padding: 4px; border-radius: 8px; gap: 4px;">
                        <button onclick="setAdminLang('en')" id="btn-lang-en" style="border: none; background: transparent; padding: 4px 10px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; color: #475569; transition: .2s;">EN</button>
                        <button onclick="setAdminLang('hi')" id="btn-lang-hi" style="border: none; background: transparent; padding: 4px 10px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; color: #475569; transition: .2s;">HI</button>
                    </div>

                    <a href="<?php echo BASE_URL; ?>" target="_blank" class="desktop-only" style="color: #475569; padding: 8px 10px; border-radius: 8px; background: #f1f5f9; transition: .2s; display: flex; align-items: center; justify-content: center;" title="Visit Website">
                        <i data-feather="globe" style="width: 18px; height: 18px;"></i>
                    </a>

                    <a href="post_add.php" class="desktop-only" style="color: white; padding: 8px 10px; border-radius: 8px; background: var(--primary); transition: .2s; display: flex; align-items: center; justify-content: center;" title="Create New Post">
                        <i data-feather="plus" style="width: 18px; height: 18px;"></i>
                    </a>
                    
                    <a href="system_update.php" class="desktop-only" style="color: #475569; padding: 8px 10px; border-radius: 8px; background: #f1f5f9; transition: .2s; display: flex; align-items: center; justify-content: center; position: relative;" title="Check System Update">
                        <i data-feather="refresh-cw" style="width: 18px; height: 18px;"></i>
                        <?php if (get_setting('update_available', 'no') === 'yes'): ?>
                            <span style="position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; border: 1.5px solid #fff;"></span>
                        <?php endif; ?>
                    </a>

                    <!-- Profile Dropdown -->
                    <style>
                        .h-prof-drop { position: relative; }
                        .h-prof-menu { position: absolute; right: 0; top: 100%; margin-top: 10px; background: #fff; width: 200px; border-radius: 12px; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; padding: 8px; opacity: 0; visibility: hidden; transform: translateY(10px); transition: all .2s cubic-bezier(.4,0,.2,1); z-index: 1000; }
                        .h-prof-drop:hover .h-prof-menu { opacity: 1; visibility: visible; transform: translateY(0); }
                        .h-prof-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #475569; text-decoration: none; font-size: 13px; font-weight: 600; border-radius: 8px; transition: .15s; }
                        .h-prof-item:hover { background: #f8fafc; color: #0f172a; }
                        .h-prof-item i { transition: transform .2s; }
                        .h-prof-item:hover i { transform: scale(1.1); }
                        .h-prof-logout { color: #ef4444; }
                        .h-prof-logout:hover { background: #fef2f2; color: #dc2626; }
                    </style>
                    <div class="h-prof-drop">
                        <div class="user-meta" style="display: flex; align-items: center; gap: 12px; cursor: pointer; transition: .2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                            <div style="text-align: right;" class="desktop-only">
                                <div style="font-size: 14px; font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                                <div style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">
                                    <?php 
                                    if ($_SESSION['role'] === 'dev') echo 'Developer';
                                    elseif ($_SESSION['role'] === 'admin') echo 'Administrator';
                                    elseif ($_SESSION['role'] === 'editor') echo 'Editor';
                                    elseif ($_SESSION['role'] === 'reporter') echo 'Reporter';
                                    else echo 'Staff Member';
                                    ?>
                                </div>
                            </div>
                            <div class="profile-trigger" style="position: relative;">
                                <img src="<?php echo get_profile_image($_SESSION['profile_image'] ?? ''); ?>"
                                     alt="Profile"
                                     onerror="this.onerror=null;this.src='<?php echo BASE_URL; ?>assets/images/default-avatar.svg';"
                                     style="width: 38px; height: 38px; border-radius: 10px; object-fit: cover; border: 2px solid #f1f5f9;">
                            </div>
                        </div>
                        <div class="h-prof-menu">
                            <a href="profile.php" class="h-prof-item">
                                <i data-feather="user" style="width: 16px;"></i> My Profile
                            </a>
                            <a href="change_password.php" class="h-prof-item">
                                <i data-feather="shield" style="width: 16px;"></i> Security
                            </a>
                            <a href="help.php" class="h-prof-item">
                                <i data-feather="help-circle" style="width: 16px;"></i> Help &amp; Docs
                            </a>
                            <div style="height: 1px; background: #f1f5f9; margin: 6px 0;"></div>
                            <a href="<?php echo BASE_URL; ?>logout.php" class="h-prof-item h-prof-logout">
                                <i data-feather="log-out" style="width: 16px;"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const toggle = document.getElementById('sidebarToggle');
                    const sidebar = document.querySelector('.sidebar');
                    const body = document.body;

                    if (toggle) {
                        toggle.onclick = function(e) {
                            e.stopPropagation();
                            if (window.innerWidth <= 1024) {
                                // Mobile behavior: toggle overlay
                                sidebar.classList.toggle('mobile-active');
                            } else {
                                // Desktop behavior: collapse sidebar
                                body.classList.toggle('sidebar-collapsed');
                                // Remember state
                                localStorage.setItem('sidebar-collapsed', body.classList.contains('sidebar-collapsed'));
                            }
                        };
                    }

                    // Close sidebar when clicking outside on mobile
                    document.addEventListener('click', function(e) {
                        if (sidebar.classList.contains('mobile-active') && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                            sidebar.classList.remove('mobile-active');
                        }
                    });
                });
            </script>

            <?php if (isset($_SESSION['flash_msg'])): ?>
                <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'success'); ?>" id="admin-flash-msg" style="cursor:pointer;" onclick="this.remove()">
                    <span><?php echo htmlspecialchars($_SESSION['flash_msg']); ?></span>
                    <span style="margin-left:auto; font-size:18px; opacity:0.5; line-height:1;">&times;</span>
                </div>
                <script>
                    setTimeout(function(){
                        var el=document.getElementById('admin-flash-msg');
                        if(el){el.style.transition='opacity .4s';el.style.opacity='0';setTimeout(function(){el.remove();},400);}
                    },5000);
                </script>
                <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
            <?php endif; ?>
