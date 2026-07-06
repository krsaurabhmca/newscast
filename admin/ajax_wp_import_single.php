<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    echo json_encode(["success" => false, "error" => "Access denied."]);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(["success" => false, "error" => "Invalid input data."]);
    exit;
}

$original_title = trim($input['title'] ?? '');
$original_content = trim($input['content'] ?? ''); // Preserve HTML if direct import
$image_url = trim($input['image_url'] ?? '');
$published_at = date('Y-m-d H:i:s', strtotime($input['published_at'] ?? 'now'));
$source_url = trim($input['source_url'] ?? '');
$rewrite_with_ai = isset($input['rewrite_with_ai']) ? (bool)$input['rewrite_with_ai'] : false;
$category_name = trim($input['category_name'] ?? 'Uncategorized');

if (empty($original_title) || empty($original_content)) {
    echo json_encode(["success" => false, "error" => "Missing required fields (title, content)."]);
    exit;
}

if (!empty($source_url)) {
    $stmt_check = $pdo->prepare("SELECT id FROM posts WHERE source_url = ?");
    $stmt_check->execute([$source_url]);
    if ($stmt_check->rowCount() > 0) {
        echo json_encode(["success" => true, "skipped" => true, "message" => "Post already imported"]);
        exit;
    }
}

// 1. AI REWRITE (Groq)
if ($rewrite_with_ai) {
    $API_KEY = get_setting('groq_api_key');
    $MODEL   = "llama-3.1-8b-instant";

    if (empty($API_KEY)) {
        echo json_encode(["success" => false, "error" => "Groq API Key is missing in Settings > AI Integrations."]);
        exit;
    }

    $system_prompt = "You are an automated Professional Indian News Reporter script. 
    Your job is to rewrite the provided news article to be highly engaging, objective, and SEO-optimized in Hindi.
    IMPORTANT: You MUST return the output strictly as a valid JSON object with exactly these three keys: 'title', 'slug', 'content'.
    - 'title': The rewritten, click-worthy news headline (in Hindi).
    - 'slug': A URL-friendly English/Hinglish slug (e.g. 'new-rule-2026').
    - 'content': The fully rewritten article formatted in proper HTML (using <p>, <h2>, <ul>). The content must be in Hindi.
    Do NOT include any markdown formatting blocks like ```json. Output ONLY the raw JSON string.";

    $user_prompt = "Original Title: {$original_title}\n\nOriginal Content: " . strip_tags($original_content);

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

    $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $API_KEY,
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo json_encode(["success" => false, "error" => "AI API Error: " . curl_error($ch)]);
        exit;
    }
    curl_close($ch);

    $result = json_decode($response, true);
    $ai_reply = $result['choices'][0]['message']['content'] ?? '';
    $parsed = json_decode($ai_reply, true);

    if (!$parsed || !isset($parsed['title']) || !isset($parsed['content'])) {
        // Fallback if AI fails to return JSON
        $parsed = [
            'title' => $original_title,
            'slug' => create_slug($original_title),
            'content' => $original_content
        ];
    }
} else {
    // Direct Import without AI Rewrite
    $parsed = [
        'title' => $original_title,
        'slug' => create_slug($original_title),
        'content' => $original_content
    ];
}

$final_title = clean($parsed['title']);
$final_slug = clean($parsed['slug'] ?? create_slug($final_title));
$final_content = $parsed['content'];

// If slug is empty (e.g. non-english characters stripped), generate a random one
if (empty($final_slug) || $final_slug == '-') {
    $final_slug = 'post-' . rand(10000, 99999);
}

// Check for slug uniqueness
$stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
$stmt->execute([$final_slug]);
if ($stmt->rowCount() > 0) {
    $final_slug = $final_slug . '-' . time() . rand(10, 99);
}

// 2. IMAGE DOWNLOAD & COMPRESSION
$final_image_name = '';
if (!empty($image_url)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $image_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    $img_content = curl_exec($ch);
    curl_close($ch);

    if ($img_content) {
        $image = @imagecreatefromstring($img_content);
        if ($image) {
            $filename = 'import_' . time() . '_' . rand(1000, 9999) . '.webp';
            $upload_dir = '../assets/images/posts/';
            
            // Resize if too large
            $width = imagesx($image);
            $height = imagesy($image);
            $max_width = 1200;
            
            if ($width > $max_width) {
                $new_height = floor($height * ($max_width / $width));
                $new_image = imagecreatetruecolor($max_width, $new_height);
                // Handle transparency for PNGs before resizing
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

// 3. INSERT INTO DATABASE
try {
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, slug, content, excerpt, featured_image, status, is_featured, published_at, source_url) 
                           VALUES (?, ?, ?, ?, '', ?, 'published', 0, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $final_title,
        $final_slug,
        $final_content,
        $final_image_name,
        $published_at,
        $source_url
    ]);
    
    $post_id = $pdo->lastInsertId();
    
    // Auto-create category if it doesn't exist
    $stmt_cat_check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt_cat_check->execute([$category_name]);
    if ($stmt_cat_check->rowCount() > 0) {
        $category_id = $stmt_cat_check->fetchColumn();
    } else {
        $cat_slug = create_slug($category_name);
        if (empty($cat_slug)) $cat_slug = 'cat-' . time();
        $stmt_cat_insert = $pdo->prepare("INSERT INTO categories (name, slug, description, status) VALUES (?, ?, '', 'active')");
        $stmt_cat_insert->execute([$category_name, $cat_slug]);
        $category_id = $pdo->lastInsertId();
    }

    // Map category
    $stmt = $pdo->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)");
    $stmt->execute([$post_id, $category_id]);
    
    echo json_encode(["success" => true, "post_id" => $post_id]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Database Error: " . $e->getMessage()]);
}
