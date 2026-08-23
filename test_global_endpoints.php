<?php
require 'app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
require 'app/services/ExternalApiService.php';
$api = new ExternalApiService($db);
$token = $api->getBearerToken();

$urls = [
    "Late" => "https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/late-approvals?page=0&count=100&status=0",
    "Manual" => "https://app.tipsoi.ai/inovace-client/api/v1/employee/profile/manual?page=0&count=100&status=0",
    "Movement" => "https://app.tipsoi.ai/inovace-client/api/v1/employee-movement?page=0&count=100&status=0"
];

foreach ($urls as $name => $url) {
    echo "\n--- $name ---\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    curl_close($ch);
    echo substr($res, 0, 500) . "\n";
}
