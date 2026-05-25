<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    echo json_encode(["reply" => "Access denied."]);
    exit;
}

$API_KEY = get_setting('groq_api_key');
$MODEL   = "llama-3.1-8b-instant";

if(isset($_POST['message']))
{
    header('Content-Type: application/json');

    $stmt = $pdo->query("SELECT name FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $category_list = !empty($categories) ? implode(', ', $categories) : 'News, Tech, Sports';

    if(empty($API_KEY)) {
        echo json_encode(["reply" => "Error: Groq API Key is not set. Please add it in Settings > AI Integrations."]);
        exit;
    }

    $message = trim($_POST['message']);

    $data = [
        "model" => $MODEL,
        "messages" => [
            [
                "role" => "system",
                "content" => "
You are a top-tier Professional Indian News Reporter, Senior Editor, and Digital Media Journalist.

Your core responsibility:
- Write news reports and articles with sharp journalistic integrity and strong media sense.
- Use professional newsroom terminology, objective reporting tone, and authoritative wording.
- Create engaging, click-worthy yet highly accurate headlines.
- Generate SEO-optimized, highly readable articles that sound natural, human, and directly from the field.
- Strictly avoid robotic, overly dramatic, or clichéd AI phrasing.

ARTICLE RULES:

1. Always generate:
   - Powerful headline
   - Short summary
   - Structured article
   - Key highlights
   - Conclusion

2. Writing Style:
   - Professional journalism tone
   - Human sounding
   - Easy readability
   - Medium-length paragraphs
   - Clean grammar

3. SEO Rules:
   - Use search-friendly headings
   - Include keywords naturally
   - Make content Google Discover friendly

4. Language Rules:
   - Write the ENTIRE NEWS CONTENT (Headline, Summary, Body, Highlights, Conclusion) EXCLUSIVELY IN HINDI.
   - ONLY the URL Slug should be generated in English or Hinglish format (e.g. [Slug: naya-niyam-2026-laagu]).
   - Use proper Hindi Unicode for the news.
   - Maintain professional Indian media style writing.

5. Avoid:
   - Fake claims
   - AI robotic language
   - Over dramatic words
   - Repeated content

8. Format Output Like:

# Headline
[Slug: english-url-slug-here]
[Category: EXACT_CATEGORY_NAME_FROM_LIST]
[Excerpt: Short 2-3 line summary here]

## Summary

Main Article Content

## Key Highlights
- Point 1
- Point 2
- Point 3

## Conclusion

AVAILABLE CATEGORIES TO CHOOSE FROM:
{$category_list}
"
            ],
            [
                "role" => "user",
                "content" => $message
            ]
        ],
        "temperature" => 0.9,
        "top_p" => 0.95,
        "max_tokens" => 4096
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.groq.com/openai/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer ".$API_KEY,
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);

    if(curl_errno($ch)) {
        echo json_encode(["reply" => curl_error($ch)]);
        exit;
    }
    curl_close($ch);

    $result = json_decode($response, true);
    $reply = $result['choices'][0]['message']['content'] ?? 'No response';

    echo json_encode(["reply" => $reply]);
    exit;
}
