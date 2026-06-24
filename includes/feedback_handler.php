<?php
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $subject = clean($_POST['subject'] ?? '');
    $message = clean($_POST['message'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0) ?: null;

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit;
    }

    $featured_image = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
        $uploaded = upload_and_optimize_image($_FILES['featured_image'], '../assets/images/posts/', 'user_sub_', 1000, 85);
        if ($uploaded) {
            $featured_image = $uploaded;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO feedback (name, email, phone, category_id, subject, message, featured_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$name, $email, $phone, $category_id, $subject, $message, $featured_image]);
        echo json_encode(['status' => 'success', 'message' => 'Thank you! Your news story has been submitted for approval.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Something went wrong while saving your submission. Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
exit();
