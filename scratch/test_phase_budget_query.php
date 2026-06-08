<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$pid = 13;

$sql = "
SELECT 
    pp.id AS phase_id,
    pp.phase_name,
    pp.weight_percentage AS phase_weight,
    (
        COALESCE((
            SELECT SUM(iei.estimated_qty * iei.unit_cost_approx)
            FROM initial_estimate_items iei
            INNER JOIN initial_estimate_budgets ieb ON ieb.id = iei.budget_id
            INNER JOIN project_phase_items ppi ON ppi.id = iei.phase_item_id
            WHERE ieb.status IN ('approved', 'pm_approved') AND ppi.phase_id = pp.id
        ), 0)
        +
        COALESCE((
            SELECT SUM(iel.rate_per_sqft * iel.area_sqft)
            FROM initial_estimate_labors iel
            INNER JOIN initial_estimate_budgets ieb ON ieb.id = iel.budget_id
            INNER JOIN project_phase_items ppi ON ppi.id = iel.phase_item_id
            WHERE ieb.status IN ('approved', 'pm_approved') AND ppi.phase_id = pp.id
        ), 0)
    ) AS total_budget
FROM project_phases pp
WHERE pp.project_id = ?
ORDER BY pp.phase_order ASC
";

$stmt = $db->prepare($sql);
$stmt->bind_param('i', $pid);
$stmt->execute();
$res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
print_r($res);
