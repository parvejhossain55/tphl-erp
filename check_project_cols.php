<?php
require_once __DIR__ . '/app/core/Database.php';
$db = (new Database())->getConnection();

// 1. Get all actual columns in projects table
$result = $db->query("SHOW COLUMNS FROM projects");
$existingCols = [];
while ($row = $result->fetch_assoc()) {
    $existingCols[] = $row['Field'];
}

// 2. Columns the model tries to INSERT
$modelCols = [
    'name', 'slug', 'location_id', 'community_id', 'building_type',
    'total_share', 'unit_per_floor', 'floor_wise_unit_meta', 'types', 'image',
    'image2', 'image3', 'front_road', 'price_range', 'hover_text',
    'plot_size', 'plot_no', 'block_no', 'brochure_link', 'description',
    'apartment_count', 'amenities_details', 'amenities_image', 'map_image', 'map_link',
    'project_map_location', 'passenger_lift', 'cargo_lift', 'created_by', 'rooftop_gardening',
    'swimming_pool', 'convention', 'electricity_backup', 'car_parking', 'project_sale_status',
    'service_charge_per_emi',
    'pm_id', 'site_engineer_id', 'landman_id', 'struct_engineer_id',
    'draftsman_id', 'architect_id', 'estimator_id', 'qc_id', 'operator_id'
];

echo "<h2>Project Table Column Check</h2>";
echo "<h3 style='color:red'>❌ Missing Columns (in model but NOT in table):</h3><ul>";
$missing = [];
foreach ($modelCols as $col) {
    if (!in_array($col, $existingCols)) {
        $missing[] = $col;
        echo "<li style='color:red;font-weight:bold'>$col</li>";
    }
}
if (empty($missing)) echo "<li style='color:green'>✅ None missing — all columns exist!</li>";
echo "</ul>";

echo "<h3>✅ All Existing Columns in projects table:</h3><ul>";
foreach ($existingCols as $col) {
    echo "<li>" . (in_array($col, $modelCols) ? "✅" : "—") . " $col</li>";
}
echo "</ul>";

// 3. Try a minimal test insert
echo "<h3>🧪 Test INSERT (will rollback):</h3>";
$db->begin_transaction();
$sql = "INSERT INTO projects (name, slug, location_id, community_id, building_type, total_share, unit_per_floor, floor_wise_unit_meta, types, image, image2, image3, front_road, price_range, hover_text, plot_size, plot_no, block_no, brochure_link, description, apartment_count, amenities_details, amenities_image, map_image, map_link, project_map_location, passenger_lift, cargo_lift, created_by, rooftop_gardening, swimming_pool, convention, electricity_backup, car_parking, project_sale_status, service_charge_per_emi, pm_id, site_engineer_id, landman_id, struct_engineer_id, draftsman_id, architect_id, estimator_id, qc_id, operator_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
$stmt = $db->prepare($sql);
if (!$stmt) {
    echo "<p style='color:red'>❌ Prepare Failed: " . $db->error . "</p>";
} else {
    echo "<p style='color:green'>✅ SQL Prepare succeeded — all 45 columns exist</p>";
    $db->rollback();
}
?>
