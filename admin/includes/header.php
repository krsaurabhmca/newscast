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
            <header>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button id="sidebarToggle" class="mobile-toggle" style="display: none; background: none; border: none; cursor: pointer; color: #1e293b;">
                        <i data-feather="menu"></i>
                    </button>
                    <div class="page-info">
                        <h2 style="font-size: 24px; font-weight: 700; color: #1e293b;"><?php echo isset($page_title) ? $page_title : "Dashboard"; ?></h2>
                        <p style="color: #64748b; font-size: 14px;">Welcome, <?php echo $_SESSION['username']; ?></p>
                    </div>
                </div>
                <div class="user-meta" style="display: flex; align-items: center; gap: 15px;">
                    <a href="<?php echo BASE_URL; ?>" target="_blank" class="btn desktop-only" style="background: #f1f5f9; color: #475569;">
                        View Site
                    </a>
                    <div class="profile" style="display: flex; align-items: center; gap: 10px;">
                        <img src="../assets/images/<?php echo $_SESSION['profile_image']; ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0;">
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
                <div class="alert alert-<?php echo $_SESSION['flash_type']; ?>">
                    <?php 
                        echo $_SESSION['flash_msg']; 
                        unset($_SESSION['flash_msg']);
                        unset($_SESSION['flash_type']);
                    ?>
                </div>
            <?php endif; ?>
