<?php
ob_start();
if (!file_exists(__DIR__ . '/config.php')) {
    header("Location: install.php");
    exit;
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Canonical URL Generation
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$current_url = $protocol . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

if (!isset($canonical_url)) {
    $parsed_url = parse_url($current_url);
    $path = $parsed_url['path'] ?? '';
    
    // Normalize index.php to root URL
    if (basename($path) === 'index.php') {
        $canonical_url = BASE_URL;
    } else {
        $query_params = [];
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
            // Retain key query params needed for pagination or standard content views
            $allowed_params = ['page', 'id', 'slug'];
            $filtered_params = array_filter($query_params, function($key) use ($allowed_params) {
                return in_array($key, $allowed_params);
            }, ARRAY_FILTER_USE_KEY);
            
            if (!empty($filtered_params)) {
                $canonical_url = $protocol . "://$_SERVER[HTTP_HOST]" . $path . '?' . http_build_query($filtered_params);
            } else {
                $canonical_url = $protocol . "://$_SERVER[HTTP_HOST]" . $path;
            }
        } else {
            $canonical_url = $current_url;
        }
    }
}

// Fetch categories for menu
$stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC");
$nav_categories = $stmt->fetchAll();

$categories_tree = [];
$sub_categories = [];
foreach ($nav_categories as $cat) {
    if (empty($cat['parent_id'])) {
        $categories_tree[$cat['id']] = $cat;
        $categories_tree[$cat['id']]['children'] = [];
    } else {
        $sub_categories[] = $cat;
    }
}
foreach ($sub_categories as $sub) {
    if (isset($categories_tree[$sub['parent_id']])) {
        $categories_tree[$sub['parent_id']]['children'][] = $sub;
    }
}

// Fetch latest active poll
$latest_poll_stmt = $pdo->query("SELECT id FROM polls WHERE status = 'active' AND (starts_at IS NULL OR starts_at <= NOW()) AND (expires_at IS NULL OR expires_at >= NOW()) ORDER BY created_at DESC LIMIT 1");
$latest_poll_header = $latest_poll_stmt->fetch();
$poll_header_url = $latest_poll_header ? BASE_URL . "poll.php?id=" . $latest_poll_header['id'] : '#';

// Default SEO — fallback to settings, then hardcoded
$site_title = SITE_NAME;
$meta_desc = get_setting('meta_description', 'Your ultimate destination for the latest news and insights.');
$meta_keywords = get_setting('meta_keywords', '');
$meta_robots = get_setting('meta_robots', 'index, follow');
$site_logo = get_setting('site_logo');
$og_image_setting = get_setting('og_image_url');
if ($og_image_setting) {
    $og_image = (strpos($og_image_setting, 'http') === 0) ? $og_image_setting : BASE_URL . ltrim($og_image_setting, '/');
}
else {
    $og_image = ($site_logo) ? BASE_URL . "assets/images/" . $site_logo : BASE_URL . "assets/images/default-post.jpg";
}
$twitter_handle = get_setting('twitter_handle', '');
$ga_id = get_setting('google_analytics_id', '');
$gsc_verify = get_setting('google_site_verify', '');
$bing_verify = get_setting('bing_site_verify', '');
?>
<!DOCTYPE html>
<html lang="en" prefix="og: http://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php 
        $dynamic_site_name = get_setting('site_name', 'NewsCast');
        if (isset($page_title) && !empty($page_title)) {
            echo htmlspecialchars($page_title) . ' | ' . htmlspecialchars($dynamic_site_name);
        } else {
            $dynamic_site_tagline = get_setting('site_tagline', 'Digital News Portal');
            echo htmlspecialchars($dynamic_site_name . ' - ' . $dynamic_site_tagline);
        }
    ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(isset($meta_description) ? $meta_description : $meta_desc); ?>">

    <!-- SEO -->
    <meta name="robots" content="<?php echo $meta_robots; ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    <link rel="canonical" href="<?php echo $canonical_url; ?>">
    <link rel="alternate" type="application/rss+xml" title="<?php echo htmlspecialchars(get_setting('site_name', 'NewsCast')); ?> RSS Feed" href="<?php echo BASE_URL; ?>feed.php">

    <!-- Open Graph (WhatsApp/Facebook) -->
    <?php

// Ensure page image is an absolute URL
if (isset($page_image) && $page_image) {
    if (strpos($page_image, 'http') === 0) {
        $final_og_image = $page_image;
    } else {
        $final_og_image = BASE_URL . ltrim($page_image, '/');
    }
} else {
    $final_og_image = $og_image;
}

// Determine image type based on extension
$img_type = "image/jpeg";
if (strpos($final_og_image, '.png') !== false) $img_type = "image/png";
elseif (strpos($final_og_image, '.webp') !== false) $img_type = "image/webp";
elseif (strpos($final_og_image, '.gif') !== false) $img_type = "image/gif";

