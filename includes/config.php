<?php
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    // Local Development
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'newsportal_db');
    define('BASE_URL', 'http://localhost/news/');
}
else {
    // Live Server: newscast.offerplant.com
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u960515621_newscast');
    define('DB_PASS', '@News_2001');
    define('DB_NAME', 'u960515621_newscast');
    define('BASE_URL', 'https://newscast.offerplant.com/');
}

define('SITE_NAME', 'NewsCast');

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
}
catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$settings = [];
try {
    $stmt_set = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt_set->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
catch (Exception $e) {
}

function get_setting($key, $default = '')
{
    global $settings;
    return isset($settings[$key]) ? $settings[$key] : $default;
}

define('SITE_NAME_DYNAMIC', get_setting('site_name') ?: SITE_NAME);
?>