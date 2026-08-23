<?php
require 'app/config/config.php';
require 'app/services/ExternalApiService.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$api = new ExternalApiService($db);
$token = $api->getBearerToken();

$ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/employee?order=asc&pageNumber=0&perPage=500&status=1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resEmp = curl_exec($ch);
curl_close($ch);

$dEmp = json_decode($resEmp, true);
$employees = $dEmp['employees'] ?? [];
echo "Tipsoi Employees:\n";
foreach($employees as $e) {
    if(stripos($e['employeeName'], 'Ejaj') !== false || stripos($e['employeeName'], 'Akramul') !== false || stripos($e['employeeName'], 'Shuvo') !== false) {
        echo $e['employeeName'] . ' - ' . $e['email'] . "\n";
    }
}

echo "\nERP Employees:\n";
$res = $db->query("SELECT full_name, email FROM employees WHERE full_name LIKE '%Ejaj%' OR full_name LIKE '%Akramul%' OR full_name LIKE '%Shuvo%'");
while($row = $res->fetch_assoc()) {
    echo $row['full_name'] . ' - ' . $row['email'] . "\n";
}
