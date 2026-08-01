<?php
require_once __DIR__ . '/app/models/AssetManagement.php';

$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
$model = new AssetManagement($db);

$res = $model->addProcurementCategory("IT Security & Accessories");
if ($res) {
    echo "✓ SUCCESS: Category 'IT Security & Accessories' added successfully!\n";
} else {
    echo "Failed to add category.\n";
}
