<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$API_KEY = get_setting('groq_api_key');
$MODEL   = "llama-3.1-8b-instant";

$prompt = "You are an expert Indian News Editor. Generate a highly engaging poll question based on current trending social or political topics in India. The output MUST be in Hindi. 
Return ONLY valid JSON with no markdown formatting or extra text. Format:
{
    \"question\": \"Poll Question Here in Hindi?\",
    \"options\": [\"Option 1\", \"Option 2\", \"Option 3\", \"Option 4\"]
}";

$data = [
    "model" => $MODEL,
    "messages" => [
        [
            "role" => "system",
            "content" => "You are a JSON generating assistant. Always output clean, raw JSON."
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
    echo "CURL Error: " . curl_error($ch) . "\n";
}
curl_close($ch);

echo $response;
?>
