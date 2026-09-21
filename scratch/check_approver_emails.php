<?php
require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../app/config/config.php';
require __DIR__ . '/../app/services/ExternalApiService.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$api = new ExternalApiService($db);
$token = $api->getBearerToken();

$ch = curl_init($config['tipsoi_base_url'] . "employee?order=asc&pageNumber=0&perPage=2000&status=1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resEmp = curl_exec($ch);
curl_close($ch);
$dEmp = json_decode($resEmp, true);
$externalEmployees = $dEmp['data'] ?? $dEmp['employees'] ?? $dEmp['content'] ?? $dEmp ?? [];

$tipsoiEmployeesMap = [];
$tipsoiByName = [];
foreach ($externalEmployees as $emp) {
    $tid = $emp['employeeId'] ?? $emp['id'] ?? null;
    if ($tid) {
        $tipsoiEmployeesMap[$tid] = $emp;
    }
    $name = strtolower(trim($emp['employeeName'] ?? ''));
    if ($name) {
        $tipsoiByName[$name] = $emp;
    }
}

// Check our approvers from pending_results.json
$pendingData = json_decode(file_get_contents(__DIR__ . '/pending_results.json'), true);
$approversSeen = [];
foreach ($pendingData as $p) {
    $app = trim($p['currently_pending_with']);
    if ($app) $approversSeen[$app] = true;
}

echo "Checking email availability for approvers:\n\n";

$localQuery = $db->query("SELECT employee_id, email, full_name FROM employees");
$localByName = [];
while ($row = $localQuery->fetch_assoc()) {
    $n = strtolower(trim($row['full_name'] ?? ''));
    if ($n) $localByName[$n] = $row['email'];
}

foreach (array_keys($approversSeen) as $approverName) {
    $norm = strtolower($approverName);
    $tipsoiEmp = $tipsoiByName[$norm] ?? null;
    $tipsoiEmail = $tipsoiEmp ? ($tipsoiEmp['email'] ?? 'EMPTY') : 'NOT_FOUND_BY_NAME';
    $localEmail = $localByName[$norm] ?? 'NOT_FOUND_IN_LOCAL';

    printf("%-40s | Tipsoi Email: %-25s | Local DB Email: %-25s\n", $approverName, $tipsoiEmail, $localEmail);
}
