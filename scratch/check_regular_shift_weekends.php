<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/helpers/helper.php';
require_once __DIR__ . '/../app/services/ExternalApiService.php';

$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$api = new ExternalApiService($db);

$token = $api->getBearerToken();

$sunTimeStart = strtotime('2026-07-12 00:00:00') * 1000;
$sunTimeEnd = strtotime('2026-07-12 23:59:59') * 1000;
$urlSun = "https://app.tipsoi.ai/inovace-client/api/v1/attendance?" . http_build_query([
    'officeId' => 4699,
    'order' => 'asc',
    'pageNumber' => 0,
    'perPage' => 10,
    'reportType' => 'department',
    'sortKey' => 'reportPosition',
    'status' => 1,
    'from' => $sunTimeStart,
    'to' => $sunTimeEnd
]);

$ch = curl_init($urlSun);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resSun = json_decode(curl_exec($ch), true);
curl_close($ch);

header('Content-Type: text/plain');
if ($resSun) {
    $employees = $resSun['data'] ?? $resSun['employees'] ?? $resSun['content'] ?? [];
    if (!empty($employees)) {
        $first = reset($employees);
        print_r($first);
    } else {
        echo "No employees returned in resSun: " . json_encode($resSun) . "\n";
    }
} else {
    echo "No response.\n";
}
