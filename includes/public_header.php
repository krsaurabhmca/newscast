<?php
ob_start();
require_once 'config.php';
require_once 'functions.php';

// Fetch categories for menu
$stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC");
$nav_categories = $stmt->fetchAll();

// Default SEO
$site_title = SITE_NAME;
$meta_desc = "Your ultimate destination for the latest news and insights.";
$site_logo = get_setting('site_logo');
$og_image = ($site_logo) ? BASE_URL . "assets/images/" . $site_logo : BASE_URL . "assets/images/default-post.jpg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?php echo isset($page_title) ? $page_title . " | " . SITE_NAME : SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($meta_description) ? $meta_description : $meta_desc; ?>">
    <link rel="canonical" href="<?php echo (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo BASE_URL; ?>">
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title : SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo isset($meta_description) ? $meta_description : $meta_desc; ?>">
    <meta property="og:image" content="<?php echo isset($page_image) ? $page_image : $og_image; ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="<?php echo isset($page_title) ? $page_title : SITE_NAME; ?>">
    <meta property="twitter:description" content="<?php echo isset($meta_description) ? $meta_description : $meta_desc; ?>">

    <!-- Favicon -->
    <?php if (get_setting('site_favicon')): ?>
        <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/<?php echo get_setting('site_favicon'); ?>">
    <?php else: ?>
        <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/favicon.png">
    <?php endif; ?>

    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>
    <div class="app-container">
        <!-- Vertical Sidebar -->
        <aside class="side-nav">
            <ul>
                <li>
                    <a href="<?php echo BASE_URL; ?>">
                        <div class="icon" style="color: #ff3c00;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        </div>
                        Top News
                    </a>
                </li>
                <?php foreach($nav_categories as $cat): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>category/<?php echo $cat['slug']; ?>">
                        <div class="icon" style="color: <?php echo $cat['color']; ?>;">
                             <i data-feather="<?php echo $cat['icon']; ?>" style="width: 18px; height: 18px;"></i>
                        </div>
                        <?php echo $cat['name']; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Top Header -->
            <header class="top-header">
                <a href="<?php echo BASE_URL; ?>" class="logo-bhaskar">
                    <?php if (get_setting('site_logo')): ?>
                        <img src="<?php echo BASE_URL . 'assets/images/' . get_setting('site_logo'); ?>" style="height: 45px;" alt="<?php echo SITE_NAME_DYNAMIC; ?>">
                    <?php else: ?>
                        <div style="background: var(--primary); color: #fff; padding: 5px 10px; border-radius: 4px; font-weight: 900; letter-spacing: -1px;">DB</div>
                    <?php endif; ?>
                    
                    <div style="display: flex; flex-direction: column; line-height: 1;">
                        <span style="font-size: 18px; letter-spacing: 1px; color: #1a1a1b; font-weight: 800;"><?php echo strtoupper(SITE_NAME_DYNAMIC); ?></span>
                        <span style="font-size: 12px; font-weight: 500; color: #666;">DIGITAL NEWS</span>
                    </div>
                </a>

                <div style="display: flex; align-items: center; gap: 15px;">
                    <ul class="top-menu">
                        <li>
                            <a href="<?php echo BASE_URL; ?>" class="active">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                Home
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>category/videos">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                                Video
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>digital-paper">
                                <i data-feather="file-text" style="width: 20px; height: 20px;"></i>
                                E-Paper
                            </a>
                        </li>
                    </ul>

                    <div class="header-search-desktop">
                        <form action="<?php echo BASE_URL; ?>search.php" method="GET" class="search-form" style="display: flex; align-items: center; background: #f1f5f9; border-radius: 20px; padding: 5px 15px;">
                            <input type="text" name="q" placeholder="Search news..." style="border: none; background: transparent; padding: 5px; font-size: 14px; outline: none; width: 120px;">
                            <button type="submit" style="background: none; border: none; cursor: pointer; color: #64748b;">
                                <i data-feather="search" style="width: 16px; height: 16px;"></i>
                            </button>
                        </form>
                    </div>

                    <div class="user-action">
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="btn" style="background: #f1f5f9; color: #444; font-size: 14px; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </a>
                    </div>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="menu-toggle" onclick="toggleMobileMenu()" style="background: none; border: none; cursor: pointer; color: #1a1a1b; display: none; padding: 5px;">
                        <i data-feather="menu"></i>
                    </button>
                </div>
            </header>

            <!-- Mobile Sidebar Overlay -->
            <div id="mobileMenu" class="mobile-menu-overlay" onclick="toggleMobileMenu()">
                <div class="mobile-menu-content" onclick="event.stopPropagation()">
                    <div class="mobile-menu-header">
                        <span style="font-weight: 800; color: var(--primary);">MENU</span>
                        <button onclick="toggleMobileMenu()" style="background: none; border: none; cursor: pointer; color: #666;">
                            <i data-feather="x"></i>
                        </button>
                    </div>
                    
                    <form action="<?php echo BASE_URL; ?>search.php" method="GET" style="padding: 15px; margin-bottom: 10px;">
                        <div style="background: #f1f5f9; border-radius: 8px; padding: 8px 15px; display: flex; align-items: center;">
                            <input type="text" name="q" placeholder="Search news..." style="border: none; background: transparent; width: 100%; outline: none; font-size: 15px;">
                            <button type="submit" style="background: none; border: none;"><i data-feather="search" style="width: 18px;"></i></button>
                        </div>
                    </form>

                    <nav class="mobile-nav-body">
                        <ul>
                            <li><a href="<?php echo BASE_URL; ?>"><i data-feather="home"></i> Home</a></li>
                            <li><a href="<?php echo BASE_URL; ?>category/videos"><i data-feather="video"></i> Videos</a></li>
                            <li><a href="<?php echo BASE_URL; ?>digital-paper"><i data-feather="file-text"></i> Digital Paper</a></li>
                            <li class="divider">Sections</li>
                            <?php foreach($nav_categories as $cat): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>category/<?php echo $cat['slug']; ?>">
                                    <span class="dot" style="background: <?php echo $cat['color']; ?>;"></span>
                                    <?php echo $cat['name']; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                </div>
            </div>

            <script>
                function toggleMobileMenu() {
                    const menu = document.getElementById('mobileMenu');
                    menu.classList.toggle('active');
                    document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';
                }
            </script>

            <?php echo display_ad('header', $pdo); ?>
