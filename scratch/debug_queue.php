<?php

require_once 'app/config/config.php';
require_once 'app/models/IncentiveModel.php';

$mysqli = new mysqli("127.0.0.1", "root", "", "tphl_db");
$model = new IncentiveModel($mysqli);

echo "=== Calling getSalesClaimsQueue() ===\n";
try {
    $res = $model->getSalesClaimsQueue();
    echo "Count: " . count($res) . "\n";
    if (count($res) > 0) {
        print_r($res[0]);
    } else {
        echo "Returned empty array!\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
