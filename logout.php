<?php
require_once __DIR__ . '/includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Unset all session variables in memory
$_SESSION = [];

// 2. Erase the session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"] ?? '/',
        $params["domain"] ?? '',
        $params["secure"] ?? false,
        $params["httponly"] ?? true
    );
}

// 3. Destroy the session storage on server
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// 4. Prevent browser caching of logout redirect
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Location: " . BASE_URL . "login.php");
exit();
?>
