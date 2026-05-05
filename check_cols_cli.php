<?php
require_once __DIR__ . '/app/core/Database.php';
$db = (new Database())->getConnection();

$r = $db->query('SHOW COLUMNS FROM projects');
$cols = [];
while ($row = $r->fetch_assoc()) {
    $cols[] = $row['Field'];
}

$need = [
    'swimming_pool', 'pm_id', 'site_engineer_id', 'landman_id',
    'struct_engineer_id', 'draftsman_id', 'architect_id', 'estimator_id',
    'qc_id', 'operator_id', 'service_charge_per_emi', 'floor_wise_unit_meta',
    'project_map_location', 'project_sale_status', 'slug'
];

foreach ($need as $c) {
    echo (in_array($c, $cols) ? 'OK' : 'MISSING') . ': ' . $c . "\n";
}

// Also test prepare
$sql = "INSERT INTO projects (name, slug, location_id, community_id, building_type, total_share, unit_per_floor, floor_wise_unit_meta, types, image, image2, image3, front_road, price_range, hover_text, plot_size, plot_no, block_no, brochure_link, description, apartment_count, amenities_details, amenities_image, map_image, map_link, project_map_location, passenger_lift, cargo_lift, created_by, rooftop_gardening, swimming_pool, convention, electricity_backup, car_parking, project_sale_status, service_charge_per_emi, pm_id, site_engineer_id, landman_id, struct_engineer_id, draftsman_id, architect_id, estimator_id, qc_id, operator_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
$stmt = $db->prepare($sql);
if (!$stmt) {
    echo "PREPARE FAILED: " . $db->error . "\n";
} else {
    echo "PREPARE OK: all 45 columns exist in table\n";
}
