<?php
// helpers.php

/**
 * Generate a URL friendly slug
 */
function create_slug($string)
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug;
}

/**
 * Sanitize input data
 */
function clean($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Check if the user is a demo account
 */
function is_demo_account()
{
    if (isset($_SESSION['username'])) {
        return stripos($_SESSION['username'], 'demo') !== false;
    }
    return false;
}

/**
 * Check if user is logged in
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin()
{
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'dev');
}

/**
 * Check if user is editor or higher
 */
function is_editor()
{
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['editor', 'admin', 'dev']);
}

/**
 * Check if user is reporter
 */
function is_reporter()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'reporter';
}

/**
 * Check if current user can edit a specific post
 */
function can_edit_post($post)
{
    if (is_admin() || is_editor()) {
        return true; // Admins and Editors can edit any post
    }
    if (is_reporter()) {
        // Reporter can only edit their own draft posts
        return (isset($post['status']) && $post['status'] === 'draft') && 
               (isset($post['user_id']) && $post['user_id'] == $_SESSION['user_id']);
    }
    return false;
}

/**
 * Redirect with message
 */
function redirect($path, $message = '', $type = 'success')
{
    if ($message) {
        $_SESSION['flash_msg'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: " . BASE_URL . $path);
    exit();
}

/**
 * Format date
 */
function format_date($date)
{
    return date('M d, Y', strtotime($date));
}

/**
 * Get category name by ID
 */
function get_category_name($pdo, $id)
{
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $cat = $stmt->fetch();
    return $cat ? $cat['name'] : 'Uncategorized';
}

/**
 * Get all categories for a post
 */
function get_post_categories($pdo, $post_id)
{
    $stmt = $pdo->prepare("SELECT c.* FROM categories c JOIN post_categories pc ON c.id = pc.category_id WHERE pc.post_id = ? AND c.status = 'active'");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}
/**
 * Get Post Thumbnail or Dynamic Placeholder
 */
function get_post_thumbnail($image)
{
    if (empty($image)) {
        return BASE_URL . 'assets/images/default-post.jpg';
    }

    // If it's a base64 data URI, return as-is
    if (strpos($image, 'data:image') === 0) {
        return $image;
    }

    // If it's already a full URL, return as-is
    if (strpos($image, 'http') === 0) {
        return $image;
    }

    // If it's a library media image
    if (strpos($image, 'media/') === 0) {
        return BASE_URL . 'assets/images/' . $image;
    }

    // Build full URL from filename — do NOT check file_exists (breaks on remote/live server)
    return BASE_URL . 'assets/images/posts/' . $image;
}

/**
 * Get profile image URL with fallback to default avatar
 */
function get_profile_image($filename, $base = '../')
{
    $default = BASE_URL . 'assets/images/default-avatar.svg';
    if (empty($filename))
        return $default;
    // Check file exists on disk
    $disk_path = dirname(__DIR__) . '/assets/images/' . $filename;
    if (!file_exists($disk_path))
        return $default;
    return BASE_URL . 'assets/images/' . $filename;
}
/**
 * Robustly extract YouTube Video ID from any URL
 */
function extract_youtube_id($url)
{
    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
    return isset($match[1]) ? $match[1] : false;
}

/**
 * Get and Render Ad for a specific location
 */
function display_ad($location, $pdo)
{
    $today = date('Y-m-d');

    // Fetch an ad that is active and within its date range (if set)
    $stmt = $pdo->prepare("SELECT * FROM ads 
                           WHERE location = ? 
                           AND status = 1 
                           AND (start_date IS NULL OR start_date <= ?) 
                           AND (end_date IS NULL OR end_date >= ?) 
                           ORDER BY RAND() LIMIT 1");
    $stmt->execute([$location, $today, $today]);
    $ad = $stmt->fetch();

    if (!$ad)
        return '';

    // Increment Impression
    $pdo->prepare("UPDATE ads SET impressions = impressions + 1 WHERE id = ?")->execute([$ad['id']]);

    $html = '<div class="ad-container ad-' . $location . '" style="margin: 20px 0; text-align: center;">';
    $html .= '<span style="display: block; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Advertisement</span>';

    if ($ad['type'] == 'image') {
        // Construct the click tracking URL
        $tracker_url = BASE_URL . "click_tracker.php?id=" . $ad['id'];

        $html .= '<a href="' . $tracker_url . '" target="_blank" style="display: block;">';
        $html .= '<img src="' . BASE_URL . 'assets/images/ads/' . $ad['image_path'] . '" alt="' . $ad['name'] . '" style="max-width: 100%; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">';
        $html .= '</a>';
    }
    else {
        // For code-based ads (like AdSense), we can't easily track clicks via a redirect, so we just output the code
        $html .= $ad['ad_code'];
    }

    $html .= '</div>';

    return $html;
}

/**
 * Get all tags for a post
 */
function get_post_tags($pdo, $post_id)
{
    $stmt = $pdo->prepare("SELECT t.* FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

/**
 * Get all active/popular tags (Prioritize recent activity)
 */
function get_all_tags($pdo, $limit = 20)
{
    $limit = (int)$limit;
    $stmt = $pdo->prepare("SELECT t.*, COUNT(pt.post_id) as post_count 
                           FROM tags t 
                           LEFT JOIN post_tags pt ON t.id = pt.tag_id 
                           LEFT JOIN posts p ON pt.post_id = p.id
                           WHERE p.published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) OR p.id IS NULL
                           GROUP BY t.id 
                           ORDER BY post_count DESC, t.name ASC 
                           LIMIT {$limit}");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Calculate estimated reading time in minutes
 */
function calculate_reading_time($content)
{
    $word_count = str_word_count(strip_tags($content));
    $words_per_minute = 200;
    return ceil($word_count / $words_per_minute);
}

/**
 * Log user activity
 */
function log_activity($pdo, $user_id, $post_id, $type = 'view')
{
    if (!$user_id)
        return;
    try {
        $stmt = $pdo->prepare("INSERT INTO user_activity (user_id, post_id, action_type) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $post_id, $type]);
    }
    catch (PDOException $e) {
    }
}

/**
 * Shorten text to a specific word count with optional fallback
 */
function get_excerpt($text, $word_count = 25)
{
    if (empty($text))
        return '';

    // Remove HTML and clean up whitespace
    $text = strip_tags($text);
    $text = trim(preg_replace('/\s+/', ' ', $text));

    $words = explode(' ', $text);
    if (count($words) > $word_count) {
        return implode(' ', array_slice($words, 0, $word_count)) . '...';
    }
    return $text;
}

/**
 * Get excerpt from a post array, falling back to content if needed
 */
function get_post_excerpt($post, $word_count = 25)
{
    if (!$post)
        return '';
    $text = !empty($post['excerpt']) ? $post['excerpt'] : (!empty($post['content']) ? $post['content'] : '');
    return get_excerpt($text, $word_count);
}
/**
 * Upload, Resize, and Convert Image to highly optimized WEBP
 * @return string|false Returns new WEBP filename on success, false on failure
 */
function upload_and_optimize_image($file_array, $upload_dir, $prefix = 'img_', $max_width = 1200, $quality = 80)
{
    if (!isset($file_array['tmp_name']) || empty($file_array['tmp_name']) || $file_array['error'] !== 0) {
        return false;
    }
    
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    $source = $file_array['tmp_name'];
    $info = @getimagesize($source);
    if ($info === false) {
        return false;
    }

    // Attempt to use GD to convert and resize
    if (extension_loaded('gd')) {
        $image = null;
        switch ($info['mime']) {
            case 'image/jpeg': $image = @imagecreatefromjpeg($source); break;
            case 'image/gif':  $image = @imagecreatefromgif($source);  break;
            case 'image/png':  $image = @imagecreatefrompng($source);  break;
            case 'image/webp': $image = @imagecreatefromwebp($source); break;
        }

        if ($image) {
            $width = imagesx($image);
            $height = imagesy($image);

            $new_width = $width;
            $new_height = $height;

            if ($width > $max_width) {
                $new_width = $max_width;
                $new_height = floor($height * ($max_width / $width));
            }

            $tmp = imagecreatetruecolor($new_width, $new_height);
            // Preserve transparency
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);
            $transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
            imagefilledrectangle($tmp, 0, 0, $new_width, $new_height, $transparent);

            imagecopyresampled($tmp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($image);
            $image = $tmp;

            // Add Website URL as watermark stamp for post images
            if (defined('BASE_URL') && (strpos($prefix, 'post') === 0 || strpos($upload_dir, 'posts') !== false)) {
                $stamp_text = preg_replace('/^https?:\/\/(www\.)?/', '', rtrim(BASE_URL, '/'));
                if (!empty($stamp_text)) {
                    // Enable alpha blending to support semi-transparent watermark background
                    imagealphablending($image, true);
                    
                    $font_size = 5;
                    $char_width = 9;
                    $char_height = 15;
                    $text_width = strlen($stamp_text) * $char_width;
                    
                    $margin = 15;
                    // Position bottom-right
                    $x = $new_width - $text_width - $margin;
                    $y = $new_height - $char_height - $margin;
                    
                    // Only apply if the image is large enough
                    if ($new_width > ($text_width + $margin * 2) && $new_height > ($char_height + $margin * 2)) {
                        // Draw semi-transparent background badge (black with ~40% opacity)
                        $bg_color = imagecolorallocatealpha($image, 0, 0, 0, 50);
                        imagefilledrectangle($image, $x - 8, $y - 6, $x + $text_width + 8, $y + $char_height + 6, $bg_color);
                        
                        // Draw white text
                        $text_color = imagecolorallocate($image, 255, 255, 255);
                        imagestring($image, $font_size, $x, $y, $stamp_text, $text_color);
                    }
                }
            }

            $new_filename = uniqid($prefix) . '_' . time() . '.webp';
            $destination = rtrim($upload_dir, '/') . '/' . $new_filename;

            if (imagewebp($image, $destination, $quality)) {
                imagedestroy($image);
                return $new_filename;
            }
            imagedestroy($image);
        }
    }

    // Fallback: If GD fails or not loaded, just move original file
    $img_ext = strtolower(pathinfo($file_array['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid($prefix) . '_' . time() . '.' . $img_ext;
    $destination = rtrim($upload_dir, '/') . '/' . $new_filename;
    if (move_uploaded_file($source, $destination)) {
        return $new_filename;
    }

    return false;
}

/**
 * Compress and resize images on upload
 * Reduces file size while maintaining visibility (Target: 60-70% reduction)
 */
function compress_image($source, $destination, $quality = 60)
{
    if (!extension_loaded('gd'))
        return false;

    $info = getimagesize($source);
    if ($info === false)
        return false;

    // Create image from source based on type
    switch ($info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/webp':
            $image = @imagecreatefromwebp($source);
            break;
        default:
            return false;
    }

    if (!$image)
        return false;

    // Get original dimensions
    $width = imagesx($image);
    $height = imagesy($image);

    // Optional: Resize if image is extremely large to save more space
    $max_width = 1280;
    if ($width > $max_width) {
        $new_width = $max_width;
        $new_height = floor($height * ($max_width / $width));
        $tmp = imagecreatetruecolor($new_width, $new_height);

        // Preserve transparency for PNG/GIF/WEBP
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);

        imagecopyresampled($tmp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($image);
        $image = $tmp;
    }

    // Save with reduced quality
    // Mapping: 60% quality often leads to ~70-80% byte reduction for high-res photos
    switch ($info['mime']) {
        case 'image/jpeg':
            imagejpeg($image, $destination, $quality);
            break;
        case 'image/webp':
            imagewebp($image, $destination, $quality);
            break;
        case 'image/png':
            // PNG quality is 0-9 (0 = no compression, 9 = max compression)
            $png_quality = 9 - floor($quality / 10); // Quality 60 maps to ~3, 70 to ~2, but we want high compression (9)
            imagepng($image, $destination, 9);
            break;
        case 'image/gif':
            imagegif($image, $destination);
            break;
    }

    imagedestroy($image);
    return true;
}

/**
 * Ensure wp_sources table exists in the database.
 */
function ensure_wp_sources_table($pdo) {
    try {
        $pdo->query("SELECT 1 FROM wp_sources LIMIT 1");
    } catch (PDOException $e) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS wp_sources (
                id INT AUTO_INCREMENT PRIMARY KEY,
                site_name VARCHAR(255) NOT NULL,
                feed_url VARCHAR(500) NOT NULL,
                category_id INT NOT NULL,
                status ENUM('active', 'inactive') DEFAULT 'active',
                last_checked DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (PDOException $ex) {
            error_log("Failed to auto-create wp_sources table: " . $ex->getMessage());
                }
    }
}

/**
 * Cached check for system updates (runs once every 4 hours max to avoid rate limits)
 * @return bool True if update is available, false otherwise
 */
function check_system_updates_cached($pdo) {
    $last_check = (int)get_setting('last_update_check', 0);
    $now = time();
    
    // Check every 4 hours (14400 seconds)
    if ($now - $last_check > 14400) {
        $api_version_url = 'https://api.github.com/repos/krsaurabhmca/newscast/contents/version.json';
        $local_version_file = __DIR__ . '/../version.json';
        $local_info = ['version' => '1.0.0', 'db_version' => 1];
        if (file_exists($local_version_file)) {
            $content = @file_get_contents($local_version_file);
            $local_info = json_decode($content, true) ?: $local_info;
        }

        // Get actual db_version from setting
        try {
            $stmt_db = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'db_version'");
            $actual_db_version = $stmt_db->fetchColumn();
            if ($actual_db_version !== false) {
                $local_info['db_version'] = (int)$actual_db_version;
            }
        } catch (Exception $e) {}

        // Perform curl
        $ch = curl_init($api_version_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); // 2 second timeout to prevent blocking page rendering
        curl_setopt($ch, CURLOPT_USERAGENT, 'NewsCast-AutoUpdater');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $update_available = 'no';
        if ($http_code == 200 && $response) {
            $api_data = json_decode($response, true);
            if (isset($api_data['content'])) {
                $decoded_json = base64_decode($api_data['content']);
                $remote_info = json_decode($decoded_json, true);
                if ($remote_info) {
                    if (version_compare($remote_info['version'], $local_info['version'], '>') || (int)$remote_info['db_version'] > (int)$local_info['db_version']) {
                        $update_available = 'yes';
                    }
                }
            }
        }

        // Save back to DB settings
        try {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('update_available', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$update_available, $update_available]);

            $stmt_time = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('last_update_check', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt_time->execute([$now, $now]);
            
            // Update global settings array so it's active immediately
            global $settings;
            $settings['update_available'] = $update_available;
            $settings['last_update_check'] = $now;
        } catch (Exception $e) {}
    }

    return get_setting('update_available', 'no') === 'yes';
}

function share_to_facebook($url, $title)
{
    $page_id = get_setting('fb_page_id', '');
    $access_token = get_setting('fb_page_access_token', '');
    if (!$page_id || !$access_token)
        return ['ok' => false, 'msg' => 'Not configured. Add Page ID and Access Token in Configuration tab.'];
    $api_url = "https://graph.facebook.com/v22.0/{$page_id}/feed";
    $data = ['message' => $title . "\n\nRead more: " . $url, 'link' => $url, 'access_token' => $access_token];
    $ch = curl_init($api_url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data), CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 15]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($resp, true);
    if (isset($json['id']))
        return ['ok' => true, 'msg' => 'Posted! Post ID: ' . $json['id']];
    return ['ok' => false, 'msg' => $json['error']['message'] ?? 'Unknown error'];
}

function share_to_instagram($url, $title, $image_url = '')
{
    $ig_id = get_setting('ig_business_account_id', '');
    $access_token = get_setting('ig_access_token', '');
    if (!$ig_id || !$access_token)
        return ['ok' => false, 'msg' => 'Instagram not configured. Add Business Account ID and Access Token.'];
    if (!$image_url)
        return ['ok' => false, 'msg' => 'Instagram requires a featured image on the article.'];
    $caption = $title . "\n\n" . $url . "\n\n#news #breakingnews #media";
    $ch = curl_init("https://graph.facebook.com/v22.0/{$ig_id}/media");
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['image_url' => $image_url, 'caption' => $caption, 'access_token' => $access_token]), CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false]);
    $r = json_decode(curl_exec($ch), true);
    curl_close($ch);
    if (!isset($r['id']))
        return ['ok' => false, 'msg' => 'Container failed: ' . ($r['error']['message'] ?? 'Unknown')];
    $ch2 = curl_init("https://graph.facebook.com/v22.0/{$ig_id}/media_publish");
    curl_setopt_array($ch2, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['creation_id' => $r['id'], 'access_token' => $access_token]), CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false]);
    $r2 = json_decode(curl_exec($ch2), true);
    curl_close($ch2);
    if (isset($r2['id']))
        return ['ok' => true, 'msg' => 'Posted to Instagram! ID: ' . $r2['id']];
    return ['ok' => false, 'msg' => $r2['error']['message'] ?? 'Publish failed'];
}

function trigger_auto_share($pdo, $post_id)
{
    if (get_setting('auto_share_on_publish', 'yes') !== 'yes') {
        return;
    }
    
    // Fetch post details
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    if (!$post || $post['status'] !== 'published') {
        return;
    }
    
    $post_url = BASE_URL . 'article/' . $post['slug'];
    $post_title = $post['title'];
    $post_image = !empty($post['featured_image']) ? BASE_URL . 'assets/images/posts/' . $post['featured_image'] : '';
    
    // Auto Share to Facebook
    if (get_setting('auto_share_facebook', 'no') === 'yes') {
        share_to_facebook($post_url, $post_title);
    }
    
    // Auto Share to Instagram
    if (get_setting('auto_share_instagram', 'no') === 'yes') {
        share_to_instagram($post_url, $post_title, $post_image);
    }
}

function get_google_indexing_token($json_creds_string) {
    $creds = json_decode($json_creds_string, true);
    if (!$creds || !isset($creds['private_key']) || !isset($creds['client_email'])) {
        return null;
    }
    
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $payload = json_encode([
        'iss' => $creds['client_email'],
        'scope' => 'https://www.googleapis.com/auth/indexing',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature_input = $base64UrlHeader . "." . $base64UrlPayload;
    $private_key = $creds['private_key'];
    
    $signature = '';
    if (!openssl_sign($signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256)) {
        return null;
    }
    
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $signature_input . "." . $base64UrlSignature;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $res_json = json_decode($response, true);
    return $res_json['access_token'] ?? null;
}

function submit_to_google_indexing($url, $action = 'URL_UPDATED') {
    $enabled = get_setting('google_indexing_enabled', 'no');
    if ($enabled !== 'yes') return false;
    
    $creds = get_setting('google_indexing_credentials', '');
    if (empty($creds)) return false;
    
    $token = get_google_indexing_token($creds);
    if (!$token) return false;
    
    $body = json_encode([
        'url' => $url,
        'type' => $action
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://indexing.googleapis.com/v3/urlNotifications:publish');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

function trigger_google_indexing($pdo, $post_id, $action = 'URL_UPDATED')
{
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    if (!$post) return;
    
    // For external link types, indexing is usually not done
    if ($post['external_type'] !== 'none') return;
    
    $post_url = BASE_URL . 'article/' . $post['slug'];
    submit_to_google_indexing($post_url, $action);
}
?>
