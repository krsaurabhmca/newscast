<?php
ob_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " | NewsPro Admin" : "NewsPro Admin"; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <!-- Rich Text Editor - Quill -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <header style="background: white; border-bottom: 1px solid var(--border); padding: 15px 30px; margin-bottom: 30px; position: sticky; top: 0; z-index: 90; display: flex; justify-content: space-between; align-items: center; border-radius: 0 0 12px 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button id="sidebarToggle" class="mobile-toggle" style="background: #f1f5f9; border: none; cursor: pointer; color: #1e293b; width: 40px; height: 40px; border-radius: 8px; display: none; align-items: center; justify-content: center;">
                        <i data-feather="menu" style="width: 20px;"></i>
                    </button>
                    <div class="page-info">
                        <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0;"><?php echo isset($page_title) ? $page_title : "Dashboard"; ?></h2>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 20px;">
                    <a href="<?php echo BASE_URL; ?>" target="_blank" class="desktop-only" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: #475569; font-size: 13px; font-weight: 700; background: #f1f5f9; padding: 10px 15px; border-radius: 8px; transition: .2s;">
                        <i data-feather="external-link" style="width: 16px;"></i> View Website
                    </a>

                    <a href="post_add.php" class="btn btn-primary desktop-only" style="background: var(--primary); font-size: 13px; font-weight: 600; border-radius: 8px; padding: 10px 18px;">
                        <i data-feather="plus" style="width: 16px;"></i> Create Post
                    </a>
                    
                    <div style="width: 1px; height: 25px; background: #e2e8f0;" class="desktop-only"></div>

                    <div class="user-meta" style="display: flex; align-items: center; gap: 12px;">
                        <div style="text-align: right;" class="desktop-only">
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a;"><?php echo $_SESSION['username']; ?></div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Administrator</div>
                        </div>
                        <div class="profile-trigger" style="position: relative; cursor: pointer;">
                            <img src="<?php echo get_profile_image($_SESSION['profile_image'] ?? ''); ?>"
                                 alt="Profile"
                                 onerror="this.onerror=null;this.src='<?php echo BASE_URL; ?>assets/images/default-avatar.svg';"
                                 style="width: 38px; height: 38px; border-radius: 10px; object-fit: cover; border: 2px solid #f1f5f9;">
                        </div>
                    </div>
                </div>
            </header>

            <script>
                // This will be used for both desktop toggle and mobile
                document.addEventListener('DOMContentLoaded', function() {
                    const toggle = document.getElementById('sidebarToggle');
                    const sidebar = document.querySelector('.sidebar');
                    if (toggle) {
                        toggle.onclick = function(e) {
                            e.stopPropagation();
                            sidebar.classList.toggle('mobile-active');
                        };
                    }

                    // Close sidebar when clicking outside on mobile
                    document.addEventListener('click', function(e) {
                        if (sidebar.classList.contains('mobile-active') && !sidebar.contains(e.target)) {
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
