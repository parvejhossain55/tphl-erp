<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Con_InitialEstimate.php';
$model = new Con_InitialEstimate($db);

$items = $model->getItemsByBudget(2);
echo "Successfully fetched " . count($items) . " items for budget 2.\n";
if (!empty($items)) {
    echo "First item work_type_label: " . ($items[0]['work_type_label'] ?? 'NULL') . "\n";
    echo "First item design_type_name: " . ($items[0]['design_type_name'] ?? 'NULL') . "\n";
    echo "First item phase_name: " . ($items[0]['phase_name'] ?? 'NULL') . "\n";
    echo "First item work_type_id: " . ($items[0]['work_type_id'] ?? 'NULL') . "\n";
    echo "First item work_type_name: " . ($items[0]['work_type_name'] ?? 'NULL') . "\n";
}

$labors = $model->getLaborsByBudget(2);
echo "\nSuccessfully fetched " . count($labors) . " labors for budget 2.\n";
if (!empty($labors)) {
    echo "First labor work_type_label: " . ($labors[0]['work_type_label'] ?? 'NULL') . "\n";
    echo "First labor design_type_name: " . ($labors[0]['design_type_name'] ?? 'NULL') . "\n";
    echo "First labor phase_name: " . ($labors[0]['phase_name'] ?? 'NULL') . "\n";
    echo "First labor work_type_id: " . ($labors[0]['work_type_id'] ?? 'NULL') . "\n";
    echo "First labor work_type_name: " . ($labors[0]['work_type_name'] ?? 'NULL') . "\n";
}

