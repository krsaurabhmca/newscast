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
 * Get Post Thumbnail or Fallback Logo
 */
function get_post_thumbnail($image) {
    if ($image && file_exists(dirname(__DIR__) . '/assets/images/posts/' . $image)) {
        return BASE_URL . 'assets/images/posts/' . $image;
    }
    
    // Fallback to Site Logo
    $site_logo = get_setting('site_logo');
    if ($site_logo && file_exists(dirname(__DIR__) . '/assets/images/' . $site_logo)) {
        return BASE_URL . 'assets/images/' . $site_logo;
    }
    
    // Default placeholder if everything fails
    return BASE_URL . 'assets/images/default-post.jpg';
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
?>
