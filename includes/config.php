<?php
// ══════════════════════════════════════════════════════════════
//  AUTO-DETECT: Local (XAMPP) vs Live Server
// ══════════════════════════════════════════════════════════════
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_live = (strpos($host, 'panchayatvoice.in') !== false);

if ($is_live) {
    // ── Live Server ─────────────────────────────────────────
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u305984835_pvoice');
    define('DB_PASS', '@Voice_2001');
    define('DB_NAME', 'u305984835_pvoice');
    define('BASE_URL', 'https://panchayatvoice.in/');
} else {
    // ── Local Development (XAMPP) ───────────────────────────
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'newsportal_db');
    define('BASE_URL', 'http://localhost/news/');
}

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
    // Show friendly error instead of exposing credentials
    die("<div style='font-family:sans-serif;padding:40px;text-align:center;'>
            <h2 style='color:#dc2626;'>Database Connection Error</h2>
            <p style='color:#64748b;'>Could not connect to the database. Please check your configuration.</p>
            <small style='color:#94a3b8;'>(" . ($is_live ? 'Live' : 'Local') . " environment)</small>
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
