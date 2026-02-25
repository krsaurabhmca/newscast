<?php
/**
 * NewsCast CMS - Professional Setup Wizard
 * Version: 1.0.0
 */

ob_start();
session_start();

$config_file = 'includes/config.php';

// Basic guard: If config exists, we only redirect if we aren't currently on the install page to fix it
// The index.php/login.php will handle redirecting TO here if config is missing or broken.
if (file_exists($config_file) && $step == 3) {
    // Only block access if installation is clearly finished
    header("Location: index.php");
    exit;
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

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

            // 2. Create Admin Account
            $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$admin_user, $admin_email, $hashed_pass]);
            $admin_id = $pdo->lastInsertId();

            // 3. Create Default Category
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, color) VALUES (?, ?, ?, ?)");
            $stmt->execute(['General', 'general', 'General News and Updates', '#6366f1']);
            $cat_id = $pdo->lastInsertId();

            // 4. Create Welcome Post
            $welcome_title = "Welcome to " . $site_name;
            $welcome_slug = "welcome-to-newscast";
            $welcome_content = "<h2>Greetings!</h2><p>This is your first news article. You can edit or delete this post from the Articles management section in your admin dashboard. Start publishing your stories to reach your audience!</p>";
            
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, slug, content, excerpt, status, is_featured) VALUES (?, ?, ?, ?, ?, 'published', 1)");
            $stmt->execute([$admin_id, $welcome_title, $welcome_slug, $welcome_content, 'Your journey with NewsCast CMS starts here!', 'published', 1]);
            $post_id = $pdo->lastInsertId();

            // Link post to category
            $pdo->exec("INSERT INTO post_categories (post_id, category_id) VALUES ($post_id, $cat_id)");

            // 5. Insert Initial Settings
            $settings = [
                'site_name' => $site_name,
                'site_tagline' => 'Digital News Portal',
                'live_youtube_enabled' => '0',
                'live_stream_sound' => '0',
                'breaking_news_enabled' => 'yes',
                'theme_color' => '#ff3c00'
            ];
            foreach ($settings as $k => $v) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$k, $v]);
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
            --bg: #f8fafc;
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
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
            background-attachment: fixed;
        }

        .setup-container {
            width: 100%;
            max-width: 550px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-box {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, #818cf8 100%);
            color: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 900;
            margin: 0 auto 15px;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        h1 { font-size: 24px; font-weight: 800; margin-bottom: 8px; }
        p.subtitle { color: var(--text-light); font-size: 14px; }

        .steps {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }
        .step-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--border);
        }
        .step-dot.active {
            background: var(--primary);
            width: 24px;
            border-radius: 4px;
        }

        form { display: flex; flex-direction: column; gap: 20px; }

        .form-group { display: flex; flex-direction: column; gap: 8px; }
        label { font-size: 13px; font-weight: 700; color: var(--text); padding-left: 2px; }
        
        input {
            padding: 14px 18px;
            border-radius: 12px;
            border: 1px solid var(--border);
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
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn:hover { background: var(--primary-dark); transform: translateY(-1px); }

        .alert {
            padding: 15px;
            border-radius: 12px;
            background: #fef2f2;
            color: #dc2626;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid #fee2e2;
            margin-bottom: 20px;
        }

        .success-box {
            text-align: center;
            padding: 20px 0;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #ecfdf5;
            color: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        /* Loading Animation */
        .loader {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="setup-container">
    <div class="header">
        <div class="logo-box">NC</div>
        <h1>NewsCast CMS Setup</h1>
        <p class="subtitle">Complete the steps to launch your portal.</p>
    </div>

    <div class="steps">
        <div class="step-dot <?php echo $step == 1 ? 'active' : ''; ?>"></div>
        <div class="step-dot <?php echo $step == 2 ? 'active' : ''; ?>"></div>
        <div class="step-dot <?php echo $step == 3 ? 'active' : ''; ?>"></div>
    </div>

    <?php if ($error): ?>
        <div class="alert"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($step == 1): ?>
        <!-- STEP 1: DATABASE SETUP -->
        <form method="POST">
            <h2 style="font-size: 18px; margin-bottom: 10px;">Database Connection</h2>
            <div class="form-group">
                <label>Database Host</label>
                <input type="text" name="db_host" value="localhost" placeholder="localhost" required>
            </div>
            <div class="form-group">
                <label>Database User</label>
                <input type="text" name="db_user" value="root" placeholder="Username" required>
            </div>
            <div class="form-group">
                <label>Database Password</label>
                <input type="password" name="db_pass" placeholder="Leave blank if none">
            </div>
            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" value="newscast_db" placeholder="Database Name" required>
            </div>
            <button type="submit" name="save_db" class="btn">
                Test Connection & Continue <i data-feather="arrow-right" style="width: 18px;"></i>
            </button>
        </form>

    <?php elseif ($step == 2): ?>
        <!-- STEP 2: SITE & ADMIN -->
        <form method="POST">
            <h2 style="font-size: 18px; margin-bottom: 10px;">Portal Configuration</h2>
            
            <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Site Name</label>
                    <input type="text" name="site_name" value="Panchayat Voice" required>
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border); margin: 10px 0;">
            <h2 style="font-size: 18px; margin-bottom: 5px;">Admin Account</h2>
            <p style="font-size: 12px; color: var(--text-light); margin-bottom: 10px;">Use these credentials to login later.</p>
            
            <div class="form-group">
                <label>Admin Username</label>
                <input type="text" name="admin_user" placeholder="admin" required>
            </div>
            <div class="form-group">
                <label>Admin Email</label>
                <input type="email" name="admin_email" placeholder="admin@example.com" required>
            </div>
            <div class="form-group">
                <label>Admin Password</label>
                <input type="password" name="admin_pass" placeholder="Create strong password" required>
            </div>

            <button type="submit" name="install_now" class="btn" style="background: #1e293b;">
                Finish Installation <i data-feather="check-circle" style="width: 18px;"></i>
            </button>
        </form>

    <?php elseif ($step == 3): ?>
        <!-- STEP 3: SUCCESS -->
        <div class="success-box">
            <div class="success-icon">
                <i data-feather="check" style="width: 48px; height: 48px;"></i>
            </div>
            <h2 style="font-weight: 800; margin-bottom: 10px;">Installation Successful!</h2>
            <p style="color: var(--text-light); margin-bottom: 30px;">Your news portal has been successfully configured and is ready for use.</p>
            
            <a href="login.php" class="btn">
                Login to Dashboard <i data-feather="log-in" style="width: 18px;"></i>
            </a>
            
            <p style="font-size: 12px; color: #ef4444; font-weight: 700; margin-top: 25px;">
                <i data-feather="alert-triangle" style="width: 12px;"></i> For security, please delete install.php from your server.
            </p>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 30px; border-top: 1px solid var(--border); padding-top: 15px;">
        <p style="font-size: 12px; color: var(--text-light); font-weight: 600;">Powered by NewsCast CMS v1.0</p>
    </div>
</div>

<script>
    feather.replace();
</script>
</body>
</html>
