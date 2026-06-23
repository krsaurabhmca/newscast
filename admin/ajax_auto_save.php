<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data provided.']);
    exit();
}

$post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;
$title = clean($data['title'] ?? '');
$content = $data['content'] ?? '';
$excerpt = clean($data['excerpt'] ?? '');
$slug = !empty($data['slug']) ? create_slug($data['slug']) : create_slug($title);
$video_url = clean($data['video_url'] ?? '');
$external_link = clean($data['external_link'] ?? '');
$meta_description = clean($data['meta_description'] ?? '');
$published_at = !empty($data['published_at']) ? $data['published_at'] : date('Y-m-d H:i:s');
$is_featured = isset($data['is_featured']) && $data['is_featured'] ? 1 : 0;
$category_ids = isset($data['category_ids']) && is_array($data['category_ids']) ? $data['category_ids'] : [];
$tags = clean($data['tags'] ?? '');
$ai_image_url = clean($data['ai_image_url'] ?? '');

$user_id = $_SESSION['user_id'];

// Auto Ad Logic
$external_type = 'none';
$external_label = 'none';
if (!empty($external_link)) {
    $external_label = 'Ad';
    if (filter_var($external_link, FILTER_VALIDATE_URL)) {
        $external_type = 'url';
    } elseif (preg_match('/^[0-9+\(\)#\s-]+$/', $external_link)) {
        $external_type = 'call';
    } else {
        $external_type = 'url';
    }
}

if (empty($title)) {
    $title = 'Auto Saved Draft';
}
if (empty($slug) || $slug === '-') {
    $slug = 'draft-' . time() . '-' . rand(1000, 9999);
}

try {
    $pdo->beginTransaction();

    if ($post_id > 0) {
        // Update existing post
        // Verify post ownership or if admin
        $stmt_check = $pdo->prepare("SELECT user_id, featured_image FROM posts WHERE id = ?");
        $stmt_check->execute([$post_id]);
        $existing = $stmt_check->fetch();
        
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Post not found.']);
            $pdo->rollBack();
            exit();
        }
        
        if ($existing['user_id'] != $user_id && !is_admin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            $pdo->rollBack();
            exit();
        }

        // We preserve featured_image if already set, or handle ai_image_url download
        $featured_image = $existing['featured_image'];
        if (!empty($ai_image_url) && strpos($featured_image, 'post_ai_') === false) {
            $image_data = @file_get_contents($ai_image_url);
            if ($image_data) {
                $new_filename = uniqid("post_ai_") . '_' . time() . '.jpg';
                $destination = "../assets/images/posts/" . $new_filename;
                if (file_put_contents($destination, $image_data)) {
                    if ($featured_image && file_exists("../assets/images/posts/" . $featured_image)) {
                        @unlink("../assets/images/posts/" . $featured_image);
                    }
                    $featured_image = $new_filename;
                }
            }
        }

        // Check if slug is already taken by another post
        $stmt_slug_check = $pdo->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
        $stmt_slug_check->execute([$slug, $post_id]);
        if ($stmt_slug_check->fetch()) {
            $slug .= '-' . rand(10, 99);
        }

        $stmt = $pdo->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, excerpt = ?, featured_image = ?, video_url = ?, external_link = ?, external_type = ?, external_label = ?, status = 'draft', is_featured = ?, meta_description = ?, published_at = ? WHERE id = ?");
        $stmt->execute([$title, $slug, $content, $excerpt, $featured_image, $video_url, $external_link, $external_type, $external_label, $is_featured, $meta_description, $published_at, $post_id]);
    } else {
        // Insert new post
        $featured_image = '';
        if (!empty($ai_image_url)) {
            $image_data = @file_get_contents($ai_image_url);
            if ($image_data) {
                $new_filename = uniqid("post_ai_") . '_' . time() . '.jpg';
                $destination = "../assets/images/posts/" . $new_filename;
                if (file_put_contents($destination, $image_data)) {
                    $featured_image = $new_filename;
                }
            }
        }

        // Check if slug is taken
        $stmt_slug_check = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
        $stmt_slug_check->execute([$slug]);
        if ($stmt_slug_check->fetch()) {
            $slug .= '-' . rand(10, 99);
        }

        $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, slug, content, excerpt, featured_image, video_url, external_link, external_type, external_label, status, is_featured, meta_description, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?)");
        $stmt->execute([$user_id, $title, $slug, $content, $excerpt, $featured_image, $video_url, $external_link, $external_type, $external_label, $is_featured, $meta_description, $published_at]);
        $post_id = $pdo->lastInsertId();
    }

    // Sync categories
    $pdo->prepare("DELETE FROM post_categories WHERE post_id = ?")->execute([$post_id]);
    $stmt_cat = $pdo->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)");
    foreach ($category_ids as $cat_id) {
        $stmt_cat->execute([$post_id, (int)$cat_id]);
    }

    // Sync Tags
    $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$post_id]);
    if (!empty($tags)) {
        $tags_input = explode(',', $tags);
        $stmt_tag_insert = $pdo->prepare("INSERT IGNORE INTO tags (name, slug) VALUES (?, ?)");
        $stmt_tag_get = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt_tag_link = $pdo->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)");

        foreach ($tags_input as $tag_name) {
            $tag_name = trim($tag_name);
            if (empty($tag_name)) continue;

            $tag_slug = create_slug($tag_name);
            $stmt_tag_insert->execute([$tag_name, $tag_slug]);

            $stmt_tag_get->execute([$tag_name]);
            $tag_id = $stmt_tag_get->fetchColumn();

            if ($tag_id) {
                $stmt_tag_link->execute([$post_id, $tag_id]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'post_id' => $post_id, 'message' => 'Draft auto-saved successfully.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
