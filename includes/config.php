<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'newsportal_db');

// Application Constants
define('SITE_NAME', 'NewsPro');
define('BASE_URL', 'http://localhost/news/');

// Connect to Database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Session Start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Site Settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Settings table might not exist yet
}

// Helper to get setting
function get_setting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) ? $settings[$key] : $default;
}

// Update Site Name from settings
if (get_setting('site_name')) {
    define('SITE_NAME_DYNAMIC', get_setting('site_name'));
} else {
    define('SITE_NAME_DYNAMIC', SITE_NAME);
}
?>
