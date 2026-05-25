<?php
/**
 * Automated WordPress Sync Cron Script
 * Fetches posts from active WP sources, rewrites them using Groq AI, and imports them.
 * Limits processing to prevent server timeouts.
 */

// Since this is a cron/background script, increase execution time
set_time_limit(300); // 5 minutes max
ignore_user_abort(true); // Don't stop if the user closes the connection

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Check if we should run (only every 30 minutes to save API costs and prevent spam)
$last_run = get_setting('last_wp_cron_run');
if ($last_run) {
    $time_since_last = time() - strtotime($last_run);
    if ($time_since_last < 1800) { // 1800 seconds = 30 minutes
        // Not enough time has passed.
        exit('Skipping run: 30 minutes have not elapsed.');
    }
}

// Update the last run time immediately to prevent concurrent executions
try {
    $now_str = date('Y-m-d H:i:s');
    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('last_wp_cron_run', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$now_str, $now_str]);
} catch (Exception $e) { }

$API_KEY = get_setting('groq_api_key');
$MODEL   = "llama-3.1-8b-instant";
if (empty($API_KEY)) {
    exit('Error: Groq API Key is not set.');
}

// Fetch active sources
$stmt = $pdo->query("SELECT * FROM wp_sources WHERE status = 'active' ORDER BY last_checked ASC LIMIT 3");
$sources = $stmt->fetchAll();

if (empty($sources)) {
    exit('No active sources found.');
}

