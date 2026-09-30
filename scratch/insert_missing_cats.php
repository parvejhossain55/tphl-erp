<?php
require 'app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');

$categories = [
    // Construction
    ['name' => 'MS Items', 'type' => 'Construction', 'pr' => 4, 'cs' => 2, 'approval' => 3, 'wo' => 1, 'adv' => 3, 'transport' => 5, 'total' => 18],
    ['name' => 'GI Items', 'type' => 'Construction', 'pr' => 2, 'cs' => 2, 'approval' => 3, 'wo' => 1, 'adv' => 0, 'transport' => 2, 'total' => 10],
    ['name' => 'Safety Item', 'type' => 'Construction', 'pr' => 6, 'cs' => 2, 'approval' => 3, 'wo' => 1, 'adv' => 0, 'transport' => 3, 'total' => 15],
    
    // EME
    ['name' => 'Mechanical Items', 'type' => 'EME', 'pr' => 2, 'cs' => 2, 'approval' => 3, 'wo' => 1, 'adv' => 3, 'transport' => 4, 'total' => 15],
    ['name' => 'Fire Items', 'type' => 'EME', 'pr' => 5, 'cs' => 2, 'approval' => 3, 'wo' => 1, 'adv' => 0, 'transport' => 3, 'total' => 14],
    ['name' => 'Domestic & Power Cable', 'type' => 'EME', 'pr' => 8, 'cs' => 2, 'approval' => 3, 'wo' => 1, 'adv' => 8, 'transport' => 8, 'total' => 30],
    
    // Finishing
    ['name' => 'Door/Frame', 'type' => 'Finishing', 'pr' => 10, 'cs' => 2, 'approval' => 3, 'wo' => 1, 'adv' => 4, 'transport' => 60, 'total' => 80],
    ['name' => 'Hardware Items', 'type' => 'Finishing', 'pr' => 4, 'cs' => 2, 'approval' => 3, 'wo' => 1, 'adv' => 0, 'transport' => 3, 'total' => 13],
    ['name' => 'Lift/Gen/Sub.', 'type' => 'Finishing', 'pr' => 10, 'cs' => 3, 'approval' => 10, 'wo' => 2, 'adv' => 5, 'transport' => 90, 'total' => 120]
];

foreach ($categories as $cat) {
    // Check if exists
    $stmt = $db->prepare("SELECT id FROM con_material_categories WHERE name = ?");
    $stmt->bind_param("s", $cat['name']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        $insert = $db->prepare("INSERT INTO con_material_categories (name, item_type, pr_to_qtn_days, cs_analysis_days, approval_days, wo_po_days, adv_payment_days, transport_days, total_lead_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->bind_param("ssiiiiiii", $cat['name'], $cat['type'], $cat['pr'], $cat['cs'], $cat['approval'], $cat['wo'], $cat['adv'], $cat['transport'], $cat['total']);
        $insert->execute();
        echo "Inserted: {$cat['name']}\n";
    } else {
        echo "Already exists: {$cat['name']}\n";
    }
}
echo "Done.";
