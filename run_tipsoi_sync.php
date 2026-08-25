<?php
$config = require 'app/config/config.php';
require_once 'app/services/ExternalApiService.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$serv = new ExternalApiService($db);
$token = $serv->getBearerToken();

$url = "https://app.tipsoi.ai/inovace-client/api/v1/employee?order=asc&pageNumber=0&perPage=2000&status=1";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Accept: application/json"]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);
$employees = $data['data'] ?? $data['employees'] ?? $data['content'] ?? $data;

// Auto-sync joining_date for local employees based on Tipsoi startDate
if (is_array($employees)) {
    $updateStmt = $db->prepare("UPDATE employees SET joining_date = ? WHERE employee_id = ?");
    if ($updateStmt) {
        foreach ($employees as $extEmp) {
            $officeId = trim((string)($extEmp['employeeOfficeId'] ?? $extEmp['employeeId'] ?? ''));
            if ($officeId !== '' && !empty($extEmp['startDate'])) {
                $parsedDate = date('Y-m-d', strtotime($extEmp['startDate']));
                if ($parsedDate && $parsedDate !== '1970-01-01') {
                    $updateStmt->bind_param("ss", $parsedDate, $officeId);
                    $updateStmt->execute();
                }
            }
        }
        $updateStmt->close();
    }
}
echo "Script finished.";
