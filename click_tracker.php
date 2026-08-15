<?php
require_once 'includes/config.php';

/**
 * Helper: get visitor's real IP address
 */
function get_visitor_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Helper: insert a row into ad_click_logs
 */
function log_ad_click(PDO $pdo, array $data): void {
    try {
        $pdo->prepare("
            INSERT INTO ad_click_logs
                (ad_id, post_id, event_type, ad_name, ad_location, ip_address, user_agent, referer_url)
            VALUES
                (:ad_id, :post_id, :event_type, :ad_name, :ad_location, :ip_address, :user_agent, :referer_url)
        ")->execute($data);
    } catch (Exception $e) {
        // Silently fail — never block the redirect because of logging
    }
}

$visitor_ip    = get_visitor_ip();
$user_agent    = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
$referer_url   = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500);

// ── Handle Regular Ad Clicks ───────────────────────────────────────────────
if (isset($_GET['id'])) {
    $ad_id = (int)$_GET['id'];

    // Fetch ad details
    $stmt = $pdo->prepare("SELECT link_url, link_type, name, location FROM ads WHERE id = ?");
    $stmt->execute([$ad_id]);
    $ad = $stmt->fetch();

    if ($ad) {
        // Increment aggregate clicks counter
        $pdo->prepare("UPDATE ads SET clicks = clicks + 1 WHERE id = ?")->execute([$ad_id]);

        // Log the individual click
        log_ad_click($pdo, [
            ':ad_id'      => $ad_id,
            ':post_id'    => null,
            ':event_type' => 'ad_click',
            ':ad_name'    => $ad['name'],
            ':ad_location'=> $ad['location'],
            ':ip_address' => $visitor_ip,
            ':user_agent' => $user_agent,
            ':referer_url'=> $referer_url,
        ]);

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

// ── Handle Sponsored Post Clicks ──────────────────────────────────────────
if (isset($_GET['post_id'])) {
    $post_id = (int)$_GET['post_id'];

    $stmt = $pdo->prepare("SELECT external_link, external_type, title FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if ($post && $post['external_type'] != 'none') {
        // Record post view
        record_post_view($pdo, $post_id);

        // Log the individual click
        log_ad_click($pdo, [
            ':ad_id'      => null,
            ':post_id'    => $post_id,
            ':event_type' => 'sponsored_post_click',
            ':ad_name'    => $post['title'],
            ':ad_location'=> 'sponsored_post',
            ':ip_address' => $visitor_ip,
            ':user_agent' => $user_agent,
            ':referer_url'=> $referer_url,
        ]);

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
