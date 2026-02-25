<?php
// helpers.php

/**
 * Generate a URL friendly slug
 */
function create_slug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug;
}

/**
 * Sanitize input data
 */
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect with message
 */
function redirect($path, $message = '', $type = 'success') {
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
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Get category name by ID
 */
function get_category_name($pdo, $id) {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $cat = $stmt->fetch();
    return $cat ? $cat['name'] : 'Uncategorized';
}

/**
 * Get all categories for a post
 */
function get_post_categories($pdo, $post_id) {
    $stmt = $pdo->prepare("SELECT c.* FROM categories c JOIN post_categories pc ON c.id = pc.category_id WHERE pc.post_id = ?");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}
/**
 * Get Post Thumbnail or Dynamic Placeholder
 */
function get_post_thumbnail($image) {
    if ($image && file_exists(dirname(__DIR__) . '/assets/images/posts/' . $image)) {
        return BASE_URL . 'assets/images/posts/' . $image;
    }
    
    // Dynamic Placeholder Implementation
    $portal_name = defined('SITE_NAME_DYNAMIC') ? SITE_NAME_DYNAMIC : (defined('SITE_NAME') ? SITE_NAME : 'News Cast');
    $portal_url = str_replace(['http://', 'https://'], '', BASE_URL);
    $portal_url = rtrim($portal_url, '/');
    
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450" viewBox="0 0 800 450">
        <rect width="800" height="450" fill="#cbd5e1"/>
        <text x="50%" y="48%" font-family="system-ui, -apple-system, sans-serif" font-size="36" font-weight="900" fill="#ffffff" text-anchor="middle" dominant-baseline="middle" letter-spacing="4px">
            ' . strtoupper($portal_name) . '
        </text>
        <text x="50%" y="58%" font-family="system-ui, -apple-system, sans-serif" font-size="18" font-weight="600" fill="#ffffff" fill-opacity="0.8" text-anchor="middle" dominant-baseline="middle" letter-spacing="1px">
            ' . strtolower($portal_url) . '
        </text>
    </svg>';
    
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Get profile image URL with fallback to default avatar
 */
function get_profile_image($filename, $base = '../') {
    $default = BASE_URL . 'assets/images/default-avatar.svg';
    if (empty($filename)) return $default;
    // Check file exists on disk
    $disk_path = dirname(__DIR__) . '/assets/images/' . $filename;
    if (!file_exists($disk_path)) return $default;
    return BASE_URL . 'assets/images/' . $filename;
}
/**
 * Robustly extract YouTube Video ID from any URL
 */
function extract_youtube_id($url) {
    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
    return isset($match[1]) ? $match[1] : false;
}

/**
 * Get and Render Ad for a specific location
 */
function display_ad($location, $pdo) {
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

    if (!$ad) return '';

    // Increment Impression
    $pdo->prepare("UPDATE ads SET impressions = impressions + 1 WHERE id = ?")->execute([$ad['id']]);

    $html = '<div class="ad-container ad-' . $location . '" style="margin: 20px 0; text-align: center;">';
    
    if ($ad['type'] == 'image') {
        // Construct the click tracking URL
        $tracker_url = BASE_URL . "click_tracker.php?id=" . $ad['id'];
        
        $html .= '<a href="' . $tracker_url . '" target="_blank" style="display: block;">';
        $html .= '<img src="' . BASE_URL . 'assets/images/ads/' . $ad['image_path'] . '" alt="' . $ad['name'] . '" style="max-width: 100%; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">';
        $html .= '</a>';
    } else {
        // For code-based ads (like AdSense), we can't easily track clicks via a redirect, so we just output the code
        $html .= $ad['ad_code'];
    }
    
    $html .= '</div>';

    return $html;
}

/**
 * Get all tags for a post
 */
function get_post_tags($pdo, $post_id) {
    $stmt = $pdo->prepare("SELECT t.* FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

/**
 * Get all active/popular tags (Prioritize recent activity)
 */
function get_all_tags($pdo, $limit = 20) {
    $stmt = $pdo->prepare("SELECT t.*, COUNT(pt.post_id) as post_count 
                           FROM tags t 
                           LEFT JOIN post_tags pt ON t.id = pt.tag_id 
                           LEFT JOIN posts p ON pt.post_id = p.id
                           WHERE p.published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) OR p.id IS NULL
                           GROUP BY t.id 
                           ORDER BY post_count DESC, t.name ASC 
                           LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Calculate estimated reading time in minutes
 */
function calculate_reading_time($content) {
    $word_count = str_word_count(strip_tags($content));
    $words_per_minute = 200;
    return ceil($word_count / $words_per_minute);
}

/**
 * Log user activity
 */
function log_activity($pdo, $user_id, $post_id, $type = 'view') {
    if (!$user_id) return;
    try {
        $stmt = $pdo->prepare("INSERT INTO user_activity (user_id, post_id, action_type) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $post_id, $type]);
    } catch (PDOException $e) {}
}

/**
 * Shorten text to a specific word count
 */
function get_excerpt($text, $word_count = 25) {
    if (!$text) return '';
    $text = strip_tags($text);
    $words = explode(' ', $text);
    if (count($words) > $word_count) {
        return implode(' ', array_slice($words, 0, $word_count)) . '...';
    }
    return $text;
}
?>
