<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
require_once __DIR__ . '/app/helpers/helper.php';
require_once __DIR__ . '/app/core/Model.php';
require_once __DIR__ . '/app/models/ApprovalRequest.php';

$arFile = __DIR__ . '/app/models/ApprovalRequest.php';
$arContent = file_get_contents($arFile);
$needle = '$stmt = $this->db->prepare($sql);';
$debug = 'file_put_contents(__DIR__ . "/debug_sql.txt", $sql);' . "\n        " . '$stmt = $this->db->prepare($sql);';
$arContent = str_replace($needle, $debug, $arContent);
file_put_contents($arFile, $arContent);

$model = new ApprovalRequest($conn);

$filters = [
    'status' => '', // Default: My Actionable Pending
    'department' => '',
    'source_table' => '',
    'role_id' => 3,
    'user_id' => 3,
    'role_name' => 'management'
];

$history = $model->getAllApprovalHistory($filters, 25, 0);

echo file_get_contents(__DIR__ . "/debug_sql.txt");

// Revert debug
$arContent = file_get_contents($arFile);
$arContent = str_replace($debug, $needle, $arContent);
file_put_contents($arFile, $arContent);
unlink(__DIR__ . "/debug_sql.txt");

