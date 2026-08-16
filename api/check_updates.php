<?php
if (!file_exists('../includes/config.php')) {
    exit;
}
require_once '../includes/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

try {
    $stmt = $pdo->query("SELECT MAX(published_at) as latest_time FROM posts WHERE status = 'published' AND published_at <= NOW()");
    $result = $stmt->fetch();
    echo json_encode(['status' => 'success', 'latest_timestamp' => $result['latest_time']]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
