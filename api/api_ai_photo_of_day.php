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

$topic = isset($_POST['topic']) ? trim($_POST['topic']) : '';
if (empty($topic)) {
    echo json_encode(["success" => false, "message" => "Please enter a topic or keyword first."]);
    exit;
}

$prompt_text = "Topic: " . $topic;

$data = [
    "model" => "llama-3.1-8b-instant",
    "messages" => [
        [
            "role" => "system",
            "content" => "You are an expert AI creative director. Based on the topic provided by the user, you must output a JSON object containing three properties:
- 'title': A short, catchy title for a featured Photo of the Day (maximum 6 words).
- 'caption': A beautiful, poetic, or engaging caption/story describing the photo (maximum 25 words).
- 'image_prompt': A highly descriptive, visually rich prompt in English suitable for an AI image generation model like Midjourney/Flux/Pollinations (maximum 20 words).

Response MUST be a valid JSON object ONLY. Do not include markdown formatting or extra text outside the JSON."
        ],
        [
            "role" => "user",
            "content" => $prompt_text
        ]
    ],
    "temperature" => 0.7,
    "response_format" => ["type" => "json_object"]
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
    echo json_encode(["success" => false, "message" => "Failed to generate AI content."]);
    exit;
}

$parsed = json_decode($reply, true);
if (!$parsed || !isset($parsed['title']) || !isset($parsed['caption']) || !isset($parsed['image_prompt'])) {
    // Fallback if JSON format fails
    echo json_encode(["success" => false, "message" => "Failed to parse AI output structure.", "raw" => $reply]);
    exit;
}

echo json_encode([
    "success" => true,
    "title" => trim($parsed['title']),
    "caption" => trim($parsed['caption']),
    "image_prompt" => trim($parsed['image_prompt'])
]);
exit;