$og_type = (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'website' : 'article';
?>
    <meta property="og:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) : SITE_NAME_DYNAMIC; ?>">
    <meta property="og:description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : htmlspecialchars($meta_desc); ?>">
    <meta property="og:image" content="<?php echo $final_og_image; ?>">
    <meta property="og:image:secure_url" content="<?php echo $final_og_image; ?>">
    <meta property="og:image:type" content="<?php echo $img_type; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="<?php echo $current_url; ?>">
    <meta property="og:type" content="<?php echo $og_type; ?>">
    <meta property="og:site_name" content="<?php echo SITE_NAME_DYNAMIC; ?>">
    <meta property="og:locale" content="hi_IN">
    
    <?php if (get_setting('fb_app_id')): ?>
    <meta property="fb:app_id" content="<?php echo get_setting('fb_app_id'); ?>">
    <?php
endif; ?>

    <!-- Schema.org for Legacy / Telegram / Viber -->
    <meta itemprop="name" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) : SITE_NAME_DYNAMIC; ?>">
    <meta itemprop="description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : htmlspecialchars($meta_desc); ?>">
    <meta itemprop="image" content="<?php echo $final_og_image; ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) : SITE_NAME_DYNAMIC; ?>">
    <meta name="twitter:description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : htmlspecialchars($meta_desc); ?>">
    <meta name="twitter:image" content="<?php echo $final_og_image; ?>">
    <?php if ($twitter_handle): ?>
    <meta name="twitter:site" content="<?php echo htmlspecialchars($twitter_handle); ?>">
    <?php
endif; ?>

    <!-- Favicon -->
    <?php if (get_setting('site_favicon')): ?>
        <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/<?php echo get_setting('site_favicon'); ?>">
    <?php
else: ?>
        <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/favicon.png">
    <?php
