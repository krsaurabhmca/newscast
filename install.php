<?php
/**
 * NewsCast - Web Installer Wizard
 * Developed for professional, automated deployment.
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config_file = 'includes/config.php';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// If already configured, don't allow re-install unless explicitly requested (e.g. by deleting config.php)
if (file_exists($config_file) && $step < 5) {
    // Try connecting to existing database
    try {
        require_once $config_file;
        if (isset($pdo)) {
            // If we can reach here, system is already installed.
            // But let's check if the 'users' table exists to be sure.
            $check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
            if ($check) {
                die("NewsCast is already installed. To reinstall, please remove <code>includes/config.php</code>.");
            }
        }
    } catch (Exception $e) {
        // config exists but connection fails, continue to installer
    }
}

function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Logic for Step 2 -> 3 (DB Setup)
if ($step == 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = clean_input($_POST['db_host']);
    $db_name = clean_input($_POST['db_name']);
    $db_user = clean_input($_POST['db_user']);
    $db_pass = $_POST['db_pass'];
    $base_url = clean_input($_POST['base_url']);

    // Attempt connection
    try {
        $dsn = "mysql:host=$db_host;charset=utf8mb4";
        $test_pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        // Create database if not exists
        $test_pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $test_pdo->exec("USE `$db_name`");

        // Import SQL File
        $sql_file = 'newscast_db.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            // Remove comments and multi-line comments
            $sql = preg_replace('/--.*?\n/', '', $sql);
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
            
            $queries = explode(';', $sql);
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $test_pdo->exec($query);
                }
            }
        }

        // Generate Config File
        $config_content = "<?php
define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_NAME', '$db_name');
define('BASE_URL', '$base_url');

define('SITE_NAME', 'NewsCast');

try {
    \$pdo = new PDO(
        \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
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
        
        header("Location: install.php?step=3");
        exit;
    } catch (PDOException $e) {
        $error = "Database connection failed: " . $e->getMessage();
    }
}

// Logic for Step 3 -> 4 (Admin Setup)
if ($step == 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once $config_file;
    $adm_user = clean_input($_POST['adm_user']);
    $adm_pass = password_hash($_POST['adm_pass'], PASSWORD_BCRYPT);
    $adm_email = clean_input($_POST['adm_email']);
    $site_name = clean_input($_POST['site_name']);

    try {
        // Clear existing users if any to ensure fresh start
        $pdo->exec("TRUNCATE TABLE users");
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, 'admin', 'active')");
        $stmt->execute([$adm_user, $adm_pass, $adm_email, 'admin']);

        // Update site name in settings
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_name', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$site_name, $site_name]);

        header("Location: install.php?step=4");
        exit;
    } catch (Exception $e) {
        $error = "Admin setup failed: " . $e->getMessage();
    }
}

// Get primary base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$current_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . str_replace('install.php', '', $_SERVER['REQUEST_URI']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NewsCast Installation Wizard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1c1e21; }
        .install-box { max-width: 650px; margin: 60px auto; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 40px; position: relative; }
        .step-indicator::before { content: ''; position: absolute; top: 18px; left: 0; right: 0; height: 2px; background: #e4e6eb; z-index: 1; }
        .step-item { position: relative; z-index: 2; background: white; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #e4e6eb; font-weight: 700; color: #8a8d91; transition: 0.3s; }
        .step-item.active { border-color: #0d6efd; background: #0d6efd; color: white; }
        .step-item.completed { border-color: #198754; background: #198754; color: white; }
        .btn-primary { padding: 10px 30px; border-radius: 10px; font-weight: 600; box-shadow: 0 4px 10px rgba(13,110,253,0.2); }
        .form-label { font-weight: 600; color: #4b4f56; font-size: 14px; }
        .form-control { padding: 12px; border-radius: 10px; border: 1.5px solid #dddfe2; }
        .form-control:focus { box-shadow: none; border-color: #0d6efd; }
        .icon-circle { width: 60px; height: 60px; background: #e7f3ff; color: #0d6efd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 20px; }
        .requirement-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f2f5; font-size: 15px; }
        .requirement-item:last-child { border-bottom: none; }
    </style>
</head>
<body>

<div class="container">
    <div class="install-box">
        
        <!-- Header -->
        <div class="text-center mb-5">
            <h1 class="fw-bolder h3 mb-1">NewsCast</h1>
            <p class="text-muted small text-uppercase fw-bold letter-spacing-1">Installation Wizard</p>
        </div>

        <!-- Progress Bar -->
        <div class="step-indicator">
            <div class="step-item <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>"><?php echo $step > 1 ? '<i class="bi bi-check-lg"></i>' : '1'; ?></div>
            <div class="step-item <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>"><?php echo $step > 2 ? '<i class="bi bi-check-lg"></i>' : '2'; ?></div>
            <div class="step-item <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>"><?php echo $step > 3 ? '<i class="bi bi-check-lg"></i>' : '3'; ?></div>
            <div class="step-item <?php echo $step >= 4 ? 'active' : ''; ?>">4</div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- STEP 1: WELCOME & REQUIREMENTS -->
        <?php if ($step == 1): ?>
            <div class="icon-circle"><i class="bi bi-rocket-takeoff"></i></div>
            <h4 class="text-center fw-bold mb-3">Welcome to NewsCast</h4>
            <p class="text-center text-muted mb-4 px-4">Let's check if your server is ready to host the most powerful digital news portal.</p>
            
            <div class="mb-4">
                <div class="requirement-item">
                    <span>PHP Version (>= 7.4)</span>
                    <span><?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? '<i class="bi bi-check-circle-fill text-success"></i> ' . PHP_VERSION : '<i class="bi bi-x-circle-fill text-danger"></i> ' . PHP_VERSION; ?></span>
                </div>
                <div class="requirement-item">
                    <span>MySQL / PDO Extension</span>
                    <span><?php echo extension_loaded('pdo_mysql') ? '<i class="bi bi-check-circle-fill text-success"></i> Loaded' : '<i class="bi bi-x-circle-fill text-danger"></i> Missing'; ?></span>
                </div>
                <div class="requirement-item">
                    <span>GD Library (Image Compression)</span>
                    <span><?php echo extension_loaded('gd') ? '<i class="bi bi-check-circle-fill text-success"></i> Loaded' : '<i class="bi bi-x-warning text-warning"></i> Optional but Recommended'; ?></span>
                </div>
                <div class="requirement-item">
                    <span>Config Directory Writable</span>
                    <span><?php echo is_writable('includes') ? '<i class="bi bi-check-circle-fill text-success"></i> Yes' : '<i class="bi bi-x-circle-fill text-danger"></i> No (CHMOD 755/777)'; ?></span>
                </div>
            </div>

            <div class="text-center">
                <a href="install.php?step=2" class="btn btn-primary w-100">Let's Go <i class="bi bi-arrow-right ms-2"></i></a>
            </div>

        <!-- STEP 2: DATABASE CONFIG -->
        <?php elseif ($step == 2): ?>
            <h4 class="fw-bold mb-1">Database Configuration</h4>
            <p class="text-muted small mb-4">Provide your MySQL database credentials below.</p>
            
            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Base URL (Automatic)</label>
                        <input type="text" name="base_url" class="form-control" value="<?php echo $current_url; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Database Host</label>
                        <input type="text" name="db_host" class="form-control" value="localhost" placeholder="localhost" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Database Name</label>
                        <input type="text" name="db_name" class="form-control" placeholder="newscast_db" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DB Username</label>
                        <input type="text" name="db_user" class="form-control" placeholder="root" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DB Password</label>
                        <input type="password" name="db_pass" class="form-control" placeholder="Leave empty if none">
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary w-100">Setup Database & Tables <i class="bi bi-database-add ms-2"></i></button>
                    <p class="mt-3 small text-muted"><i class="bi bi-shield-lock"></i> We will create the database automatically if it doesn't exist.</p>
                </div>
            </form>

        <!-- STEP 3: ADMIN ACCOUNT -->
        <?php elseif ($step == 3): ?>
            <div class="icon-circle" style="background: #e6fffa; color: #38b2ac;"><i class="bi bi-person-badge"></i></div>
            <h4 class="text-center fw-bold mb-1">Success! Database Linked.</h4>
            <p class="text-center text-muted small mb-4">Now, create your first Administrator account.</p>
            
            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">News Website Name</label>
                        <input type="text" name="site_name" class="form-control" value="NewsCast 24x7" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Admin Username</label>
                        <input type="text" name="adm_user" class="form-control" placeholder="e.g. admin" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Admin Email</label>
                        <input type="email" name="adm_email" class="form-control" placeholder="your@email.com" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Admin Password</label>
                        <input type="password" name="adm_pass" class="form-control" required minlength="6">
                        <div class="form-text">Choose a strong password for your portal security.</div>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary w-100">Finish Installation <i class="bi bi-check-all ms-2"></i></button>
                </div>
            </form>

        <!-- STEP 4: FINISHED -->
        <?php elseif ($step == 4): ?>
            <div class="text-center mt-4">
                <div class="icon-circle" style="background: #f0fdf4; color: #16a34a; width: 80px; height: 80px;"><i class="bi bi-check-circle" style="font-size: 40px;"></i></div>
                <h3 class="fw-bolder text-success">All Done!</h3>
                <p class="text-muted mb-5">NewsCast has been successfully installed. You can now login to your dashboard and start publishing news.</p>
                
                <div class="alert alert-warning small text-start mb-4">
                    <i class="bi bi-shield-exclamation"></i> <strong>Security Tip:</strong> Please delete the <code>install.php</code> and <code>newscast_db.sql</code> files from your server root now.
                </div>

                <a href="login.php" class="btn btn-primary btn-lg w-100 mb-3">Login to Admin Panel</a>
                <a href="index.php" class="btn btn-outline-secondary w-100">View Homepage</a>
            </div>
        <?php endif; ?>

        <div class="text-center mt-5">
            <p class="text-muted small">&copy; <?php echo date('Y'); ?> NewsCast Digital. Handcrafted by <a href="https://offerplant.com" target="_blank" class="text-decoration-none">OfferPlant</a></p>
        </div>

    </div>
</div>

</body>
</html>
