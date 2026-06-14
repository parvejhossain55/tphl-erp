<?php

$apiKey = "AIzaSyAxHTAWdnEx0ZubmHkC6t2rrWHCkoCBcfg";
$url = "https://generativelanguage.googleapis.com/v1/models?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "cURL Error: {$error}\n";
} else {
    $data = json_decode($response, true);
    if (isset($data['models'])) {
        foreach ($data['models'] as $model) {
            echo $model['name'] . " - " . $model['displayName'] . "\n";
        }
    } else {
        echo "Error response:\n";
        print_r($data);
    }
}