endif; ?>

    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">

    <!-- Dynamic Theme Color -->
    <style>
        :root {
            --primary: <?php echo get_setting('theme_color', '#ff3c00'); ?>;
        }
    </style>
    
    <?php if (get_setting('homepage_theme', 'theme1') === 'theme2'): ?>
    <style>
        /* Theme 2 Layout Overrides */
        .side-nav {
            display: none !important;
        }
        .main-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
        .content-container {
            max-width: 100% !important;
            padding-left: clamp(15px, 4vw, 40px) !important;
            padding-right: clamp(15px, 4vw, 40px) !important;
        }
        /* Body padding-bottom for mobile bottom nav */
        @media (max-width: 1024px) {
            body.theme2-body {
                padding-bottom: 65px;
            }
        }
        /* Top Horizontal Category Navigation */
        .theme2-nav {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0 40px;
            position: relative !important; /* Non Sticky */
            z-index: 600; /* Must be above breaking ticker so submenus render on top */
            overflow: visible !important; /* Allow dropdown to extend outside nav bar */
        }
        /* Nav items that have submenus need their own stacking context */
        .t2-has-sub {
            position: relative;
            overflow: visible !important; /* Never clip the dropdown */
        }
        /* The nav ul uses the padding-bottom hack to allow horizontal scrolling while preserving vertical dropdown visibility */
        .theme2-nav > div > ul {
            overflow-x: auto !important;
            overflow-y: hidden !important;
            flex-wrap: nowrap !important;
            padding-bottom: 500px !important;
            margin-bottom: -500px !important;
            pointer-events: none; /* Prevent invisible padded area from blocking clicks below */
            -ms-overflow-style: none; /* IE/Edge ghost scrollbar */
            scrollbar-width: none; /* Firefox ghost scrollbar */
        }
        .theme2-nav > div > ul::-webkit-scrollbar {
            display: none; /* Chrome/Safari ghost scrollbar */
        }
        .theme2-nav > div > ul > li {
            pointer-events: auto; /* Restore clicks on menu items */
        }
        .t2-nav-link {
            display: flex !important;
            align-items: center;
            gap: 8px;
            padding: 16px 20px !important;
            font-weight: 700;
            font-size: 14px;
            color: #475569 !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            text-decoration: none;
            border-bottom: 3px solid transparent !important;
        }
        .t2-nav-link:hover {
            color: var(--primary) !important;
        }
        .t2-nav-link.active {
            color: var(--primary) !important;
            border-bottom-color: var(--primary) !important;
        }
        .t2-nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--primary);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(-50%);
        }
        .t2-nav-link:hover::after {
            width: 80%;
        }
        .t2-nav-link.active::after {
            width: 100%;
        }
        .t2-submenu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: #fff;
            min-width: 220px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            list-style: none;
            padding: 10px 0;
            margin: 0;
            z-index: 99999; /* Highest — must exceed nav, ticker, header, and page content */
            overflow: hidden;
        }
        .t2-has-sub:hover .t2-submenu {
            display: block !important;
            animation: fadeInSub 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes fadeInSub {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .t2-submenu li a {
            display: flex !important;
            align-items: center;
            gap: 10px;
            padding: 10px 20px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            color: #475569 !important;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .t2-submenu li a:hover {
            background: #f8fafc !important;
            color: var(--primary) !important;
            padding-left: 24px !important;
        }
        /* Hide scrollbar on the nav ul */
        .theme2-nav ul::-webkit-scrollbar { display: none; }
        .theme2-nav ul { -ms-overflow-style: none; scrollbar-width: none; }
        /* Hide horizontal nav on mobile — mobile drawer handles navigation */
        @media (max-width: 1024px) {
            .theme2-nav { display: none !important; }
        }
        /* Ticker animation for Theme 2 breaking bar */
        @keyframes ticker {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .ticker-wrapper:hover .ticker-content {
            animation-play-state: paused;
        }
        /* Theme 2 card image hover */
        .t2-bento-card:hover .t2-card-img,
        .t2-img-hover:hover,
        a:hover .t2-img-hover {
            transform: scale(1.04);
        }
        /* Theme 2 split lead hover */
        .t2-split-lead:hover {
            box-shadow: 0 12px 30px rgba(0,0,0,0.08) !important;
            transform: translateY(-3px);
        }
        .t2-split-item:hover {
            background: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
            transform: translateX(4px);
        }
        /* Top 10 hover */
        .t2-top-10-card:hover h4,
        .t2-popular-card:hover h5 {
            color: var(--primary) !important;
        }
        /* Breaking ticker: reduce font on very small screens */
        @media (max-width: 400px) {
            .t2-breaking-ticker span:first-child {
                padding: 0 12px !important;
                font-size: 10px !important;
                letter-spacing: 0.5px !important;
            }
        }
    </style>
    <?php endif; ?>

    <!-- Verification Codes -->
    <?php if ($gsc = get_setting('google_site_verify')): ?>
    <meta name="google-site-verification" content="<?php echo htmlspecialchars($gsc); ?>">
    <?php
endif; ?>
    <?php if ($bing = get_setting('bing_site_verify')): ?>
    <meta name="msvalidate.01" content="<?php echo htmlspecialchars($bing); ?>">
    <?php
endif; ?>
    <style>
        @media (max-width: 768px) {
            .logo-has-image .logo-text-group {
                display: none !important;
            }
        }
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

    <?php if ($adsense_pub_id = get_setting('google_adsense_pub_id')): ?>
    <!-- Google AdSense -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo htmlspecialchars($adsense_pub_id); ?>"
     crossorigin="anonymous"></script>
    <?php endif; ?>

    <?php
    $onesignal_app_id = get_setting('onesignal_app_id', '');
    $onesignal_safari_web_id = get_setting('onesignal_safari_web_id', '');
    if (!empty($onesignal_app_id)): 
    ?>
    <!-- OneSignal Web Push -->
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    <script>
      window.OneSignalDeferred = window.OneSignalDeferred || [];
      OneSignalDeferred.push(async function(OneSignal) {
        await OneSignal.init({
          appId: "<?php echo htmlspecialchars($onesignal_app_id); ?>",
          <?php if (!empty($onesignal_safari_web_id)): ?>
          safari_web_id: "<?php echo htmlspecialchars($onesignal_safari_web_id); ?>",
          <?php endif; ?>
          notifyButton: {
            enable: true,
            displayPredicate: function() {
                return OneSignal.Notifications.isPushSupported();
            }
          },
          allowLocalhostAsSecureOrigin: true
        });
      });
    </script>
    <?php endif; ?>
</head>
<?php 
$body_classes = [];
if (get_setting('homepage_theme', 'theme1') === 'theme2') {
    $body_classes[] = 'theme2-body';
}
if (is_logged_in() && is_editor()) {
    $body_classes[] = 'admin-bar-active';
}
$class_attr = !empty($body_classes) ? ' class="' . implode(' ', $body_classes) . '"' : '';
?>
<body<?php echo $class_attr; ?>>
    <?php 
    $current_file = basename($_SERVER['PHP_SELF']); 
    $current_slug = $_GET['slug'] ?? '';
    ?>

    <?php if (is_logged_in() && is_editor()): ?>
        <!-- CSS styles for admin top bar -->
        <style>
            .admin-top-bar {
                background: #0f172a;
                color: #e2e8f0;
                font-size: 13px;
                font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 24px;
                z-index: 10000;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 42px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            }
            .admin-bar-nav {
                display: flex;
                align-items: center;
                gap: 16px;
                overflow-x: auto;
                white-space: nowrap;
                scrollbar-width: none;
            }
            .admin-bar-nav::-webkit-scrollbar {
                display: none;
            }
            .admin-bar-link {
                color: #94a3b8;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: all 0.2s ease;
                padding: 4px 8px;
                border-radius: 4px;
            }
            .admin-bar-link:hover {
                color: #fff;
                background: rgba(255, 255, 255, 0.05);
            }
            .admin-bar-btn {
                background: var(--primary);
                color: #fff !important;
                font-weight: 700;
                padding: 4px 12px;
                border-radius: 6px;
                transition: all 0.2s ease;
                box-shadow: 0 2px 8px rgba(248, 153, 29, 0.3);
            }
            .admin-bar-btn:hover {
                opacity: 0.95;
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(248, 153, 29, 0.4);
            }
            .admin-bar-user-badge {
                background: rgba(255, 255, 255, 0.06);
                border: 1px solid rgba(255, 255, 255, 0.1);
                padding: 3px 10px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 12px;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .admin-bar-role {
                padding: 1px 6px;
                border-radius: 4px;
                font-size: 10px;
                text-transform: uppercase;
                font-weight: 800;
                letter-spacing: 0.5px;
            }
            .admin-role-admin {
                background: rgba(239, 68, 68, 0.15);
                color: #f87171;
                border: 1px solid rgba(239, 68, 68, 0.2);
            }
            .admin-role-editor {
                background: rgba(59, 130, 246, 0.15);
                color: #60a5fa;
                border: 1px solid rgba(59, 130, 246, 0.2);
            }
            .admin-role-dev {
                background: rgba(168, 85, 247, 0.15);
                color: #c084fc;
                border: 1px solid rgba(168, 85, 247, 0.2);
            }
            .admin-bar-logout {
                color: #f87171;
                font-weight: 700;
                text-decoration: none;
                transition: all 0.2s ease;
                padding: 4px 8px;
                border-radius: 4px;
            }
            .admin-bar-logout:hover {
                background: rgba(239, 68, 68, 0.1);
                color: #ef4444;
            }
            body.admin-bar-active {
                padding-top: 42px !important;
            }
            body.admin-bar-active .side-nav {
                top: 42px !important;
                height: calc(100vh - 42px) !important;
            }
            @media (max-width: 768px) {
                .admin-bar-user-section {
                    display: none !important;
                }
            }
        </style>

        <!-- Admin Top Bar HTML -->
        <div class="admin-top-bar">
            <div class="admin-bar-nav">
                <span style="font-weight: 800; display: flex; align-items: center; gap: 6px; color: #fff; margin-right: 8px;">
                    <i data-feather="sliders" style="width: 14px; height: 14px; color: var(--primary);"></i>
                    Admin Control
                </span>
                <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="admin-bar-link">
                    <i data-feather="grid" style="width: 13px; height: 13px;"></i>
                    Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>admin/post_add.php" class="admin-bar-link">
                    <i data-feather="plus" style="width: 13px; height: 13px;"></i>
                    New Post
                </a>
                <?php if (is_admin()): ?>
                    <a href="<?php echo BASE_URL; ?>admin/settings.php" class="admin-bar-link">
                        <i data-feather="settings" style="width: 13px; height: 13px;"></i>
                        Settings
                    </a>
                <?php endif; ?>
                
                <?php if ($current_file == 'article.php' && isset($post) && is_array($post) && isset($post['id'])): ?>
                    <a href="<?php echo BASE_URL; ?>admin/post_edit.php?id=<?php echo $post['id']; ?>" class="admin-bar-link admin-bar-btn">
                        <i data-feather="edit" style="width: 13px; height: 13px; stroke: #fff;"></i>
                        Edit Post
                    </a>
                <?php elseif ($current_file == 'category.php' && isset($category) && is_array($category) && isset($category['id'])): ?>
                    <a href="<?php echo BASE_URL; ?>admin/categories.php" class="admin-bar-link admin-bar-btn">
                        <i data-feather="edit" style="width: 13px; height: 13px; stroke: #fff;"></i>
                        Edit Category
                    </a>
                <?php endif; ?>
            </div>
            <div class="admin-bar-user-section" style="display: flex; align-items: center; gap: 15px; flex-shrink: 0;">
                <div class="admin-bar-user-badge">
                    <i data-feather="user" style="width: 12px; height: 12px; color: #94a3b8;"></i>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <?php 
                    $role_class = 'admin-role-editor';
                    if ($_SESSION['role'] === 'admin') $role_class = 'admin-role-admin';
                    elseif ($_SESSION['role'] === 'dev') $role_class = 'admin-role-dev';
                    ?>
                    <span class="admin-bar-role <?php echo $role_class; ?>"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                </div>
                <span style="color: rgba(255,255,255,0.15);">|</span>
                <a href="<?php echo BASE_URL; ?>logout.php" class="admin-bar-logout">
                    <i data-feather="log-out" style="width: 13px; height: 13px; vertical-align: middle; margin-right: 3px;"></i>
                    Log out
                </a>
            </div>
        </div>

        <script>
            if (typeof feather !== 'undefined') {
                feather.replace();
            } else {
                document.addEventListener("DOMContentLoaded", function() {
                    if (typeof feather !== 'undefined') feather.replace();
                });
            }
        </script>
    <?php endif; ?>
    <div class="app-container">
        <?php 
        $current_file = basename($_SERVER['PHP_SELF']); 
        $current_slug = $_GET['slug'] ?? '';
        ?>
        <!-- Vertical Sidebar -->
        <aside class="side-nav">
            <ul>
                <li>
                    <a href="<?php echo BASE_URL; ?>" class="<?php echo ($current_file == 'index.php') ? 'active' : ''; ?>">
                        <div class="icon" style="color: <?php echo ($current_file == 'index.php') ? 'var(--primary)' : '#ff3c00'; ?>;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        </div>
                        Top News
                    </a>
                </li>
                <?php if($latest_poll_header): ?>
                <li>
                    <a href="<?php echo $poll_header_url; ?>" class="<?php echo ($current_file == 'poll.php' && isset($_GET['id']) && $_GET['id'] == $latest_poll_header['id']) ? 'active' : ''; ?>">
                        <div class="icon" style="color: <?php echo ($current_file == 'poll.php' && isset($_GET['id']) && $_GET['id'] == $latest_poll_header['id']) ? 'var(--primary)' : '#9333ea'; ?>;">
                            <i data-feather="pie-chart" style="width: 18px; height: 18px;"></i>
                        </div>
                        Poll
                    </a>
                </li>
                <?php endif; ?>
                <?php foreach ($categories_tree as $cat): ?>
                    <?php 
                    $cat_url = !empty($cat['custom_url']) ? $cat['custom_url'] : BASE_URL . 'category/' . $cat['slug'];
                    $cat_target = !empty($cat['custom_url']) ? 'target="_blank"' : '';
                    
                    if (empty($cat['children'])): 
                    ?>
                        <li>
                            <a href="<?php echo $cat_url; ?>" <?php echo $cat_target; ?> class="<?php echo ($current_file == 'category.php' && $current_slug == $cat['slug']) ? 'active' : ''; ?>">
                                <div class="icon" style="color: <?php echo ($current_file == 'category.php' && $current_slug == $cat['slug']) ? 'var(--primary)' : $cat['color']; ?>;">
                                     <i data-feather="<?php echo $cat['icon']; ?>" style="width: 18px; height: 18px;"></i>
                                </div>
                                <?php echo $cat['name']; ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Parent Category with Subcategories -->
                        <li class="has-sub">
                            <a href="javascript:void(0)" class="submenu-toggle-btn" onclick="toggleSubmenu(this)" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="icon" style="color: <?php echo $cat['color']; ?>;">
                                         <i data-feather="<?php echo $cat['icon']; ?>" style="width: 18px; height: 18px;"></i>
                                    </div>
                                    <span><?php echo $cat['name']; ?></span>
                                </div>
                                <i data-feather="chevron-down" class="chevron-icon" style="width: 14px; height: 14px; transition: transform 0.2s;"></i>
                            </a>
                            <ul class="submenu-list" style="display: none; list-style: none; padding-left: 20px; margin: 5px 0 10px 0; border-left: 2px solid #e2e8f0;">
                                <li>
                                    <a href="<?php echo $cat_url; ?>" <?php echo $cat_target; ?> style="font-size: 13px; padding: 6px 10px; color:#475569; display:flex; align-items:center; gap:8px;">
                                        <span style="width:4px; height:4px; border-radius:50%; background:#94a3b8;"></span>
                                        All <?php echo $cat['name']; ?>
                                    </a>
                                </li>
                                <?php foreach ($cat['children'] as $child): 
                                    $child_url = !empty($child['custom_url']) ? $child['custom_url'] : BASE_URL . 'category/' . $child['slug'];
                                    $child_target = !empty($child['custom_url']) ? 'target="_blank"' : '';
                                ?>
                                    <li>
                                        <a href="<?php echo $child_url; ?>" <?php echo $child_target; ?> class="<?php echo ($current_file == 'category.php' && $current_slug == $child['slug']) ? 'active' : ''; ?>" style="font-size: 13px; padding: 6px 10px; color:#475569; display:flex; align-items:center; gap:8px;">
                                            <span style="width:4px; height:4px; border-radius:50%; background:<?php echo $child['color']; ?>;"></span>
                                            <?php echo $child['name']; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <!-- Follow Section -->
            <?php 
            $fb_url = get_setting('facebook_url');
            $tw_url = get_setting('twitter_url');
            $ig_url = get_setting('instagram_url');
            $yt_url = get_setting('youtube_url');
            if ($fb_url || $tw_url || $ig_url || $yt_url): 
            ?>
            <div style="padding: 20px 25px; margin-top: 10px; border-top: 1px solid #f1f5f9;">
                <span style="font-size: 12px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 15px;">Follow Us</span>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php if ($fb_url): ?>
                    <a href="<?php echo htmlspecialchars($fb_url); ?>" target="_blank" style="color: #1877F2; background: rgba(24, 119, 242, 0.1); padding: 8px; border-radius: 8px; transition: all 0.2s; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#1877F2'; this.style.color='#fff';" onmouseout="this.style.background='rgba(24, 119, 242, 0.1)'; this.style.color='#1877F2';">
                        <i data-feather="facebook" style="width: 16px; height: 16px;"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($tw_url): ?>
                    <a href="<?php echo htmlspecialchars($tw_url); ?>" target="_blank" style="color: #0f1419; background: rgba(15, 20, 25, 0.1); padding: 8px; border-radius: 8px; transition: all 0.2s; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#0f1419'; this.style.color='#fff';" onmouseout="this.style.background='rgba(15, 20, 25, 0.1)'; this.style.color='#0f1419';">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle;">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($ig_url): ?>
                    <a href="<?php echo htmlspecialchars($ig_url); ?>" target="_blank" style="color: #E1306C; background: rgba(225, 48, 108, 0.1); padding: 8px; border-radius: 8px; transition: all 0.2s; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#E1306C'; this.style.color='#fff';" onmouseout="this.style.background='rgba(225, 48, 108, 0.1)'; this.style.color='#E1306C';">
                        <i data-feather="instagram" style="width: 16px; height: 16px;"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($yt_url): ?>
                    <a href="<?php echo htmlspecialchars($yt_url); ?>" target="_blank" style="color: #FF0000; background: rgba(255, 0, 0, 0.1); padding: 8px; border-radius: 8px; transition: all 0.2s; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#FF0000'; this.style.color='#fff';" onmouseout="this.style.background='rgba(255, 0, 0, 0.1)'; this.style.color='#FF0000';">
                        <i data-feather="youtube" style="width: 16px; height: 16px;"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Sidebar Ad -->
            <div style="padding: 0 20px; margin-top: 10px;">
                <?php echo display_ad('sidebar', $pdo); ?>
            </div>
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
                <?php
endif; ?>

                <a href="<?php echo BASE_URL; ?>" class="logo-bhaskar <?php echo get_setting('site_logo') ? 'logo-has-image' : ''; ?>">
                    <?php 
                    $brand_display = get_setting('header_brand_display', 'both');
                    $has_logo = get_setting('site_logo');
                    
                    if (($brand_display == 'both' || $brand_display == 'logo') && $has_logo): 
                    ?>
                        <img src="<?php echo BASE_URL . 'assets/images/' . $has_logo; ?>" style="height: 45px;" alt="<?php echo SITE_NAME_DYNAMIC; ?>">
                    <?php elseif (($brand_display == 'both' || $brand_display == 'logo') && !$has_logo): ?>
                        <div style="background: var(--primary); color: #fff; padding: 5px 10px; border-radius: 4px; font-weight: 900; letter-spacing: -1px;">DB</div>
                    <?php endif; ?>
                    
                    <?php if ($brand_display == 'both' || $brand_display == 'name'): ?>
                        <div class="logo-text-group" style="display: flex; flex-direction: column; line-height: 1.2;">
                            <span style="font-size: 18px; letter-spacing: 1px; color: #1a1a1b; font-weight: 800;"><?php echo strtoupper(SITE_NAME_DYNAMIC); ?></span>
                            <?php $tagline = get_setting('site_tagline', 'DIGITAL NEWS'); ?>
                            <span style="font-size: 11px; font-weight: 600; color: #888; letter-spacing: .5px; text-transform: uppercase;"><?php echo htmlspecialchars($tagline); ?></span>
                        </div>
                    <?php endif; ?>
                </a>

                <div style="display: flex; align-items: center; gap: 15px;">
                    <ul class="top-menu">
                        <li>
                            <a href="<?php echo BASE_URL; ?>" class="<?php echo ($current_file == 'index.php') ? 'active' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary); margin-bottom: 5px;"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                Home
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>category/video" class="<?php echo ($current_file == 'category.php' && $current_slug == 'video') ? 'active' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #ef4444; margin-bottom: 5px;"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                                Video
                            </a>
                        </li>
                        <?php if (get_setting('ebook_magazine_enabled', 'yes') == 'yes'): ?>
                        <?php if($latest_poll_header): ?>
                        <li>
                            <a href="<?php echo $poll_header_url; ?>" class="<?php echo ($current_file == 'poll.php' && isset($_GET['id']) && $_GET['id'] == $latest_poll_header['id']) ? 'active' : ''; ?>">
                                <i data-feather="pie-chart" style="color: #9333ea; width: 20px; height: 20px; margin-bottom: 5px;"></i>
                                Poll
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>magazine" class="<?php echo ($current_file == 'magazine.php' || $current_file == 'magazine_view.php') ? 'active' : ''; ?>">
                                <i data-feather="book-open" style="color: #f59e0b; width: 20px; height: 20px; margin-bottom: 5px;"></i>
                                Magazine
                            </a>
                        </li>
                        <?php endif; ?>
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
                        <?php if (is_logged_in()): ?>
                            <a href="<?php echo BASE_URL; ?><?php echo($_SESSION['role'] ?? 'user') == 'admin' ? 'admin/dashboard.php' : 'dashboard.php'; ?>" class="btn" style="background: var(--primary); color: #fff; font-size: 14px; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;" title="My Dashboard">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </a>
                        <?php
else: ?>
                            <a href="<?php echo BASE_URL; ?>login.php" class="btn" style="background: #f1f5f9; color: #444; font-size: 14px; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;" title="Login">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                            </a>
                        <?php
endif; ?>
                    </div>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="menu-toggle" onclick="toggleMobileMenu()" style="background: none; border: none; cursor: pointer; color: #1a1a1b; display: none; padding: 5px;">
                        <i data-feather="menu"></i>
                    </button>
                </div>
            </header>

            <?php if (get_setting('homepage_theme', 'theme1') === 'theme2'): ?>
            <nav class="theme2-nav">
                <div style="max-width: 1400px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between;">
                    <ul style="display: flex; list-style: none; gap: 0; margin: 0; padding: 0; align-items: center; overflow-x: auto; scrollbar-width: none; width: 100%;">
                        <li>
                            <a href="<?php echo BASE_URL; ?>" class="t2-nav-link <?php echo ($current_file == 'index.php') ? 'active' : ''; ?>">
                                <i data-feather="home" style="width: 15px; height: 15px;"></i> Home
                            </a>
                        </li>
                        <?php foreach ($categories_tree as $cat): ?>
                            <?php 
                            $cat_url = !empty($cat['custom_url']) ? $cat['custom_url'] : BASE_URL . 'category/' . $cat['slug'];
                            $cat_target = !empty($cat['custom_url']) ? 'target="_blank"' : '';
                            $is_active = ($current_file == 'category.php' && $current_slug == $cat['slug']);
                            $cat_icon = htmlspecialchars($cat['icon'] ?: 'folder');
                            
                            if (empty($cat['children'])): 
                            ?>
                                <li>
                                    <a href="<?php echo $cat_url; ?>" <?php echo $cat_target; ?> class="t2-nav-link <?php echo $is_active ? 'active' : ''; ?>">
                                        <i data-feather="<?php echo $cat_icon; ?>" style="width: 15px; height: 15px; color: <?php echo $cat['color']; ?>;"></i>
                                        <?php echo $cat['name']; ?>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="t2-has-sub" style="position: relative;">
                                    <a href="<?php echo $cat_url; ?>" class="t2-nav-link <?php echo $is_active ? 'active' : ''; ?>">
                                        <i data-feather="<?php echo $cat_icon; ?>" style="width: 15px; height: 15px; color: <?php echo $cat['color']; ?>;"></i>
                                        <?php echo $cat['name']; ?>
                                        <i data-feather="chevron-down" style="width: 12px; height: 12px; margin-left: 2px;"></i>
                                    </a>
                                    <ul class="t2-submenu">
                                        <?php foreach ($cat['children'] as $child): 
                                            $child_url = !empty($child['custom_url']) ? $child['custom_url'] : BASE_URL . 'category/' . $child['slug'];
                                            $child_target = !empty($child['custom_url']) ? 'target="_blank"' : '';
                                            $child_icon = htmlspecialchars($child['icon'] ?: 'folder');
                                        ?>
                                            <li>
                                                <a href="<?php echo $child_url; ?>" <?php echo $child_target; ?>>
                                                    <i data-feather="<?php echo $child_icon; ?>" style="width: 14px; height: 14px; color: <?php echo $child['color']; ?>;"></i>
                                                    <?php echo $child['name']; ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </nav>
            <?php endif; ?>

            <?php
            // Suppress global breaking ticker on pages that render their own (category page has its own ticker)
            $suppress_global_ticker = (
                (get_setting('homepage_theme', 'theme1') === 'theme2' && $current_file == 'index.php') ||
                $current_file == 'category.php'
            );
            if (get_setting('breaking_news_enabled') == 'yes' && !$suppress_global_ticker):
    $breaking_stmt = $pdo->query("SELECT title, slug FROM posts WHERE status = 'published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 4");
    $breaking_news = $breaking_stmt->fetchAll();
    if ($breaking_news):
?>
            <div class="breaking-news-box" style="background: #000; color: #fff; height: 35px; display: flex; align-items: center; overflow: hidden; font-size: 13px;">
                <div style="background: var(--primary); padding: 0 15px; height: 100%; display: flex; align-items: center; font-weight: 900; skew: -10deg; margin-left: -5px; position: relative; z-index: 2;">
                    BREAKING
                </div>
                <div class="ticker-wrapper" style="flex: 1; overflow: hidden; position: relative;">
                    <div class="ticker-content" style="display: inline-block; white-space: nowrap; animation: ticker 30s linear infinite;">
                        <?php foreach ($breaking_news as $news): ?>
                            <a href="<?php echo BASE_URL; ?>article/<?php echo $news['slug']; ?>" style="color: #fff; text-decoration: none; margin-right: 50px; font-weight: 600;">
                                <span style="color: var(--primary); font-weight: 900;">•</span> <?php echo $news['title']; ?>
                            </a>
                        <?php
        endforeach; ?>
                        <!-- Duplicate content for seamless loop -->
                        <?php foreach ($breaking_news as $news): ?>
                            <a href="<?php echo BASE_URL; ?>article/<?php echo $news['slug']; ?>" style="color: #fff; text-decoration: none; margin-right: 50px; font-weight: 600;">
                                <span style="color: var(--primary); font-weight: 900;">•</span> <?php echo $news['title']; ?>
                            </a>
                        <?php
        endforeach; ?>
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
            <?php
    endif;
endif; ?>

            <!-- Mobile Sidebar Overlay -->
            <div id="mobileMenu" class="mobile-menu-overlay" onclick="toggleMobileMenu()">
                <div class="mobile-menu-content" onclick="event.stopPropagation()">
                    <div class="mobile-menu-header" style="background: var(--primary); border-bottom: none; color: #fff;">
                        <span style="font-weight: 900; color: #fff; letter-spacing: 1px;">MENU</span>
                        <button onclick="toggleMobileMenu()" style="background: rgba(255,255,255,0.2); border: none; cursor: pointer; color: #fff; border-radius: 4px; padding: 5px; display: flex; align-items: center; justify-content: center;">
                            <i data-feather="x" style="width: 18px; height: 18px;"></i>
                        </button>
                    </div>
                    
                    <form action="<?php echo BASE_URL; ?>search.php" method="GET" style="padding: 15px; margin-bottom: 10px;">
                        <div style="background: #f1f5f9; border-radius: 8px; padding: 8px 15px; display: flex; align-items: center;">
                            <input type="text" name="q" placeholder="Search news..." style="border: none; background: transparent; width: 100%; outline: none; font-size: 15px;">
                            <button type="submit" style="background: none; border: none; color: #64748b;"><i data-feather="search" style="width: 18px;"></i></button>
                        </div>
                    </form>

                    <nav class="mobile-nav-body">
                        <ul>
                            <li><a href="<?php echo BASE_URL; ?>" class="<?php echo ($current_file == 'index.php') ? 'active' : ''; ?>"><i data-feather="home" style="color: var(--primary);"></i> Home</a></li>
                            <li><a href="<?php echo BASE_URL; ?>category/video" class="<?php echo ($current_file == 'category.php' && $current_slug == 'video') ? 'active' : ''; ?>"><i data-feather="video" style="color: #ef4444;"></i> Video</a></li>
                            <?php if (get_setting('ebook_magazine_enabled', 'yes') == 'yes'): ?>
                            <?php if($latest_poll_header): ?>
                            <li><a href="<?php echo $poll_header_url; ?>" class="<?php echo ($current_file == 'poll.php' && isset($_GET['id']) && $_GET['id'] == $latest_poll_header['id']) ? 'active' : ''; ?>"><i data-feather="pie-chart" style="color: #9333ea;"></i> Poll</a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo BASE_URL; ?>magazine" class="<?php echo ($current_file == 'magazine.php' || $current_file == 'magazine_view.php') ? 'active' : ''; ?>"><i data-feather="book-open" style="color: #f59e0b;"></i> Magazine</a></li>
                            <?php endif; ?>
                            <li class="divider">Sections</li>
                            <?php foreach ($categories_tree as $cat): ?>
                                <?php 
                                $cat_url = !empty($cat['custom_url']) ? $cat['custom_url'] : BASE_URL . 'category/' . $cat['slug'];
                                $cat_target = !empty($cat['custom_url']) ? 'target="_blank"' : '';
                                
                                if (empty($cat['children'])): 
                                ?>
                                    <li>
                                        <a href="<?php echo $cat_url; ?>" <?php echo $cat_target; ?> class="<?php echo ($current_file == 'category.php' && $current_slug == $cat['slug']) ? 'active' : ''; ?>">
                                            <i data-feather="<?php echo $cat['icon']; ?>" style="color: <?php echo $cat['color']; ?>; width: 20px; height: 20px;"></i>
                                            <?php echo $cat['name']; ?>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="has-sub">
                                        <a href="javascript:void(0)" onclick="toggleSubmenu(this)" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
                                            <span style="display: flex; align-items: center; gap: 10px;">
                                                <i data-feather="<?php echo $cat['icon']; ?>" style="color: <?php echo $cat['color']; ?>; width: 20px; height: 20px;"></i>
                                                <?php echo $cat['name']; ?>
                                            </span>
                                            <i data-feather="chevron-down" class="chevron-icon" style="width: 14px; height: 14px; transition: transform 0.2s;"></i>
                                        </a>
                                        <ul class="submenu-list" style="display: none; list-style: none; padding-left: 20px; margin: 5px 0 10px 0; border-left: 2px solid #e2e8f0;">
                                            <li>
                                                <a href="<?php echo $cat_url; ?>" <?php echo $cat_target; ?> style="font-size: 14px; padding: 8px 10px; color:#475569; display:flex; align-items:center; gap:8px;">
                                                    <span style="width:4px; height:4px; border-radius:50%; background:#94a3b8;"></span>
                                                    All <?php echo $cat['name']; ?>
                                                </a>
                                            </li>
                                            <?php foreach ($cat['children'] as $child): 
                                                $child_url = !empty($child['custom_url']) ? $child['custom_url'] : BASE_URL . 'category/' . $child['slug'];
                                                $child_target = !empty($child['custom_url']) ? 'target="_blank"' : '';
                                            ?>
                                                <li>
                                                    <a href="<?php echo $child_url; ?>" <?php echo $child_target; ?> class="<?php echo ($current_file == 'category.php' && $current_slug == $child['slug']) ? 'active' : ''; ?>" style="font-size: 14px; padding: 8px 10px; color:#475569; display:flex; align-items:center; gap:8px;">
                                                        <span style="width:4px; height:4px; border-radius:50%; background:<?php echo $child['color']; ?>;"></span>
                                                        <?php echo $child['name']; ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php endif; ?>
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
                function toggleSubmenu(btn) {
                    const parent = btn.closest('.has-sub');
                    const submenu = parent.querySelector('.submenu-list');
                    const chevron = parent.querySelector('.chevron-icon');
                    
                    if (submenu.style.display === 'none' || !submenu.style.display) {
                        submenu.style.display = 'block';
                        if (chevron) chevron.style.transform = 'rotate(180deg)';
                    } else {
                        submenu.style.display = 'none';
                        if (chevron) chevron.style.transform = 'rotate(0deg)';
                    }
                }
            </script>

            <?php echo display_ad('header', $pdo); ?>
