<?php
require 'app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');

require 'app/services/ExternalApiService.php';
$api = new ExternalApiService($db);
$token = $api->getBearerToken();

$ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/leave-management/leave-history?page=0&count=5&status=0");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);
print_r(array_slice($data['leaveLogList'] ?? [], 0, 2));

// Test manual entry pending
$ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/employee/profile/manual/approver/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);
$data = json_decode($res, true);
echo "\nManual Entry:\n";
print_r(array_slice($data['manualAttendances'] ?? $data ?? [], 0, 2));

