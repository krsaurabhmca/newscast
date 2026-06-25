<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['media_file'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$file = $_FILES['media_file'];
if ($file['error'] !== 0) {
    echo json_encode(['success' => false, 'message' => 'Upload error code: ' . $file['error']]);
    exit;
}

$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}

$upload_dir = '../assets/images/media/';
$original_name = clean($file['name']);
$file_size = $file['size'];

// Upload and optimize (saves as WebP usually)
$filename = upload_and_optimize_image($file, $upload_dir, 'media_', 1600, 80);

if ($filename) {
    // Get actual dimensions
    $path = $upload_dir . $filename;
    $width = 0;
    $height = 0;
    if (file_exists($path)) {
        $info = @getimagesize($path);
        if ($info) {
            $width = $info[0];
            $height = $info[1];
        }
        $file_size = filesize($path); // get compressed size
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO media_library (filename, original_name, file_size, width, height, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$filename, $original_name, $file_size, $width, $height, $_SESSION['user_id']]);
        
        $id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $id,
                'filename' => $filename,
                'original_name' => $original_name,
                'url' => BASE_URL . 'assets/images/media/' . $filename
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to process image']);
}
