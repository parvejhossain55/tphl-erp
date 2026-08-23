<?php
require 'app/config/config.php';
require 'app/services/ExternalApiService.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$api = new ExternalApiService($db);
$token = $api->getBearerToken();

$ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/employee?order=asc&pageNumber=0&perPage=5&status=1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resEmp = curl_exec($ch);
curl_close($ch);

$dEmp = json_decode($resEmp, true);
$emps = $dEmp['employees'] ?? [];
echo "Count: " . count($emps) . "\n";
print_r(array_slice($emps, 0, 1));
