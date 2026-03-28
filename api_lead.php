<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$type = trim($_POST['type'] ?? 'magazine');
$title = trim($_POST['title'] ?? '');
$id = (int)($_POST['id'] ?? 0);

if (!$name || !$phone) {
    echo json_encode(['success' => false, 'message' => 'Name and phone are required']);
    exit;
}

// Save to feedback table
$subject = ($type === 'magazine') ? 'Magazine Download Lead' : 'E-Paper Download Lead';
$message = "Lead for Title: $title (ID: $id)";

try {
    $stmt = $pdo->prepare("INSERT INTO feedback (name, email, phone, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, 'new', NOW())");
    $stmt->execute([$name, 'lead@offerplant.com', $phone, $subject, $message]);
    
    // Also increment download count
    if ($type === 'magazine') {
        $pdo->prepare("UPDATE magazines SET downloads = downloads + 1 WHERE id = ?")->execute([$id]);
    } else {
        $pdo->prepare("UPDATE epapers SET downloads = downloads + 1 WHERE id = ?")->execute([$id]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
