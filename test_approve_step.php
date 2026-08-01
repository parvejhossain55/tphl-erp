<?php
require_once __DIR__ . '/app/models/AssetManagement.php';

$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
$model = new AssetManagement($db);

// Test approving Step 1 (Operations) for Request ID #1
$res = $model->approveStep(1, 'operations', 'approved', 1, 'Approved by Operations Team');

if ($res) {
    echo "✓ SUCCESS: Request #1 Operations step approved successfully!\n";
} else {
    echo "Failed to approve Operations step.\n";
}
