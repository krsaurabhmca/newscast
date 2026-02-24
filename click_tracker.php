<?php
require_once 'includes/config.php';

if (isset($_GET['id'])) {
    $ad_id = (int)$_GET['id'];
    
    // Fetch ad details
    $stmt = $pdo->prepare("SELECT link_url, link_type FROM ads WHERE id = ?");
    $stmt->execute([$ad_id]);
    $ad = $stmt->fetch();

    if ($ad) {
        // Increment Clicks
        $pdo->prepare("UPDATE ads SET clicks = clicks + 1 WHERE id = ?")->execute([$ad_id]);

        $destination = '';
        if ($ad['link_type'] == 'whatsapp') {
            $phone = preg_replace('/[^0-9]/', '', $ad['link_url']);
            $destination = "https://api.whatsapp.com/send?phone=" . $phone;
        } elseif ($ad['link_type'] == 'call') {
            $destination = "tel:" . $ad['link_url'];
        } else {
            $destination = $ad['link_url'];
        }

        header("Location: " . $destination);
        exit();
    }
}

// Handle Direct Sponsored Posts
if (isset($_GET['post_id'])) {
    $post_id = (int)$_GET['post_id'];
    
    $stmt = $pdo->prepare("SELECT external_link, external_type FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if ($post && $post['external_type'] != 'none') {
        // Increment views for the post
        $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?")->execute([$post_id]);

        $destination = '';
        if ($post['external_type'] == 'whatsapp') {
            $phone = preg_replace('/[^0-9]/', '', $post['external_link']);
            $destination = "https://api.whatsapp.com/send?phone=" . $phone;
        } elseif ($post['external_type'] == 'call') {
            $destination = "tel:" . $post['external_link'];
        } else {
            $destination = $post['external_link'];
        }

        header("Location: " . $destination);
        exit();
    }
}

// Fallback to home if something is wrong
header("Location: " . BASE_URL);
exit();
