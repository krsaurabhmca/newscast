<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'newscast_db');
define('BASE_URL', 'http://localhost/news/');

// Application Constants
define('SITE_NAME', 'Panchayat Voice');

// ══════════════════════════════════════════════════════════════
//  Database Connection
// ══════════════════════════════════════════════════════════════
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // If DB connection fails, redirect to installer
    if (file_exists('install.php') || file_exists('../install.php')) {
        $path = file_exists('install.php') ? 'install.php' : '../install.php';
        header("Location: $path");
        exit;
    }
    
    // Fallback if installer is deleted
    die("<div style='font-family:sans-serif;padding:40px;text-align:center;'>
            <h2 style='color:#dc2626;'>Database Connection Error</h2>
            <p style='color:#64748b;'>Could not connect to the database. Please check your configuration.</p>
            <a href='index.php' style='display:inline-block; margin-top:20px; color:#6366f1; font-weight:700; text-decoration:none;'>Retry Connection</a>
         </div>");
}

// ══════════════════════════════════════════════════════════════
//  Session
// ══════════════════════════════════════════════════════════════
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ══════════════════════════════════════════════════════════════
//  Site Settings (from DB)
// ══════════════════════════════════════════════════════════════
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Settings table may not exist yet on first run — safe to ignore
}

// Helper to get a setting value
function get_setting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) ? $settings[$key] : $default;
}

// Dynamic site name (from DB settings, fallback to constant)
define('SITE_NAME_DYNAMIC', get_setting('site_name') ?: SITE_NAME);
?>
