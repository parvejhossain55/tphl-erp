<?php

require_once 'app/config/config.php';
require_once 'app/core/Model.php';
require_once 'app/models/ApprovalRequest.php';

$mysqli = new mysqli("127.0.0.1", "root", "", "tphl_db");
$model = new ApprovalRequest($mysqli);

try {
    $metrics = $model->getApprovalDashboardMetrics();
    $history = $model->getAllApprovalHistory([]);
    echo "Metrics Loaded Successfully:\n";
    print_r($metrics);
    echo "History Loaded Successfully: " . count($history) . " records.\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
