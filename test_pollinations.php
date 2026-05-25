<?php
$prompt = urlencode("A cow grazes calmly in a sunny green meadow, surrounded by wildflowers under soft, warm light.");
$urls = [
    "default" => "https://image.pollinations.ai/prompt/{$prompt}?width=1200&height=800&nologo=true",
    "flux" => "https://image.pollinations.ai/prompt/{$prompt}?width=1200&height=800&nologo=true&model=flux",
    "turbo" => "https://image.pollinations.ai/prompt/{$prompt}?width=1200&height=800&nologo=true&model=turbo"
];

foreach ($urls as $name => $url) {
    echo "Testing $name model...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); // Get headers
    curl_setopt($ch, CURLOPT_NOBODY, true); // Only GET headers to see status
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "$name returned HTTP $httpCode\n\n";
}
