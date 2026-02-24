<?php
ob_start();
require_once 'config.php';
require_once 'functions.php';

// Fetch categories for menu
$stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC");
$nav_categories = $stmt->fetchAll();

// Default SEO — fallback to settings, then hardcoded
$site_title     = SITE_NAME;
$meta_desc      = get_setting('meta_description', 'Your ultimate destination for the latest news and insights.');
$meta_keywords  = get_setting('meta_keywords', '');
$meta_robots    = get_setting('meta_robots', 'index, follow');
$site_logo      = get_setting('site_logo');
$og_image_fb    = get_setting('og_image_url'); // custom OG image from settings
$og_image       = $og_image_fb ?: (($site_logo) ? BASE_URL . "assets/images/" . $site_logo : BASE_URL . "assets/images/default-post.jpg");
$twitter_handle = get_setting('twitter_handle', '');
$ga_id          = get_setting('google_analytics_id', '');
$gsc_verify     = get_setting('google_site_verify', '');
$bing_verify    = get_setting('bing_site_verify', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?php echo isset($page_title) ? $page_title . " | " . SITE_NAME : SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : htmlspecialchars($meta_desc); ?>">
    <?php if ($meta_keywords): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    <?php endif; ?>
    <meta name="robots" content="<?php echo $meta_robots; ?>">
    <link rel="canonical" href="<?php echo (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    <?php if ($gsc_verify): ?>
    <meta name="google-site-verification" content="<?php echo htmlspecialchars($gsc_verify); ?>">
    <?php endif; ?>
    <?php if ($bing_verify): ?>
    <meta name="msvalidate.01" content="<?php echo htmlspecialchars($bing_verify); ?>">
    <?php endif; ?>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo BASE_URL; ?>">
    <meta property="og:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) : SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : htmlspecialchars($meta_desc); ?>">
    <meta property="og:image" content="<?php echo isset($page_image) ? $page_image : $og_image; ?>">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) : SITE_NAME; ?>">
    <meta name="twitter:description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : htmlspecialchars($meta_desc); ?>">
    <meta name="twitter:image" content="<?php echo isset($page_image) ? $page_image : $og_image; ?>">
    <?php if ($twitter_handle): ?>
    <meta name="twitter:site" content="<?php echo htmlspecialchars($twitter_handle); ?>">
    <?php endif; ?>

    <!-- Favicon -->
    <?php if (get_setting('site_favicon')): ?>
        <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/<?php echo get_setting('site_favicon'); ?>">
    <?php else: ?>
        <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/favicon.png">
    <?php endif; ?>

    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    
    <style>
        :root {
            --primary: <?php echo get_setting('theme_color', '#ff3c00'); ?>;
        }
        <?php if (get_setting('header_style') == 'sticky'): ?>
        .top-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        <?php endif; ?>
    </style>

    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    <?php if ($ga_id): ?>
    <!-- Google Analytics 4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($ga_id); ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo htmlspecialchars($ga_id); ?>');
    </script>
    <?php endif; ?>
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
                <?php if (get_setting('show_date_time', 'yes') == 'yes'): ?>
                    <div class="header-date-time desktop-only" style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">
                        <span id="live-date"><?php echo date('D, M d, Y'); ?></span>
                        <span style="margin: 0 10px;">|</span>
                        <span id="live-time"><?php echo date('h:i:s A'); ?></span>
                        <script>
                            setInterval(() => {
                                const now = new Date();
                                const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
                                document.getElementById('live-time').innerText = timeStr;
                            }, 1000);
                        </script>
                    </div>
                <?php endif; ?>

                <a href="<?php echo BASE_URL; ?>" class="logo-bhaskar">
                    <?php if (get_setting('site_logo')): ?>
                        <img src="<?php echo BASE_URL . 'assets/images/' . get_setting('site_logo'); ?>" style="height: 45px;" alt="<?php echo SITE_NAME_DYNAMIC; ?>">
                    <?php else: ?>
                        <div style="background: var(--primary); color: #fff; padding: 5px 10px; border-radius: 4px; font-weight: 900; letter-spacing: -1px;">DB</div>
                    <?php endif; ?>
                    
                    <div style="display: flex; flex-direction: column; line-height: 1.2;">
                        <span style="font-size: 18px; letter-spacing: 1px; color: #1a1a1b; font-weight: 800;"><?php echo strtoupper(SITE_NAME_DYNAMIC); ?></span>
                        <?php $tagline = get_setting('site_tagline', 'DIGITAL NEWS'); ?>
                        <span style="font-size: 11px; font-weight: 600; color: #888; letter-spacing: .5px; text-transform: uppercase;"><?php echo htmlspecialchars($tagline); ?></span>
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
                            <a href="<?php echo BASE_URL; ?>category/video">
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
                        <li>
                            <a href="<?php echo BASE_URL; ?>magazine">
                                <i data-feather="book-open" style="width: 20px; height: 20px;"></i>
                                Magazine
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

            <?php if (get_setting('breaking_news_enabled') == 'yes'): 
                $breaking_stmt = $pdo->query("SELECT title, slug FROM posts WHERE status = 'published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 5");
                $breaking_news = $breaking_stmt->fetchAll();
                if ($breaking_news):
            ?>
            <div class="breaking-news-box" style="background: #000; color: #fff; height: 35px; display: flex; align-items: center; overflow: hidden; font-size: 13px;">
                <div style="background: var(--primary); padding: 0 15px; height: 100%; display: flex; align-items: center; font-weight: 900; skew: -10deg; margin-left: -5px; position: relative; z-index: 2;">
                    BREAKING
                </div>
                <div class="ticker-wrapper" style="flex: 1; overflow: hidden; position: relative;">
                    <div class="ticker-content" style="display: inline-block; white-space: nowrap; animation: ticker 30s linear infinite;">
                        <?php foreach($breaking_news as $news): ?>
                            <a href="<?php echo BASE_URL; ?>article/<?php echo $news['slug']; ?>" style="color: #fff; text-decoration: none; margin-right: 50px; font-weight: 600;">
                                <span style="color: var(--primary); font-weight: 900;">•</span> <?php echo $news['title']; ?>
                            </a>
                        <?php endforeach; ?>
                        <!-- Duplicate content for seamless loop -->
                        <?php foreach($breaking_news as $news): ?>
                            <a href="<?php echo BASE_URL; ?>article/<?php echo $news['slug']; ?>" style="color: #fff; text-decoration: none; margin-right: 50px; font-weight: 600;">
                                <span style="color: var(--primary); font-weight: 900;">•</span> <?php echo $news['title']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <style>
                    @keyframes ticker {
                        0% { transform: translateX(0); }
                        100% { transform: translateX(-50%); }
                    }
                    .ticker-wrapper:hover .ticker-content {
                        animation-play-state: paused;
                    }
                </style>
            </div>
            <?php endif; endif; ?>

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
