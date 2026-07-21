<?php
$config = require __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/services/ExternalApiService.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("DB Connection failed: " . $db->connect_error);
}

$apiService = new ExternalApiService($db);
$token = $apiService->getBearerToken();

echo "=== 3. LATE APPROVALS ===\n";
$ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/late-approvals/298780");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);
$d = json_decode($res, true);
$lates = $d['lateLogList'] ?? [];
if (!empty($lates)) {
    print_r($lates[0]);
} else {
    echo "No late approvals found.\n";
}

echo "\n=== 4. LEAVE APPROVERS ===\n";
$ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/remaining-leave/approvers/298780");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);
$d = json_decode($res, true);
$leaves = $d['leaveApplications'] ?? [];
if (!empty($leaves)) {
    print_r($leaves[0]);
} else {
    echo "No approver leaves found.\n";
}
