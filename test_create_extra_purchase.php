<?php
require_once __DIR__ . '/app/models/AssetManagement.php';

$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
$model = new AssetManagement($db);

$testData = [
    'project_id' => 46, // The Premium Green Heaven
    'asset_location' => 'Corporate Office',
    'assigned_to' => null,
    'item_category_id' => 1,
    'item_name' => 'Dell XPS 15 Laptop for IT Dept',
    'quantity' => 2,
    'unit_price' => 150000.00,
    'justification' => 'Urgent procurement for senior AI developer',
    'attachment' => null,
    'requested_by' => 1
];

$id = $model->createExtraPurchase($testData);

if ($id) {
    echo "✓ SUCCESS: Created Office Procurement Request ID #{$id} successfully!\n";
} else {
    echo "Failed to create procurement request.\n";
}
