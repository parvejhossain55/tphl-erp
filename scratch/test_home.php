<?php

require_once 'app/config/config.php';
require_once 'app/core/Model.php';
require_once 'app/models/ApprovalRequest.php';

$mysqli = new mysqli("127.0.0.1", "root", "", "tphl_db");
$model = new ApprovalRequest($mysqli);

try {
    $res = $model->getPendingForRole(['management', 'sales'], 10);
    echo "Success! Found " . count($res) . " pending requests for role.\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
