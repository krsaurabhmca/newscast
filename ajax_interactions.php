<?php
header('Content-Type: application/json');
require_once 'includes/config.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = clean($_POST['action'] ?? '');
$postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

if ($postId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID.']);
    exit;
}

// 1. Handle Ratings (Likes/Dislikes)
if ($action === 'rate') {
    if (get_setting('likes_dislikes_enabled', 'no') !== 'yes') {
        echo json_encode(['success' => false, 'message' => 'Ratings are disabled.']);
        exit;
    }

    $type = clean($_POST['type'] ?? '');
    if ($type !== 'like' && $type !== 'dislike') {
        echo json_encode(['success' => false, 'message' => 'Invalid rating type.']);
        exit;
    }

    $cookieName = 'rated_post_' . $postId;
    if (isset($_COOKIE[$cookieName])) {
        echo json_encode(['success' => false, 'message' => 'You have already rated this article.']);
        exit;
    }

    try {
        if ($type === 'like') {
            $stmt = $pdo->prepare("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE posts SET dislikes_count = dislikes_count + 1 WHERE id = ?");
        }
        $stmt->execute([$postId]);

        // Set cookie for 30 days
        setcookie($cookieName, '1', time() + (86400 * 30), '/');

        // Fetch updated count
        $stmt_count = $pdo->prepare("SELECT likes_count, dislikes_count FROM posts WHERE id = ?");
        $stmt_count->execute([$postId]);
        $post = $stmt_count->fetch();
        $newCount = ($type === 'like') ? $post['likes_count'] : $post['dislikes_count'];

        echo json_encode(['success' => true, 'count' => $newCount]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
    }
    exit;
}

// 2. Handle Comments Submission
if ($action === 'comment') {
    if (get_setting('comments_enabled', 'no') !== 'yes') {
        echo json_encode(['success' => false, 'message' => 'Comments are disabled.']);
        exit;
    }

    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $commentText = clean($_POST['comment'] ?? '');

    if (empty($name) || empty($email) || empty($commentText)) {
        echo json_encode(['success' => false, 'message' => 'All comment fields are required.']);
        exit;
    }

    $moderation = get_setting('comments_moderation_enabled', 'yes') === 'yes';
    $status = $moderation ? 'pending' : 'approved';

    try {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_name, user_email, comment_text, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$postId, $name, $email, $commentText, $status]);

        echo json_encode([
            'success' => true,
            'message' => $moderation ? 'Your comment has been submitted and is awaiting approval by an admin.' : 'Your comment has been posted successfully!',
            'auto_approved' => !$moderation
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
    }
    exit;
}

// 3. Handle Article View Tracking via JavaScript (bypasses Cloudflare/CDN caching)
if ($action === 'view') {
    record_post_view($pdo, $postId);
    $uniqueCount = get_post_unique_views($pdo, $postId);

    $stmt_views = $pdo->prepare("SELECT views FROM posts WHERE id = ?");
    $stmt_views->execute([$postId]);
    $totalViews = (int) $stmt_views->fetchColumn();

    echo json_encode([
        'success' => true,
        'views' => $totalViews,
        'unique_views' => $uniqueCount
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action request.']);
exit;
