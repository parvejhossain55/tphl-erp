<?php
require_once __DIR__ . '/app/config/config.php';
// We need to just require the API Service or do a curl manually
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

if (!$token) {
    die("No token");
}

$url = $baseUrl . 'application/pending-list?page=0&count=100&status=0';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);

echo $res;

