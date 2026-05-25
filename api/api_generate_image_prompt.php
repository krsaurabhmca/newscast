<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(["success" => false, "message" => "Access denied."]);
    exit;
}

$API_KEY = get_setting('groq_api_key');
if (empty($API_KEY)) {
    echo json_encode(["success" => false, "message" => "Groq API Key is not set. Please add it in Settings."]);
    exit;
}

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$content = isset($_POST['content']) ? trim(strip_tags($_POST['content'])) : '';

if (empty($title) && empty($content)) {
    echo json_encode(["success" => false, "message" => "Please enter a title or content first."]);
    exit;
}

$prompt_text = "Title: " . $title . "\n\nContent: " . mb_substr($content, 0, 1000);

$data = [
    "model" => "llama-3.1-8b-instant",
    "messages" => [
        [
            "role" => "system",
            "content" => "You are an expert AI image prompt generator. Analyze the provided news article title and content. Create a highly descriptive, visually rich prompt for an image generation model (like Midjourney or Pollinations).
            
RULES:
- MUST BE IN ENGLISH. Even if the article is in another language, translate the concepts to an English prompt.
- Include a related visual theme/style (e.g., realistic, cinematic, dramatic lighting, vector art) that fits the article's mood.
- Maximum 15-20 words.
- Describe the visual scene clearly.
- NO introductory text (like 'Here is a prompt' or 'Prompt:').
- Just output the exact prompt string."
        ],
        [
            "role" => "user",
            "content" => $prompt_text
        ]
    ],
    "temperature" => 0.7,
    "max_tokens" => 50
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.groq.com/openai/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $API_KEY,
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["success" => false, "message" => curl_error($ch)]);
    exit;
}
curl_close($ch);

$result = json_decode($response, true);
$reply = $result['choices'][0]['message']['content'] ?? '';

if (empty($reply)) {
    echo json_encode(["success" => false, "message" => "Failed to generate prompt."]);
    exit;
}

// Clean up any potential unwanted quotes or prefixes
$reply = trim(str_replace(['"', "'", "Prompt:"], "", $reply));

echo json_encode(["success" => true, "prompt" => $reply]);
exit;
