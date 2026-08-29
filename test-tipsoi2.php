<?php
$config = require __DIR__ . '/app/config/config.php';
$email = $config['tipsoi_email'];
$password = $config['tipsoi_password'];
$baseUrl = $config['tipsoi_base_url'];

$loginUrl = $baseUrl . 'auth/external-sync/sign-in';
$payload = json_encode(['email' => $email, 'password' => $password]);
$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($payload)]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);
$token = json_decode($res, true)['token'] ?? null;

if (!$token) { die("No token"); }

$urls = [
    $baseUrl . 'application/pending-list?type=LEAVE',
    $baseUrl . 'application/pending-list?type=LEAVE,LATE',
    $baseUrl . 'application/pending-list?type=LEAVE,LATE,MANUAL_ENTRY,MISSING_PUNCH',
    'https://app.tipsoi.ai/inovace-client/api/v1/application/pending-list?type=LEAVE'
];

foreach ($urls as $url) {
    echo "Trying $url\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "Code: $httpCode\n$res\n\n";
}