$system_prompt = "You are an automated Professional Indian News Reporter script. 
Your job is to rewrite the provided news article to be highly engaging, objective, and SEO-optimized in Hindi.
IMPORTANT: You MUST return the output strictly as a valid JSON object with exactly these three keys: 'title', 'slug', 'content'.
- 'title': The rewritten, click-worthy news headline (in Hindi).
- 'slug': A URL-friendly English/Hinglish slug (e.g. 'new-rule-2026').
- 'content': The fully rewritten article formatted in proper HTML (using <p>, <h2>, <ul>). The content must be in Hindi.
Do NOT include any markdown formatting blocks like ```json. Output ONLY the raw JSON string.";

foreach ($sources as $source) {
    // Update last_checked for this source
    $pdo->prepare("UPDATE wp_sources SET last_checked = NOW() WHERE id = ?")->execute([$source['id']]);

    $feed_url = $source['feed_url'];
    // We fetch a max of 5 posts to prevent long execution times per source
    if (strpos($feed_url, 'per_page=') === false) {
        $feed_url .= (strpos($feed_url, '?') !== false ? '&' : '?') . 'per_page=5';
    } else {
        $feed_url = preg_replace('/per_page=\d+/', 'per_page=5', $feed_url);
    }

    $ch = curl_init($feed_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) continue;

    $posts = json_decode($response, true);
    if (!is_array($posts)) continue;

    foreach ($posts as $post) {
        $original_link = trim($post['link'] ?? '');
        if (empty($original_link)) continue;

        // CHECK DUPLICATE: Does this source_url already exist?
        $stmt_check = $pdo->prepare("SELECT id FROM posts WHERE source_url = ?");
        $stmt_check->execute([$original_link]);
        if ($stmt_check->rowCount() > 0) {
            // Post already imported, skip
            continue; 
        }

        $original_title = trim($post['title']['rendered'] ?? '');
        $original_content = trim(strip_tags($post['content']['rendered'] ?? ''));
        $published_at = date('Y-m-d H:i:s', strtotime($post['date'] ?? 'now'));
        
        $image_url = '';
        if (isset($post['_embedded']['wp:featuredmedia'][0]['source_url'])) {
            $image_url = $post['_embedded']['wp:featuredmedia'][0]['source_url'];
        }

        if (empty($original_title) || empty($original_content)) continue;

        // 1. AI REWRITE
        $user_prompt = "Original Title: {$original_title}\n\nOriginal Content: {$original_content}";
        $data = [
            "model" => $MODEL,
            "messages" => [
                ["role" => "system", "content" => $system_prompt],
                ["role" => "user", "content" => $user_prompt]
            ],
            "temperature" => 0.7,
            "max_tokens" => 4096,
            "response_format" => ["type" => "json_object"]
        ];

        $ch_ai = curl_init("https://api.groq.com/openai/v1/chat/completions");
        curl_setopt($ch_ai, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_ai, CURLOPT_POST, true);
        curl_setopt($ch_ai, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch_ai, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $API_KEY,
            "Content-Type: application/json"
        ]);
        curl_setopt($ch_ai, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch_ai, CURLOPT_TIMEOUT, 30);
        $ai_response = curl_exec($ch_ai);
        $ai_code = curl_getinfo($ch_ai, CURLINFO_HTTP_CODE);
        curl_close($ch_ai);

        $parsed = null;
        if ($ai_code === 200 && $ai_response) {
            $result = json_decode($ai_response, true);
            $ai_reply = $result['choices'][0]['message']['content'] ?? '';
            $parsed = json_decode($ai_reply, true);
        }

        if (!$parsed || !isset($parsed['title']) || !isset($parsed['content'])) {
            $parsed = [
                'title' => $original_title,
                'slug' => create_slug($original_title),
                'content' => $original_content
            ];
        }

        $final_title = clean($parsed['title']);
        $final_slug = clean($parsed['slug'] ?? create_slug($final_title));
        $final_content = $parsed['content'];

        if (empty($final_slug) || $final_slug == '-') {
            $final_slug = 'post-' . rand(10000, 99999);
        }

        $stmt_slug = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
        $stmt_slug->execute([$final_slug]);
        if ($stmt_slug->rowCount() > 0) {
            $final_slug = $final_slug . '-' . time() . rand(10, 99);
        }

        // 2. IMAGE DOWNLOAD & COMPRESSION
        $final_image_name = '';
        if (!empty($image_url)) {
            $img_content = @file_get_contents($image_url);
            if ($img_content) {
                $image = @imagecreatefromstring($img_content);
                if ($image) {
                    $filename = 'import_' . time() . '_' . rand(1000, 9999) . '.webp';
                    $upload_dir = dirname(__DIR__) . '/assets/images/posts/';
                    
                    $width = imagesx($image);
                    $height = imagesy($image);
                    $max_width = 1200;
                    
                    if ($width > $max_width) {
                        $new_height = floor($height * ($max_width / $width));
                        $new_image = imagecreatetruecolor($max_width, $new_height);
                        imagealphablending($new_image, false);
                        imagesavealpha($new_image, true);
                        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $max_width, $new_height, $width, $height);
                        $image = $new_image;
                    }
                    
                    if (imagewebp($image, $upload_dir . $filename, 85)) {
                        $final_image_name = $filename;
                    }
                    imagedestroy($image);
                }
            }
        }

        // We need an admin user ID for automated posts. Use ID 1 (Primary Admin)
        $admin_id = 1;

        // 3. INSERT INTO DATABASE
        try {
            $stmt_in = $pdo->prepare("INSERT INTO posts (user_id, title, slug, content, excerpt, featured_image, status, is_featured, published_at, source_url) 
                                   VALUES (?, ?, ?, ?, '', ?, 'published', 0, ?, ?)");
            $stmt_in->execute([
                $admin_id,
                $final_title,
                $final_slug,
                $final_content,
                $final_image_name,
                $published_at,
                $original_link
            ]);
            
            $post_id = $pdo->lastInsertId();
            
            $stmt_cat = $pdo->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)");
            $stmt_cat->execute([$post_id, $source['category_id']]);
            
        } catch (Exception $e) {
            error_log("WP Sync DB Error: " . $e->getMessage());
        }
    }
}

echo "Sync Completed Successfully.";
