<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $post_id = (int)$_POST['post_id'];

    // Check if already bookmarked
    $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $bookmark = $stmt->fetch();

    if ($bookmark) {
        // Remove bookmark
        $pdo->prepare("DELETE FROM bookmarks WHERE id = ?")->execute([$bookmark['id']]);
        echo json_encode(['status' => 'removed']);
    } else {
        // Add bookmark
        $pdo->prepare("INSERT INTO bookmarks (user_id, post_id) VALUES (?, ?)")->execute([$user_id, $post_id]);
        log_activity($pdo, $user_id, $post_id, 'bookmark');
        echo json_encode(['status' => 'added']);
    }
}
?>
