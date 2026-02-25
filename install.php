<?php
/**
 * NewsCast CMS - Professional Setup Wizard
 * Version: 1.0.0
 */

ob_start();
session_start();

$config_file = 'includes/config.php';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Basic guard: If config exists, we only redirect if we aren't currently on the install page to fix it
// The index.php/login.php will handle redirecting TO here if config is missing or broken.
if (file_exists($config_file) && $step == 3) {
    // Only block access if installation is clearly finished
    header("Location: index.php");
    exit;
}

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_db'])) {
        // Step 1: Test and Save DB Info to Session
        $db_host = $_POST['db_host'];
        $db_user = $_POST['db_user'];
        $db_pass = $_POST['db_pass'];
        $db_name = $_POST['db_name'];

        try {
            // Try connection without DB name first (incase it doesn't exist)
            $test_pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
            // Try to create DB
            $test_pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Now try connecting to the DB
            $test_pdo->exec("USE `$db_name`");
            
            $_SESSION['db_setup'] = [
                'host' => $db_host,
                'user' => $db_user,
                'pass' => $db_pass,
                'name' => $db_name
            ];
            header("Location: install.php?step=2");
            exit;
        } catch (PDOException $e) {
            $error = "Database Connection Failed: " . $e->getMessage();
        }
    }

    if (isset($_POST['install_now'])) {
        // Step 2: Final Install
        $site_name = $_POST['site_name'];
        $admin_user = $_POST['admin_user'];
        $admin_email = $_POST['admin_email'];
        $admin_pass = $_POST['admin_pass'];
        
        $db = $_SESSION['db_setup'];

        try {
            $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['name'], $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            // 1. Create Tables
            $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin', 'editor') DEFAULT 'editor',
                profile_image VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                icon VARCHAR(100) DEFAULT 'folder',
                color VARCHAR(20) DEFAULT '#6366f1',
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                content LONGTEXT NOT NULL,
                excerpt TEXT,
                featured_image VARCHAR(255),
                video_url VARCHAR(255),
                external_link TEXT,
                external_type ENUM('none', 'url', 'whatsapp', 'call') DEFAULT 'none',
                external_label ENUM('none', 'Ad', 'Promoted', 'Sponsored') DEFAULT 'none',
                status ENUM('draft', 'published') DEFAULT 'draft',
                views INT DEFAULT 0,
                is_featured BOOLEAN DEFAULT FALSE,
                is_breaking BOOLEAN DEFAULT FALSE,
                meta_description VARCHAR(160),
                published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS timeline (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_time VARCHAR(20) NOT NULL,
                description TEXT NOT NULL,
                status_color VARCHAR(20) DEFAULT '#6366f1',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS feedback (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100),
                email VARCHAR(100),
                subject VARCHAR(255),
                message TEXT,
                status ENUM('new', 'read', 'archived') DEFAULT 'new',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS bookmarks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                post_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY user_post (user_id, post_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS user_activity (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                post_id INT NOT NULL,
                action_type ENUM('view', 'bookmark', 'share') DEFAULT 'view',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS ads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                location VARCHAR(50) NOT NULL,
                type ENUM('image', 'code') NOT NULL,
                image_path VARCHAR(255),
                link_url TEXT,
                link_type ENUM('url', 'whatsapp', 'call') DEFAULT 'url',
                ad_code TEXT,
                start_date DATE,
                end_date DATE,
                status BOOLEAN DEFAULT TRUE,
                impressions INT DEFAULT 0,
                clicks INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS epapers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                paper_date DATE NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                thumbnail VARCHAR(255),
                dimensions VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS magazines (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                issue_month DATE NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                cover_image VARCHAR(255),
                pages SMALLINT DEFAULT 0,
                status ENUM('published','draft') DEFAULT 'published',
                downloads INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS tags (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                slug VARCHAR(100) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS post_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                category_id INT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS post_tags (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                tag_id INT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            $pdo->exec($sql);

            // 2. Create Admin Account (using IGNORE to avoid duplicate error)
            $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_user, $admin_email, $hashed_pass, 'admin']);
            
            // Get admin_id (either newly created or existing)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$admin_email]);
            $admin_id = $stmt->fetchColumn();

            // 3. Create Popular Categories
            $popular_categories = [
                ['name' => 'General', 'slug' => 'general', 'icon' => 'grid', 'color' => '#64748b'],
                ['name' => 'Politics', 'slug' => 'politics', 'icon' => 'users', 'color' => '#ef4444'],
                ['name' => 'Business', 'slug' => 'business', 'icon' => 'briefcase', 'color' => '#3b82f6'],
                ['name' => 'Technology', 'slug' => 'technology', 'icon' => 'cpu', 'color' => '#8b5cf6'],
                ['name' => 'Entertainment', 'slug' => 'entertainment', 'icon' => 'film', 'color' => '#ec4899'],
                ['name' => 'Sports', 'slug' => 'sports', 'icon' => 'award', 'color' => '#f59e0b'],
                ['name' => 'Education', 'slug' => 'education', 'icon' => 'book-open', 'color' => '#10b981'],
                ['name' => 'Lifestyle', 'slug' => 'lifestyle', 'icon' => 'sun', 'color' => '#f43f5e']
            ];

            $stmt_cat = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description, icon, color) VALUES (?, ?, ?, ?, ?)");
            foreach ($popular_categories as $cat) {
                $description = $cat['name'] . " news and latest updates.";
                $stmt_cat->execute([$cat['name'], $cat['slug'], $description, $cat['icon'], $cat['color']]);
            }
            
            // Get cat_id (Default to General for welcome post)
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            $stmt->execute(['general']);
            $cat_id = $stmt->fetchColumn();

            // 4. Create Welcome Post (Comprehensive Guide)
            $welcome_title = "Welcome to " . $site_name . " - Quick Start Guide";
            $welcome_slug = "welcome-guide";
            
            // Use share.png from assets/images
            $welcome_image = 'share.png';
            if (file_exists('assets/images/share.png')) {
                if (!is_dir('assets/images/posts')) mkdir('assets/images/posts', 0777, true);
                copy('assets/images/share.png', 'assets/images/posts/share.png');
            }

            $welcome_content = "
            <div class='guide-intro'>
                <p>Congratulations! You have successfully installed <strong>" . $site_name . "</strong>. This portal is built for high-performance digital journalism.</p>
                
                <h3>🚀 Your First Steps</h3>
                <ul>
                    <li><strong>Dashboard:</strong> Visit the <a href='admin/dashboard.php'>Admin Dashboard</a> to see real-time analytics.</li>
                    <li><strong>Categories:</strong> We have pre-created popular categories like Politics, Tech, and Business with custom icons.</li>
                    <li><strong>Featured Stories:</strong> Toggle the 'Featured' switch when writing an article to show it prominently on the homepage.</li>
                </ul>

                <h3>📺 Multimedia Features</h3>
                <p>You can embed any <strong>YouTube Video</strong> by simply pasting the link. Our player is optimized with a transparent security layer to keep users focused on your content.</p>

                <h3>📈 Monetization</h3>
                <p>Manage your advertisements from the 'Revenue' section. You can track impressions and clicks for both image and code-based ads.</p>

                <div style='background: #f1f5f9; padding: 20px; border-radius: 12px; margin-top: 20px;'>
                    <h4 style='margin-bottom: 5px;'>🔐 Security Tip</h4>
                    <p style='font-size: 13px; color: #64748b;'>Don't forget to delete the <code>install.php</code> file from your server root now that you are finished!</p>
                </div>
            </div>";
            
            $stmt = $pdo->prepare("INSERT IGNORE INTO posts (user_id, title, slug, content, excerpt, featured_image, status, is_featured) VALUES (?, ?, ?, ?, ?, ?, 'published', 1)");
            $stmt->execute([$admin_id, $welcome_title, $welcome_slug, $welcome_content, 'Everything you need to know about your new news portal.', $welcome_image]);
            
            // Get post_id
            $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
            $stmt->execute([$welcome_slug]);
            $post_id = $stmt->fetchColumn();

            // Link post to multiple categories (General and Technology)
            if ($post_id) {
                $target_slugs = ['general', 'technology'];
                foreach ($target_slugs as $tslug) {
                    $s_cat = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
                    $s_cat->execute([$tslug]);
                    $t_id = $s_cat->fetchColumn();
                    if ($t_id) {
                        try {
                            $stmt = $pdo->prepare("INSERT IGNORE INTO post_categories (post_id, category_id) VALUES (?, ?)");
                            $stmt->execute([$post_id, $t_id]);
                        } catch (Exception $e) {}
                    }
                }
            }

            // 5. Insert Initial Settings
            $settings_data = [
                'site_name' => $site_name,
                'site_tagline' => 'Digital News Portal',
                'live_youtube_enabled' => '0',
                'live_stream_sound' => '0',
                'breaking_news_enabled' => 'yes',
                'theme_color' => '#6366f1'
            ];
            foreach ($settings_data as $k => $v) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$k, $v, $v]);
            }

            // 4. Create config.php
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/';
            $base_url = str_replace('\\', '/', $base_url); // fix windows paths
            $base_url = rtrim($base_url, '/') . '/';

            $config_content = "<?php
define('DB_HOST', '" . $db['host'] . "');
define('DB_USER', '" . $db['user'] . "');
define('DB_PASS', '" . $db['pass'] . "');
define('DB_NAME', '" . $db['name'] . "');
define('BASE_URL', '" . $base_url . "');

define('SITE_NAME', '" . addslashes($site_name) . "');

try {
    \$pdo = new PDO(
        \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException \$e) {
    die(\"Database Connection Error: \" . \$e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

\$settings = [];
try {
    \$stmt_set = \$pdo->query(\"SELECT setting_key, setting_value FROM settings\");
    while (\$row = \$stmt_set->fetch()) {
        \$settings[\$row['setting_key']] = \$row['setting_value'];
    }
} catch (Exception \$e) {}

function get_setting(\$key, \$default = '') {
    global \$settings;
    return isset(\$settings[\$key]) ? \$settings[\$key] : \$default;
}

define('SITE_NAME_DYNAMIC', get_setting('site_name') ?: SITE_NAME);
?>";
            
            file_put_contents($config_file, $config_content);
            
            $_SESSION['install_done'] = true;
            header("Location: install.php?step=3");
            exit;

        } catch (Exception $e) {
            $error = "Critical Error: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Wizard | NewsCast CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg: #0f172a;
            --card: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            background: radial-gradient(circle at top right, #6366f122, transparent),
                        radial-gradient(circle at bottom left, #ec489922, transparent),
                        #0f172a;
            overflow-x: hidden;
        }

        /* Animated Background Elements */
        .bg-glow {
            position: fixed;
            width: 400px;
            height: 400px;
            background: var(--primary);
            filter: blur(120px);
            opacity: 0.15;
            z-index: -1;
            border-radius: 50%;
            animation: move 20s infinite alternate;
        }

        @keyframes move {
            from { transform: translate(-10%, -10%); }
            to { transform: translate(110%, 110%); }
        }

        .setup-wrapper {
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 340px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            box-shadow: 0 50px 100px -20px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .setup-main {
            padding: 50px;
        }

        .setup-sidebar {
            background: #f8fafc;
            padding: 50px 30px;
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .header {
            margin-bottom: 40px;
        }

        .logo-box {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, #818cf8 100%);
            color: white;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 900;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; color: #0f172a; }
        p.subtitle { color: var(--text-light); font-size: 15px; }

        .steps-nav {
            display: flex;
            gap: 15px;
            margin-bottom: 40px;
        }
        .step-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-light);
        }
        .step-item.active { color: var(--primary); }
        .step-num {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            background: var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }
        .step-item.active .step-num { background: var(--primary); color: white; }

        form { display: flex; flex-direction: column; gap: 24px; }

        .form-group { display: flex; flex-direction: column; gap: 8px; }
        label { font-size: 13px; font-weight: 700; color: #475569; padding-left: 2px; }
        
        input {
            padding: 14px 20px;
            border-radius: 14px;
            border: 2px solid var(--border);
            font-family: inherit;
            font-size: 15px;
            transition: 0.2s;
            outline: none;
            background: #fff;
        }
        input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }

        .btn {
            background: var(--primary);
            color: white;
            padding: 16px;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .btn:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2); }

        .help-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .help-card h4 { font-size: 14px; font-weight: 800; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; color: var(--primary); }
        .help-card p { font-size: 13px; color: var(--text-light); line-height: 1.5; }

        .alert {
            padding: 16px;
            border-radius: 14px;
            background: #fff1f2;
            color: #e11d48;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid #ffe4e6;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-hero {
            text-align: center;
            padding: 20px 0;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: #f0fdf4;
            color: #22c55e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }

        @media (max-width: 850px) {
            .setup-wrapper { grid-template-columns: 1fr; border-radius: 20px; }
            .setup-sidebar { display: none; }
            .setup-main { padding: 40px 25px; }
        }
    </style>
</head>
<body>

<div class="bg-glow"></div>

<div class="setup-wrapper">
    <div class="setup-main">
        <div class="header">
            <div class="logo-box">
                <?php 
                    $site_name_display = isset($_POST['site_name']) ? $_POST['site_name'] : 'NC';
                    echo strtoupper(substr($site_name_display, 0, 2)); 
                ?>
            </div>
            <h1>NewsCast Setup</h1>
            <p class="subtitle">Join thousands of professional digital publishers.</p>
        </div>

        <div class="steps-nav">
            <div class="step-item <?php echo $step == 1 ? 'active' : ''; ?>">
                <div class="step-num">1</div> Database
            </div>
            <div class="step-item <?php echo $step == 2 ? 'active' : ''; ?>">
                <div class="step-num">2</div> Configuration
            </div>
            <div class="step-item <?php echo $step == 3 ? 'active' : ''; ?>">
                <div class="step-num">3</div> Finish
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert">
                <i data-feather="alert-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>Database User</label>
                    <input type="text" name="db_user" value="root" required>
                </div>
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_pass" placeholder="Password (root by default)">
                </div>
                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" name="db_name" value="newscast_db" required>
                </div>
                <button type="submit" name="save_db" class="btn">
                    Connect Database <i data-feather="arrow-right"></i>
                </button>
            </form>

        <?php elseif ($step == 2): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Project / Site Name</label>
                    <input type="text" name="site_name" value="NewsCast" required>
                </div>

                <div style="margin: 10px 0; border-top: 2px solid #f1f5f9;"></div>
                
                <div class="form-group">
                    <label>Admin Username</label>
                    <input type="text" name="admin_user" placeholder="e.g. admin" required>
                </div>
                <div class="form-group">
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" placeholder="admin@domain.com" required>
                </div>
                <div class="form-group">
                    <label>Admin Password</label>
                    <input type="password" name="admin_pass" placeholder="Secure Password" required>
                </div>

                <button type="submit" name="install_now" class="btn">
                    Launch My Portal <i data-feather="zap"></i>
                </button>
            </form>

        <?php elseif ($step == 3): ?>
            <div class="success-hero">
                <div class="icon-circle">
                    <i data-feather="check" style="width: 40px; height: 40px;"></i>
                </div>
                <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 12px;">Infrastructure Ready!</h2>
                <p style="color: var(--text-light); margin-bottom: 40px; line-height: 1.6;">Your professional news portal is now online. We've added a Welcome Guide to your homepage to help you get started.</p>
                
                <a href="login.php" class="btn">
                    Go to Admin Panel <i data-feather="log-in"></i>
                </a>
                
                <div style="margin-top: 30px; padding: 20px; border-radius: 16px; background: #fff7ed; border: 1px solid #ffedd5; text-align: left;">
                    <h4 style="font-size: 14px; font-weight: 800; color: #9a3412; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                        <i data-feather="shield" style="width: 16px;"></i> Security Requirement
                    </h4>
                    <p style="font-size: 12px; color: #c2410c;">For your protection, please delete the <strong>install.php</strong> file from your server folder immediately.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="setup-sidebar">
        <div class="help-card">
            <h4><i data-feather="help-circle"></i> Need Help?</h4>
            <p>If you are on XAMPP, usually <strong>Host</strong> is 'localhost' and <strong>User</strong> is 'root' with an empty password.</p>
        </div>

        <div class="help-card">
            <h4><i data-feather="database"></i> Auto-Create</h4>
            <p>Don't have a database? Just enter a name and our wizard will try to create it for you automatically.</p>
        </div>

        <div class="help-card">
            <h4><i data-feather="award"></i> Pro Features</h4>
            <p>Once setup is done, your portal will have E-Paper support, Live Broadcasting, and Advanced Ad Management active.</p>
        </div>

        <div style="margin-top: auto; text-align: center;">
            <p style="font-size: 11px; font-weight: 700; color: #94a3b8; letter-spacing: 1px;">NEWSCAST CMS v1.0</p>
        </div>
    </div>
</div>

<script>
    feather.replace();
</script>
</body>
</html>
