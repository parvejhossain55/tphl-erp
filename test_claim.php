<?php
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Model.php';
require_once __DIR__ . '/app/models/ApprovalRequest.php';

$db = Database::getInstance();
$arModel = new ApprovalRequest($db);
$filters = [
    'status'            => 'all',
    'department'        => '',
    'source_table'      => 'project_incentive_claims',
    'search'            => '',
    'user_id'           => 128,
    'role_id'           => 20,
    'secondary_role_id' => 9,
    'role_name'         => 'operation head'
];
$history = $arModel->getAllApprovalHistory($filters, 10, 0);
foreach ($history as $h) {
    if ((int)$h['id'] === 3867) {
        echo "HISTORY 3867:\n";
        print_r($h);
    }
}
