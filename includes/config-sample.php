<?php
// Environment-Specific Configuration
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    // Local Development
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'newscast_db');
    define('BASE_URL', 'http://localhost/news/');
}
else {
    // Live Server Example
    define('DB_HOST', 'localhost');
    define('DB_USER', 'your_db_user');
    define('DB_PASS', 'your_db_password');
    define('DB_NAME', 'your_db_name');
    define('BASE_URL', 'https://yourdomain.com/');
}

// Application Constants
define('SITE_NAME', 'Panchayat Voice');

// ══════════════════════════════════════════════════════════════
//  Database Connection
// ══════════════════════════════════════════════════════════════
// Set default timezone to Indian Standard Time (Asia/Kolkata)
date_default_timezone_set('Asia/Kolkata');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    // Align MySQL session timezone with PHP's timezone to avoid timezone mismatch bugs
    $now = new DateTime();
    $mins = $now->getOffset() / 60;
    $sgn = ($mins < 0 ? -1 : 1);
    $mins = abs($mins);
    $hrs = floor($mins / 60);
    $mins -= $hrs * 60;
    $offset = sprintf('%+03d:%02d', $hrs * $sgn, $mins);
    $pdo->exec("SET time_zone = '$offset'");
}
catch (PDOException $e) {
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
if (!function_exists('get_cached_query')) {
    function get_cached_query($pdo, $sql, $params = [], $ttl = 300) {
        $cache_dir = __DIR__ . '/../cache';
        if (!is_dir($cache_dir)) {
            @mkdir($cache_dir, 0777, true);
        }
        
        $cache_key = md5($sql . serialize($params));
        $cache_file = $cache_dir . '/' . $cache_key . '.json';
        
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $ttl) {
            $data = json_decode(file_get_contents($cache_file), true);
            if (is_array($data)) return $data;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        @file_put_contents($cache_file, json_encode($data));
        return $data;
    }
}

$settings = [];
try {
    $cached_settings = get_cached_query($pdo, "SELECT setting_key, setting_value FROM settings", [], 300);
    foreach ($cached_settings as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {}

// Helper to get a setting value
function get_setting($key, $default = '')
{
    global $settings;
    return isset($settings[$key]) ? $settings[$key] : $default;
}

// Dynamic site name (from DB settings, fallback to constant)
define('SITE_NAME_DYNAMIC', get_setting('site_name') ?: SITE_NAME);
?>
