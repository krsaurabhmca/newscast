<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$API_KEY = get_setting('groq_api_key');
$MODEL   = "llama-3.1-8b-instant";

if (empty($API_KEY)) {
    echo json_encode(['success' => false, 'message' => 'Groq API Key is missing. Configure it in Settings.']);
    exit;
}

$prompt = "You are an expert Indian News Editor. Generate a highly engaging poll question based on current trending social or political topics in India. The text MUST be in Hindi. 
Return ONLY valid JSON with no markdown formatting or extra text. 
CRITICAL: The JSON keys MUST be in English EXACTLY as 'question' and 'options'. Only the values should be in Hindi.
Format:
{
    \"question\": \"Poll Question Here in Hindi?\",
    \"options\": [\"Option 1\", \"Option 2\", \"Option 3\", \"Option 4\"]
}";

$data = [
    "model" => $MODEL,
    "messages" => [
        [
            "role" => "system",
            "content" => "You are a JSON generating assistant. Always output clean, raw JSON with English keys and Hindi values."
        ],
        [
            "role" => "user",
            "content" => $prompt
        ]
    ],
    "temperature" => 0.8,
    "top_p" => 0.95,
    "response_format" => ["type" => "json_object"]
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
    echo json_encode(['success' => false, 'message' => curl_error($ch)]);
    exit;
}
curl_close($ch);

$result = json_decode($response, true);
$reply = $result['choices'][0]['message']['content'] ?? '';

if (empty($reply)) {
    echo json_encode(['success' => false, 'message' => 'Failed to generate poll.']);
    exit;
}

// Ensure it's valid JSON
$pollData = json_decode($reply, true);
if (json_last_error() === JSON_ERROR_NONE && isset($pollData['question']) && isset($pollData['options'])) {
    echo json_encode(['success' => true, 'data' => $pollData]);
} else {
    echo json_encode(['success' => false, 'message' => 'AI returned invalid format.', 'raw' => $reply]);
}
?>
