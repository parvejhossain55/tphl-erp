<?php

$apiKey = "AIzaSyAxHTAWdnEx0ZubmHkC6t2rrWHCkoCBcfg";
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

$data = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => "Hello! Say hi in Bengali."]
            ]
        ]
    ]
];

echo "Sending request to Gemini API...\n";

$ch = curl_init($url);
$payload = json_encode($data);

curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$start = microtime(true);
$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$duration = round(microtime(true) - $start, 2);
echo "Completed in {$duration}s\n";
echo "HTTP Code: {$httpCode}\n";

if ($error) {
    echo "cURL Error: {$error}\n";
} else {
    echo "Response:\n";
    echo $response . "\n";
}
