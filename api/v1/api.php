<?php
// api/v1/api.php
require_once 'init.php';

$action = $_GET['action'] ?? '';

switch($action) {
    case 'config':
        $config = [
            'site_name' => get_setting('site_name', 'NewsPortal'),
            'site_logo' => get_setting('site_logo') ? BASE_URL . "assets/images/" . get_setting('site_logo') : null,
            'theme_color' => get_setting('theme_color', '#ff3c00'),
            'live_youtube_enabled' => get_setting('live_youtube_enabled', '0'),
            'live_youtube_url' => get_setting('live_youtube_url', ''),
            'breaking_news_enabled' => get_setting('breaking_news_enabled', 'yes'),
            'ebook_magazine_enabled' => get_setting('ebook_magazine_enabled', 'yes'),
            'social' => [
                'facebook' => get_setting('facebook_url'),
                'twitter' => get_setting('twitter_url'),
                'instagram' => get_setting('instagram_url'),
                'youtube' => get_setting('youtube_url'),
                'whatsapp' => get_setting('whatsapp_number'),
            ]
        ];
        api_response(true, "Config fetched", $config);
        break;

    case 'categories':
        $stmt = $pdo->query("SELECT id, name, slug, icon, color FROM categories WHERE status = 'active' ORDER BY name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        api_response(true, "Categories fetched", $categories);
        break;

    case 'posts':
        $cat_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $query = "SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.published_at, p.views, c.name as category_name, c.color as category_color 
                  FROM posts p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.status = 'published' AND p.published_at <= NOW()";
        
        $params = [];
        if($cat_id > 0) {
            $query .= " AND p.category_id = ?";
            $params[] = $cat_id;
        }

        $query .= " ORDER BY p.published_at DESC LIMIT $limit OFFSET $offset";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Map image URLs
        foreach($posts as &$p) {
            $p['featured_image'] = $p['featured_image'] ? BASE_URL . "assets/images/" . $p['featured_image'] : null;
        }

        api_response(true, "Posts fetched", $posts);
        break;

    case 'post_detail':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if(!$id) api_response(false, "Invalid ID");

        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if($post) {
            $post['featured_image'] = $post['featured_image'] ? BASE_URL . "assets/images/" . $post['featured_image'] : null;
            // Record view
            $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?")->execute([$id]);
            api_response(true, "Post details fetched", $post);
        } else {
            api_response(false, "Post not found");
        }
        break;

    case 'breaking':
        $stmt = $pdo->query("SELECT id, title, slug FROM posts WHERE status = 'published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 5");
        $breaking = $stmt->fetchAll(PDO::FETCH_ASSOC);
        api_response(true, "Breaking news fetched", $breaking);
        break;

    case 'videos':
        $stmt = $pdo->query("SELECT id, title, slug, video_url, featured_image FROM posts WHERE video_url IS NOT NULL AND status = 'published' ORDER BY published_at DESC LIMIT 10");
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($videos as &$v) {
            $v['featured_image'] = $v['featured_image'] ? BASE_URL . "assets/images/" . $v['featured_image'] : null;
        }
        api_response(true, "Videos fetched", $videos);
        break;

    case 'digital_media':
        if(get_setting('ebook_magazine_enabled', 'yes') == 'no') api_response(false, "Module disabled");

        $epapers = $pdo->query("SELECT id, title, paper_date, thumbnail, file_path FROM epapers ORDER BY paper_date DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        foreach($epapers as &$e) {
            $e['thumbnail'] = $e['thumbnail'] ? BASE_URL . "assets/epapers/" . $e['thumbnail'] : null;
            $e['file_url'] = BASE_URL . "assets/epapers/" . $e['file_path'];
        }

        $magazines = $pdo->query("SELECT id, title, issue_month, cover_image, file_path FROM magazines WHERE status = 'published' ORDER BY issue_month DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        foreach($magazines as &$m) {
            $m['cover_image'] = $m['cover_image'] ? BASE_URL . "assets/magazines/" . $m['cover_image'] : null;
            $m['file_url'] = BASE_URL . "assets/magazines/" . $m['file_path'];
        }

        api_response(true, "Digital media fetched", ['epapers' => $epapers, 'magazines' => $magazines]);
        break;

    case 'login':
        if($_SERVER['REQUEST_METHOD'] !== 'POST') api_response(false, "Method not allowed");
        
        $data = json_decode(file_get_contents("php://input"), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if($user && password_verify($password, $user['password'])) {
            unset($user['password']); // Don't return password
            $user['profile_image'] = BASE_URL . "assets/images/" . ($user['profile_image'] ?: 'default_avatar.png');
            api_response(true, "Login successful", $user);
        } else {
            api_response(false, "Invalid credentials");
        }
        break;

    case 'search':
        $q = $_GET['q'] ?? '';
        if(empty($q)) api_response(false, "Query empty");

        $stmt = $pdo->prepare("SELECT id, title, slug, featured_image, published_at FROM posts WHERE title LIKE ? AND status = 'published' ORDER BY published_at DESC LIMIT 20");
        $stmt->execute(["%$q%"]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($posts as &$p) {
            $p['featured_image'] = $p['featured_image'] ? BASE_URL . "assets/images/" . $p['featured_image'] : null;
        }
        api_response(true, "Search results", $posts);
        break;

    case 'register_push':
        $data = json_decode(file_get_contents("php://input"), true);
        $token = $data['token'] ?? '';
        $platform = $data['platform'] ?? 'ios';
        if(empty($token)) api_response(false, "Token missing");
        
        // Logic to save token to a `device_tokens` table would go here
        api_response(true, "Device registered successfully for push notifications");
        break;

    default:
        api_response(false, "Action not found");
}
