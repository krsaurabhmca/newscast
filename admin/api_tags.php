<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode([]);
    exit();
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    // Return most popular tags if no query
    $stmt = $pdo->query("SELECT name FROM tags ORDER BY id DESC LIMIT 10");
} else {
    $stmt = $pdo->prepare("SELECT name FROM tags WHERE name LIKE ? LIMIT 10");
    $stmt->execute(['%' . $query . '%']);
}

$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($tags);
?>
