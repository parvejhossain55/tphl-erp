<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$id = 2;
$res = $db->query("SELECT * FROM initial_estimate_budgets WHERE id = $id");
$budget = $res->fetch_assoc();
if (!$budget) {
    die("Budget 2 not found in local database.\n");
}
$projectId = $budget['project_id'];
echo "Budget 2 belongs to Project ID: $projectId\n";

$stmt = $db->prepare("
    SELECT pmt.id,
           pmt.target_month_number AS month,
           pmt.target_year AS year,
           pp.phase_name AS design_type_name,
           pmt.responsible_team AS work_description,
           pmt.total_target_weight_pct AS planned_quantity,
           0.00 AS estimated_cost,
           pmt.planning_status AS approved_status,
           u.full_name AS created_by_name
    FROM phase_monthly_targets pmt
    INNER JOIN project_phases pp ON pp.id = pmt.phase_id
    LEFT JOIN users u ON u.id = pmt.created_by
    WHERE pmt.project_id = ?
    ORDER BY pmt.target_year DESC, pmt.target_month_number DESC
");
$stmt->bind_param('i', $projectId);
$stmt->execute();
$workingPlans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
echo "Successfully loaded " . count($workingPlans) . " monthly plans!\n";
foreach ($workingPlans as $wp) {
    echo "Month/Year: {$wp['month']}/{$wp['year']} | Phase: {$wp['design_type_name']} | Status: {$wp['approved_status']}\n";
}
