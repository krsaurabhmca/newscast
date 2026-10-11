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
                'whatsapp_channel' => get_setting('whatsapp_channel'),
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
            $p['featured_image'] = get_post_thumbnail($p['featured_image']);
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
            $post['featured_image'] = get_post_thumbnail($post['featured_image']);
            
            // Related posts — use same category if available, else fall back to latest
            if ($post['category_id']) {
                $stmt_related = $pdo->prepare("SELECT id, title, featured_image, published_at, views FROM posts WHERE category_id = ? AND id != ? AND status = 'published' ORDER BY published_at DESC LIMIT 4");
                $stmt_related->execute([$post['category_id'], $id]);
            } else {
                $stmt_related = $pdo->prepare("SELECT id, title, featured_image, published_at, views FROM posts WHERE id != ? AND status = 'published' ORDER BY published_at DESC LIMIT 4");
                $stmt_related->execute([$id]);
            }
            $related = $stmt_related->fetchAll(PDO::FETCH_ASSOC);
            foreach($related as &$r) {
                $r['featured_image'] = get_post_thumbnail($r['featured_image']);
            }
            $post['related'] = $related;

            // Record post view and increment in response
            if (record_post_view($pdo, $id)) {
                $post['views']++;
            }
            $post['unique_views'] = get_post_unique_views($pdo, $id);
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
        $stmt = $pdo->query("SELECT id, title, slug, video_url, featured_image, views FROM posts WHERE video_url IS NOT NULL AND status = 'published' ORDER BY published_at DESC LIMIT 10");
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($videos as &$v) {
            $v['featured_image'] = get_post_thumbnail($v['featured_image']);
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

    case 'ping':
    case 'admin_ping':
        api_response(true, "Portal API reachable", [
            'site_name' => get_setting('site_name', 'NewsPortal'),
            'site_logo' => get_setting('site_logo') ? BASE_URL . "assets/images/" . get_setting('site_logo') : null,
            'theme_color' => get_setting('theme_color', '#dc2626'),
            'version' => '1.0.0',
            'base_url' => BASE_URL,
            'server_time' => date('Y-m-d H:i:s')
        ]);
        break;

    case 'login':
    case 'admin_login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_response(false, "Method not allowed. Use POST.");
        }
        
        $data = get_json_payload();
        $identifier = trim($data['username'] ?? ($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            api_response(false, "Please provide username/email and password");
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $user['profile_image'] = get_profile_image($user['profile_image'] ?? '', '');
            
            // Create a simple token session identifier
            $token = bin2hex(random_bytes(16));
            $user['token'] = $token;

            $portal_info = [
                'site_name' => get_setting('site_name', 'NewsPortal'),
                'site_logo' => get_setting('site_logo') ? BASE_URL . "assets/images/" . get_setting('site_logo') : null,
                'theme_color' => get_setting('theme_color', '#dc2626'),
                'base_url' => BASE_URL
            ];

            api_response(true, "Login successful", [
                'user' => $user,
                'portal' => $portal_info
            ]);
        } else {
            api_response(false, "Invalid username or password");
        }
        break;

    case 'change_password':
    case 'admin_change_password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_response(false, "Method not allowed. Use POST.");
        }
        
        $data = get_json_payload();
        $user_id = (int)($data['user_id'] ?? 0);
        $username = trim($data['username'] ?? '');
        $current_password = $data['current_password'] ?? '';
        $new_password = $data['new_password'] ?? '';
        $confirm_password = $data['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password)) {
            api_response(false, "Current password and new password are required.");
        }

        if (strlen($new_password) < 6) {
            api_response(false, "New password must be at least 6 characters long.");
        }

        if (!empty($confirm_password) && $new_password !== $confirm_password) {
            api_response(false, "New password and confirmation do not match.");
        }

        if ($user_id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$user_id]);
        } elseif (!empty($username)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $username]);
        } else {
            api_response(false, "User identification is required.");
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            api_response(false, "User account not found.");
        }

        if (!password_verify($current_password, $user['password'])) {
            api_response(false, "Current password is incorrect.");
        }

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$hashed, $user['id']]);

        api_response(true, "Password changed successfully.");
        break;

    // ── ADMIN: Dashboard Stats ──────────────────────────────────────────────
    case 'admin_dashboard':
        $total_posts = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        $published_posts = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
        $draft_posts = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status='draft'")->fetchColumn();
        $today_posts = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $total_views = (int)$pdo->query("SELECT COALESCE(SUM(views), 0) FROM posts")->fetchColumn();
        $total_categories = (int)$pdo->query("SELECT COUNT(*) FROM categories WHERE status='active'")->fetchColumn();
        $total_users = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        
        $unread_feedback = 0;
        try {
            $unread_feedback = (int)$pdo->query("SELECT COUNT(*) FROM feedback WHERE status='new'")->fetchColumn();
        } catch (Exception $e) {}

        // Live Visitors (active in last 5 minutes)
        $live_visitors = 0;
        try {
            $live_visitors = (int)$pdo->query("SELECT COUNT(DISTINCT ip_address) FROM post_views_logs WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetchColumn();
        } catch (Exception $e) {
            $live_visitors = 0;
        }

        // Ad Clicks Breakdown by Period (Today, 7 days, 30 days, Lifetime)
        $ad_clicks = [
            'today' => 0,
            'seven_days' => 0,
            'thirty_days' => 0,
            'lifetime' => 0
        ];
        try {
            $ad_clicks['today'] = (int)$pdo->query("SELECT COUNT(*) FROM ad_click_logs WHERE DATE(clicked_at) = CURDATE()")->fetchColumn();
            $ad_clicks['seven_days'] = (int)$pdo->query("SELECT COUNT(*) FROM ad_click_logs WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
            $ad_clicks['thirty_days'] = (int)$pdo->query("SELECT COUNT(*) FROM ad_click_logs WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
            $ad_clicks['lifetime'] = (int)$pdo->query("SELECT COUNT(*) FROM ad_click_logs")->fetchColumn();
        } catch (Exception $e) {
            try {
                $ad_clicks['lifetime'] = (int)$pdo->query("SELECT COALESCE(SUM(clicks), 0) FROM ads")->fetchColumn();
            } catch (Exception $e2) {}
        }

        // Views Breakdown by Period
        $views_by_period = [
            'today' => 0,
            'seven_days' => 0,
            'thirty_days' => 0,
            'lifetime' => $total_views
        ];
        try {
            $views_by_period['today'] = (int)$pdo->query("SELECT COUNT(*) FROM post_views_logs WHERE DATE(viewed_at) = CURDATE()")->fetchColumn();
            $views_by_period['seven_days'] = (int)$pdo->query("SELECT COUNT(*) FROM post_views_logs WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
            $views_by_period['thirty_days'] = (int)$pdo->query("SELECT COUNT(*) FROM post_views_logs WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
        } catch (Exception $e) {}

        // Posts Published by Period
        $posts_by_period = [
            'today' => $today_posts,
            'seven_days' => 0,
            'thirty_days' => 0,
            'lifetime' => $total_posts
        ];
        try {
            $posts_by_period['seven_days'] = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
            $posts_by_period['thirty_days'] = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
        } catch (Exception $e) {}

        // Recent Posts
        $recent_stmt = $pdo->query("
            SELECT p.id, p.title, p.status, p.created_at, p.published_at, p.views, p.slug, p.featured_image, p.is_featured,
                   p.content, p.excerpt, p.video_url,
                   c.name as category_name, c.color as category_color
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.id DESC LIMIT 6
        ");
        $recent_posts = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($recent_posts as &$rp) {
            $rp['featured_image'] = get_post_thumbnail($rp['featured_image']);
        }

        // Live stream & quick settings
        $live_enabled = get_setting('live_youtube_enabled', '0') === '1';
        $live_url = get_setting('live_youtube_url', '');
        $breaking_enabled = get_setting('breaking_news_enabled', 'yes') === 'yes';

        api_response(true, "Admin dashboard fetched", [
            'stats' => [
                'total_posts' => $total_posts,
                'published_posts' => $published_posts,
                'draft_posts' => $draft_posts,
                'today_posts' => $today_posts,
                'total_views' => $total_views,
                'total_categories' => $total_categories,
                'total_users' => $total_users,
                'unread_feedback' => $unread_feedback,
                'live_visitors' => $live_visitors,
                'ad_clicks' => $ad_clicks,
                'views_by_period' => $views_by_period,
                'posts_by_period' => $posts_by_period
            ],
            'live_stream' => [
                'enabled' => $live_enabled,
                'url' => $live_url
            ],
            'breaking_news_enabled' => $breaking_enabled,
            'recent_posts' => $recent_posts
        ]);
        break;

    // ── ADMIN: Post Management ──────────────────────────────────────────────
    case 'admin_posts':
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(50, (int)($_GET['limit'] ?? 15)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? 'all');
        $cat_id = (int)($_GET['category_id'] ?? 0);

        $where = ["1=1"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(p.title LIKE ? OR p.excerpt LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($status === 'published' || $status === 'draft') {
            $where[] = "p.status = ?";
            $params[] = $status;
        }

        if ($cat_id > 0) {
            $where[] = "(p.category_id = ? OR pc.category_id = ?)";
            $params[] = $cat_id;
            $params[] = $cat_id;
        }

        $whereClause = implode(" AND ", $where);

        // Count total
        $countSql = "SELECT COUNT(DISTINCT p.id) FROM posts p LEFT JOIN post_categories pc ON p.id = pc.post_id WHERE $whereClause";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Fetch posts
        $sql = "
            SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.video_url, p.featured_image, p.status, p.views, 
                   p.is_featured, p.published_at, p.created_at, p.category_id,
                   c.name as category_name, c.color as category_color,
                   u.username as author_name
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN post_categories pc ON p.id = pc.post_id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE $whereClause
            GROUP BY p.id
            ORDER BY p.id DESC
            LIMIT $limit OFFSET $offset
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($posts as &$post) {
            $post['featured_image'] = get_post_thumbnail($post['featured_image']);
        }

        api_response(true, "Admin posts fetched", $posts, [
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
        break;

    case 'admin_post_detail':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) api_response(false, "Post ID required");

        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name, u.username as author_name
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        $raw_thumb = $post['featured_image'];
        $resolved_thumb = get_post_thumbnail($raw_thumb);
        $post['featured_image_url'] = $resolved_thumb;
        $post['featured_image'] = $resolved_thumb ?: $raw_thumb;

        // Fetch associated category IDs
        $catStmt = $pdo->prepare("SELECT category_id FROM post_categories WHERE post_id = ?");
        $catStmt->execute([$id]);
        $cats = $catStmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($cats) && $post['category_id']) {
            $cats = [(int)$post['category_id']];
        }
        $post['category_ids'] = array_map('intval', $cats);

        api_response(true, "Post detail fetched", $post);
        break;

    case 'admin_save_post':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_response(false, "Method not allowed");

        $data = get_json_payload();
        $id = (int)($data['id'] ?? 0);
        $title = trim($data['title'] ?? '');
        $content = $data['content'] ?? '';
        $excerpt = trim($data['excerpt'] ?? '');
        $status = in_array($data['status'] ?? '', ['published', 'draft']) ? $data['status'] : 'draft';
        $category_id = (int)($data['category_id'] ?? 0);
        $category_ids = $data['category_ids'] ?? [];
        if (!is_array($category_ids)) {
            $category_ids = $category_id ? [$category_id] : [];
        }
        if (empty($category_id) && !empty($category_ids)) {
            $category_id = (int)$category_ids[0];
        }
        $featured_image = trim($data['featured_image'] ?? '');
        if (!empty($featured_image)) {
            if (preg_match('#assets/images/media/([^/\s\?\#]+)#i', $featured_image, $m)) {
                $featured_image = 'media/' . $m[1];
            } elseif (preg_match('#assets/images/posts/([^/\s\?\#]+)#i', $featured_image, $m)) {
                $featured_image = $m[1];
            } elseif (strpos($featured_image, 'media_') === 0) {
                $featured_image = 'media/' . $featured_image;
            }
        }
        $video_url = trim($data['video_url'] ?? '');
        $external_link = trim($data['external_link'] ?? '');
        $is_featured = !empty($data['is_featured']) ? 1 : 0;
        $meta_description = trim($data['meta_description'] ?? '');
        $user_id = (int)($data['user_id'] ?? 1);

        if (empty($title)) {
            api_response(false, "Title is required");
        }

        // Auto slug
        $custom_slug = trim($data['slug'] ?? '');
        $slug = !empty($custom_slug) ? create_slug($custom_slug) : create_slug($title);
        if (empty($slug)) $slug = 'post-' . time();

        // Ensure slug uniqueness
        $slug_check = $pdo->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
        $slug_check->execute([$slug, $id]);
        if ($slug_check->fetch()) {
            $slug .= '-' . rand(100, 999);
        }

        // Auto excerpt if blank
        if (empty($excerpt) && !empty($content)) {
            $clean_body = strip_tags($content);
            $excerpt = mb_substr($clean_body, 0, 160) . (mb_strlen($clean_body) > 160 ? '...' : '');
        }

        $now = date('Y-m-d H:i:s');
        $published_at = !empty($data['published_at']) ? $data['published_at'] : ($status === 'published' ? $now : null);

        // Auto external type
        $external_type = 'none';
        $external_label = 'none';
        if (!empty($external_link)) {
            $external_label = 'Ad';
            $external_type = 'url';
        }

        try {
            $pdo->beginTransaction();

            if ($id > 0) {
                // UPDATE
                $update_fields = [
                    "title = ?", "slug = ?", "content = ?", "excerpt = ?", "category_id = ?",
                    "status = ?", "is_featured = ?", "video_url = ?", "external_link = ?",
                    "meta_description = ?"
                ];
                $params = [
                    $title, $slug, $content, $excerpt, $category_id ?: null,
                    $status, $is_featured, $video_url ?: null, $external_link ?: null,
                    $meta_description ?: null
                ];

                if (array_key_exists('featured_image', $data)) {
                    $update_fields[] = "featured_image = ?";
                    $params[] = !empty($featured_image) ? $featured_image : null;
                }

                if ($published_at) {
                    $update_fields[] = "published_at = ?";
                    $params[] = $published_at;
                }

                $params[] = $id;
                $sql = "UPDATE posts SET " . implode(", ", $update_fields) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $saved_id = $id;
            } else {
                // INSERT
                $stmt = $pdo->prepare("
                    INSERT INTO posts 
                    (user_id, category_id, title, slug, content, excerpt, featured_image, video_url, external_link, external_type, external_label, status, is_featured, meta_description, published_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id, $category_id ?: null, $title, $slug, $content, $excerpt,
                    $featured_image ?: null, $video_url ?: null, $external_link ?: null,
                    $external_type, $external_label, $status, $is_featured,
                    $meta_description ?: null, $published_at
                ]);
                $saved_id = (int)$pdo->lastInsertId();
            }

            // Sync post_categories
            if (!empty($category_ids)) {
                $del = $pdo->prepare("DELETE FROM post_categories WHERE post_id = ?");
                $del->execute([$saved_id]);

                $cat_insert = $pdo->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)");
                foreach ($category_ids as $c_id) {
                    if ((int)$c_id > 0) {
                        $cat_insert->execute([$saved_id, (int)$c_id]);
                    }
                }
            }

            $pdo->commit();
            api_response(true, "Post saved successfully", [
                'id' => $saved_id,
                'title' => $title,
                'slug' => $slug,
                'status' => $status
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            api_response(false, "Failed to save post: " . $e->getMessage());
        }
        break;

    case 'admin_delete_post':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_response(false, "Method not allowed");
        $data = get_json_payload();
        $id = (int)($data['id'] ?? ($_GET['id'] ?? 0));
        if (!$id) api_response(false, "Post ID required");

        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM post_categories WHERE post_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
            $pdo->commit();
            api_response(true, "Post deleted successfully", ['id' => $id]);
        } catch (Exception $e) {
            $pdo->rollBack();
            api_response(false, "Failed to delete post: " . $e->getMessage());
        }
        break;

    case 'admin_toggle_post_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_response(false, "Method not allowed");
        $data = get_json_payload();
        $id = (int)($data['id'] ?? 0);
        if (!$id) api_response(false, "Post ID required");

        $stmt = $pdo->prepare("SELECT status FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        $curr = $stmt->fetchColumn();
        if ($curr === false) api_response(false, "Post not found");

        $new_status = isset($data['status']) ? $data['status'] : ($curr === 'published' ? 'draft' : 'published');
        $pub_at = ($new_status === 'published') ? date('Y-m-d H:i:s') : null;

        $update = $pdo->prepare("UPDATE posts SET status = ?, published_at = COALESCE(published_at, ?) WHERE id = ?");
        $update->execute([$new_status, $pub_at, $id]);

        api_response(true, "Status updated to " . $new_status, [
            'id' => $id,
            'status' => $new_status
        ]);
        break;

    // ── ADMIN: Category Management ──────────────────────────────────────────
    case 'admin_categories':
        $stmt = $pdo->query("
            SELECT c.*, COUNT(pc.post_id) as post_count
            FROM categories c
            LEFT JOIN post_categories pc ON c.id = pc.category_id
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        api_response(true, "Categories fetched", $categories);
        break;

    case 'admin_save_category':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_response(false, "Method not allowed");
        $data = get_json_payload();
        $id = (int)($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $icon = trim($data['icon'] ?? 'folder');
        $color = trim($data['color'] ?? '#6366f1');
        $status = in_array($data['status'] ?? '', ['active', 'disabled']) ? $data['status'] : 'active';
        $description = trim($data['description'] ?? '');

        if (empty($name)) api_response(false, "Category name is required");

        $slug = !empty($data['slug']) ? create_slug($data['slug']) : create_slug($name);

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE categories SET name=?, slug=?, description=?, icon=?, color=?, status=? WHERE id=?");
            $stmt->execute([$name, $slug, $description, $icon, $color, $status, $id]);
            $saved_id = $id;
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon, color, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $description, $icon, $color, $status]);
            $saved_id = (int)$pdo->lastInsertId();
        }

        api_response(true, "Category saved successfully", [
            'id' => $saved_id,
            'name' => $name,
            'slug' => $slug,
            'color' => $color
        ]);
        break;

    case 'admin_delete_category':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_response(false, "Method not allowed");
        $data = get_json_payload();
        $id = (int)($data['id'] ?? 0);
        if (!$id) api_response(false, "Category ID required");

        $pdo->prepare("DELETE FROM post_categories WHERE category_id = ?")->execute([$id]);
        $pdo->prepare("UPDATE posts SET category_id = NULL WHERE category_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);

        api_response(true, "Category deleted successfully", ['id' => $id]);
        break;

    // ── ADMIN: Media / Image Upload ─────────────────────────────────────────
    case 'admin_upload_image':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_response(false, "Method not allowed");

        $data = get_json_payload();
        $base64 = $data['image_base64'] ?? '';
        $upload_dir_posts = __DIR__ . '/../../assets/images/posts/';
        $upload_dir_media = __DIR__ . '/../../assets/images/media/';
        if (!is_dir($upload_dir_posts)) @mkdir($upload_dir_posts, 0777, true);
        if (!is_dir($upload_dir_media)) @mkdir($upload_dir_media, 0777, true);

        $filename = '';
        if (!empty($base64)) {
            // Base64 string decode
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
                $ext = strtolower($type[1]);
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $ext = 'jpg';
                }
            } else {
                $ext = 'jpg';
            }

            $img_data = base64_decode($base64);
            if ($img_data === false) api_response(false, "Base64 decode failed");

            $filename = 'post_app_' . uniqid() . '_' . time() . '.' . $ext;
            file_put_contents($upload_dir_posts . $filename, $img_data);
            @copy($upload_dir_posts . $filename, $upload_dir_media . $filename);
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $filename = 'post_app_' . uniqid() . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir_posts . $filename);
            @copy($upload_dir_posts . $filename, $upload_dir_media . $filename);
        } else {
            api_response(false, "No image provided (image_base64 or file upload required)");
        }

        $file_url = BASE_URL . "assets/images/posts/" . $filename;

        // Also record in media_library table
        try {
            $f_path = file_exists($upload_dir_posts . $filename) ? $upload_dir_posts . $filename : $upload_dir_media . $filename;
            $f_size = file_exists($f_path) ? filesize($f_path) : 0;
            $dim = @getimagesize($f_path);
            $width = $dim ? (int)$dim[0] : 0;
            $height = $dim ? (int)$dim[1] : 0;

            $user_id = null;
            try {
                $u_stmt = $pdo->query("SELECT id FROM users LIMIT 1");
                $first_user = $u_stmt ? $u_stmt->fetchColumn() : null;
                if ($first_user) $user_id = (int)$first_user;
            } catch (Exception $ue) {}

            $ins = $pdo->prepare("INSERT INTO media_library (filename, original_name, file_size, width, height, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->execute([$filename, $filename, $f_size, $width, $height, $user_id]);
        } catch (Exception $e) {}

        api_response(true, "Image uploaded successfully", [
            'file_name' => $filename,
            'file_url' => $file_url
        ]);
        break;

    case 'admin_media_list':
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(60, max(1, (int)($_GET['limit'] ?? 30)));
        $offset = ($page - 1) * $limit;

        // Auto-sync files from disk into media_library if not already recorded
        try {
            $disk_dirs = [
                __DIR__ . '/../../assets/images/media/',
                __DIR__ . '/../../assets/images/posts/'
            ];
            $existing_files_stmt = $pdo->query("SELECT filename FROM media_library");
            $known = $existing_files_stmt ? $existing_files_stmt->fetchAll(PDO::FETCH_COLUMN) : [];
            $known_set = array_flip($known);

            foreach ($disk_dirs as $d) {
                if (!is_dir($d)) continue;
                $scan = scandir($d);
                foreach ($scan as $f) {
                    if ($f === '.' || $f === '..' || is_dir($d . $f)) continue;
                    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) continue;
                    if (!isset($known_set[$f])) {
                        $sz = filesize($d . $f);
                        $dim = @getimagesize($d . $f);
                        $w = $dim ? (int)$dim[0] : 0;
                        $h = $dim ? (int)$dim[1] : 0;
                        $ins = $pdo->prepare("INSERT INTO media_library (filename, original_name, file_size, width, height, uploaded_by) VALUES (?, ?, ?, ?, ?, NULL)");
                        $ins->execute([$f, $f, $sz, $w, $h]);
                        $known_set[$f] = true;
                    }
                }
            }
        } catch (Exception $e) {}

        $items = [];
        $total = 0;

        try {
            $where = "1=1";
            $params = [];
            if (!empty($search)) {
                $where .= " AND (filename LIKE ? OR original_name LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            $c_stmt = $pdo->prepare("SELECT COUNT(*) FROM media_library WHERE $where");
            $c_stmt->execute($params);
            $total = (int)$c_stmt->fetchColumn();

            if ($total > 0) {
                $sql = "SELECT * FROM media_library WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($rows as $row) {
                    $sub_dir = file_exists(__DIR__ . '/../../assets/images/media/' . $row['filename']) ? 'media/' : 'posts/';
                    $items[] = [
                        'id' => (int)$row['id'],
                        'filename' => $row['filename'],
                        'original_name' => $row['original_name'] ?: $row['filename'],
                        'file_size' => (int)($row['file_size'] ?? 0),
                        'file_url' => BASE_URL . "assets/images/" . $sub_dir . $row['filename'],
                        'width' => (int)($row['width'] ?? 0),
                        'height' => (int)($row['height'] ?? 0),
                        'created_at' => $row['created_at'] ?? date('Y-m-d H:i:s')
                    ];
                }
            }
        } catch (PDOException $e) {}
        } catch (PDOException $e) {}

        // Fallback / complement from directory scan if media_library has few files
        if (empty($items)) {
            $dirs = [
                __DIR__ . '/../../assets/images/posts/' => BASE_URL . "assets/images/posts/",
                __DIR__ . '/../../assets/images/media/' => BASE_URL . "assets/images/media/"
            ];
            $all_files = [];
            foreach ($dirs as $d => $base) {
                if (is_dir($d)) {
                    $scanned = scandir($d);
                    foreach ($scanned as $f) {
                        if ($f === '.' || $f === '..' || is_dir($d . $f)) continue;
                        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'])) continue;
                        if (!empty($search) && stripos($f, $search) === false) continue;
                        $mtime = filemtime($d . $f);
                        $fsize = filesize($d . $f);
                        $all_files[] = [
                            'filename' => $f,
                            'url' => $base . $f,
                            'size' => $fsize,
                            'mtime' => $mtime
                        ];
                    }
                }
            }
            usort($all_files, function($a, $b) {
                return $b['mtime'] - $a['mtime'];
            });
            $total = count($all_files);
            $sliced = array_slice($all_files, $offset, $limit);
            $idx = $offset + 1;
            foreach ($sliced as $f) {
                $items[] = [
                    'id' => $idx++,
                    'filename' => $f['filename'],
                    'original_name' => $f['filename'],
                    'file_size' => $f['size'],
                    'file_url' => $f['url'],
                    'created_at' => date('Y-m-d H:i:s', $f['mtime'])
                ];
            }
        }

        api_response(true, "Media fetched", $items, [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => max(1, (int)ceil($total / $limit))
        ]);
        break;

    case 'admin_media_delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_response(false, "Method not allowed");
        $data = get_json_payload();
        $id = (int)($data['id'] ?? 0);
        $filename = trim($data['filename'] ?? '');

        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT filename FROM media_library WHERE id = ?");
                $stmt->execute([$id]);
                $db_file = $stmt->fetchColumn();
                if ($db_file) $filename = $db_file;
                $pdo->prepare("DELETE FROM media_library WHERE id = ?")->execute([$id]);
            } catch (PDOException $e) {}
        }

        if (!empty($filename)) {
            $p1 = __DIR__ . '/../../assets/images/media/' . basename($filename);
            $p2 = __DIR__ . '/../../assets/images/posts/' . basename($filename);
            if (file_exists($p1)) @unlink($p1);
            if (file_exists($p2)) @unlink($p2);
        }

        api_response(true, "Media deleted successfully");
        break;

    // ── ADMIN: Settings Management ──────────────────────────────────────────
    case 'admin_settings':
        $settings_keys = [
            'site_name', 'site_tagline', 'theme_color', 'site_logo',
            'live_youtube_enabled', 'live_youtube_url', 'live_stream_title',
            'breaking_news_enabled', 'contact_email', 'contact_phone',
            'address', 'facebook_url', 'twitter_url', 'instagram_url',
            'youtube_url', 'whatsapp_number'
        ];
        $result = [];
        foreach ($settings_keys as $k) {
            $result[$k] = get_setting($k, '');
        }
        api_response(true, "Settings fetched", $result);
        break;

    case 'admin_update_settings':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_response(false, "Method not allowed");
        $data = get_json_payload();
        if (empty($data)) api_response(false, "No settings provided to update");

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        foreach ($data as $key => $val) {
            if ($key === 'action') continue;
            $stmt->execute([$key, (string)$val, (string)$val]);
        }

        // Clear cache if exists
        $cache_dir = __DIR__ . '/../../cache';
        if (is_dir($cache_dir)) {
            array_map('unlink', glob("$cache_dir/*.json"));
        }

        api_response(true, "Settings updated successfully");
        break;

    case 'admin_toggle_live':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_response(false, "Method not allowed");
        $data = get_json_payload();
        $enabled = !empty($data['enabled']) ? '1' : '0';
        $url = trim($data['url'] ?? '');

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute(['live_youtube_enabled', $enabled, $enabled]);

        if (!empty($url)) {
            $stmt->execute(['live_youtube_url', $url, $url]);
        }

        api_response(true, "Live stream " . ($enabled === '1' ? 'enabled' : 'disabled'), [
            'enabled' => $enabled === '1',
            'url' => $url ?: get_setting('live_youtube_url')
        ]);
        break;

    // ── ADMIN: Feedback & Messages ──────────────────────────────────────────
    case 'admin_feedback':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = get_json_payload();
            $sub = $data['subaction'] ?? '';
            $f_id = (int)($data['id'] ?? 0);

            if ($sub === 'mark_read' && $f_id > 0) {
                $pdo->prepare("UPDATE feedback SET status = 'read' WHERE id = ?")->execute([$f_id]);
                api_response(true, "Feedback marked as read");
            } elseif ($sub === 'delete' && $f_id > 0) {
                $pdo->prepare("DELETE FROM feedback WHERE id = ?")->execute([$f_id]);
                api_response(true, "Feedback deleted");
            }
        }

        $items = $pdo->query("SELECT * FROM feedback ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
        api_response(true, "Feedback fetched", $items);
        break;

    // ── ADMIN: Polls Management ───────────────────────────────────────────────
    case 'admin_polls':
        $polls = $pdo->query("SELECT * FROM polls ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        $opt_stmt = $pdo->prepare("SELECT id, poll_id, option_text, votes_count FROM poll_options WHERE poll_id = ? ORDER BY id ASC");

        foreach ($polls as $poll) {
            $opt_stmt->execute([$poll['id']]);
            $options = $opt_stmt->fetchAll(PDO::FETCH_ASSOC);
            $total_votes = 0;
            foreach ($options as $o) {
                $total_votes += (int)$o['votes_count'];
            }
            foreach ($options as &$o) {
                $o['votes_count'] = (int)$o['votes_count'];
                $o['percentage'] = $total_votes > 0 ? round(($o['votes_count'] / $total_votes) * 100, 1) : 0;
            }
            $poll['total_votes'] = $total_votes;
            $poll['options'] = $options;
            $result[] = $poll;
        }
        api_response(true, "Polls fetched", $result);
        break;

    case 'admin_save_poll':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_response(false, "Method not allowed");
        $data = get_json_payload();
        $question = trim($data['question'] ?? '');
        $status = in_array($data['status'] ?? '', ['active', 'closed']) ? $data['status'] : 'active';
        $options = $data['options'] ?? [];
        $starts_at = !empty($data['starts_at']) ? $data['starts_at'] : null;
        $expires_at = !empty($data['expires_at']) ? $data['expires_at'] : null;

        if (empty($question)) {
            api_response(false, "Poll question is required");
        }

        $valid_options = [];
        if (is_array($options)) {
            foreach ($options as $opt) {
                $trimmed = trim(is_string($opt) ? $opt : ($opt['option_text'] ?? ''));
                if (!empty($trimmed)) {
                    $valid_options[] = $trimmed;
                }
            }
        }

        if (count($valid_options) < 2) {
            api_response(false, "Please provide at least 2 poll options");
        }

        try {
            // Generate unique slug
            $base_slug = create_slug($question);
            if (empty($base_slug)) $base_slug = 'poll';
            $slug = $base_slug . '-' . substr(md5(uniqid()), 0, 6);

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO polls (question, slug, status, starts_at, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$question, $slug, $status, $starts_at, $expires_at]);
            $poll_id = (int)$pdo->lastInsertId();

            $opt_stmt = $pdo->prepare("INSERT INTO poll_options (poll_id, option_text, votes_count) VALUES (?, ?, 0)");
            foreach ($valid_options as $opt_text) {
                $opt_stmt->execute([$poll_id, $opt_text]);
            }
            $pdo->commit();

            api_response(true, "Poll created successfully", ['id' => $poll_id, 'slug' => $slug]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            api_response(false, "Failed to create poll: " . $e->getMessage());
        }
        break;

    case 'admin_toggle_poll_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_response(false, "Method not allowed");
        $data = get_json_payload();
        $poll_id = (int)($data['id'] ?? 0);
        $status = ($data['status'] ?? '') === 'closed' ? 'closed' : 'active';
        if ($poll_id <= 0) api_response(false, "Invalid poll ID");

        $stmt = $pdo->prepare("UPDATE polls SET status = ? WHERE id = ?");
        $stmt->execute([$status, $poll_id]);
        api_response(true, "Poll status updated to " . $status);
        break;

    case 'admin_delete_poll':
        $data = get_json_payload();
        $poll_id = (int)($data['id'] ?? ($_GET['id'] ?? 0));
        if ($poll_id <= 0) api_response(false, "Invalid poll ID");

        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM poll_votes WHERE poll_id = ?")->execute([$poll_id]);
            $pdo->prepare("DELETE FROM poll_options WHERE poll_id = ?")->execute([$poll_id]);
            $pdo->prepare("DELETE FROM polls WHERE id = ?")->execute([$poll_id]);
            $pdo->commit();
            api_response(true, "Poll deleted successfully");
        } catch (PDOException $e) {
            $pdo->rollBack();
            api_response(false, "Failed to delete poll: " . $e->getMessage());
        }
        break;

    case 'search':
        $q = $_GET['q'] ?? '';
        if(empty($q)) api_response(false, "Query empty");

        $stmt = $pdo->prepare("SELECT id, title, slug, featured_image, published_at, views FROM posts WHERE title LIKE ? AND status = 'published' ORDER BY published_at DESC LIMIT 20");
        $stmt->execute(["%$q%"]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($posts as &$p) {
            $p['featured_image'] = get_post_thumbnail($p['featured_image']);
        }
        api_response(true, "Search results", $posts);
        break;

    case 'ads':
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT id, name, image_path, link_url, link_type, type FROM ads WHERE status = 1 AND (start_date IS NULL OR start_date <= ?) AND (end_date IS NULL OR end_date >= ?) ORDER BY RAND() LIMIT 5");
        $stmt->execute([$today, $today]);
        $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($ads as &$ad) {
            $ad['image_url'] = $ad['image_path'] ? BASE_URL . "assets/images/ads/" . $ad['image_path'] : null;
        }
        api_response(true, "Ads fetched", $ads);
        break;

    case 'register_push':
        $data = get_json_payload();
        $token = $data['token'] ?? '';
        $platform = $data['platform'] ?? 'ios';
        if(empty($token)) api_response(false, "Token missing");
        
        api_response(true, "Device registered successfully for push notifications");
        break;

    default:
        api_response(false, "Action not found");
}

