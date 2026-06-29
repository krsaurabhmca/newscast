<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['cropped_image']) || !isset($_POST['original_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$file = $_FILES['cropped_image'];
$original_id = (int)$_POST['original_id'];

if ($file['error'] !== 0) {
    echo json_encode(['success' => false, 'message' => 'Upload error code: ' . $file['error']]);
    exit;
}

$upload_dir = '../assets/images/media/';

// Get original name from DB
try {
    $stmt = $pdo->prepare("SELECT original_name FROM media_library WHERE id = ?");
    $stmt->execute([$original_id]);
    $orig = $stmt->fetch();
    $original_name = $orig ? 'cropped_' . $orig['original_name'] : 'cropped_' . time() . '.webp';
} catch (Exception $e) {
    $original_name = 'cropped_' . time() . '.webp';
}

// Generate new unique filename
$ext = 'webp';
$filename = 'media_cropped_' . uniqid() . '_' . time() . '.' . $ext;
$target_path = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    $width = 0;
    $height = 0;
    $file_size = filesize($target_path);
    
    if (file_exists($target_path)) {
        $info = @getimagesize($target_path);
        if ($info) {
            $width = $info[0];
            $height = $info[1];
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO media_library (filename, original_name, file_size, width, height, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$filename, $original_name, $file_size, $width, $height, $_SESSION['user_id']]);
        
        $new_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $new_id,
                'filename' => $filename,
                'original_name' => $original_name,
                'url' => BASE_URL . 'assets/images/media/' . $filename
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save cropped image file']);
}
