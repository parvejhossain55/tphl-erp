<?php
// Test different models and endpoints to see which ones are active and what their response is.

$apiKey = "AIzaSyAxHTAWdnEx0ZubmHkC6t2rrWHCkoCBcfg";
$models = [
    "v1beta/models/gemini-2.5-flash",
    "v1beta/models/gemini-1.5-flash-latest",
    "v1/models/gemini-1.5-flash",
    "v1/models/gemini-1.5-flash-latest"
];

foreach ($models as $path) {
    $url = "https://generativelanguage.googleapis.com/" . $path . ":generateContent?key=" . $apiKey;
    $data = [
        "contents" => [
            ["role" => "user", "parts" => [["text" => "Hi"]]]
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Endpoint: $path\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: " . substr($response, 0, 300) . "\n\n";
}
