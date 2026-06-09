<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$roleFilter = '1=1';

$sql = "
SELECT 
    pp.phase_name,
    AVG(pp.weight_percentage) AS phase_weight,
    (
        COALESCE((
            SELECT SUM(iei.estimated_qty * iei.unit_cost_approx)
            FROM initial_estimate_items iei
            INNER JOIN initial_estimate_budgets ieb ON ieb.id = iei.budget_id
            INNER JOIN project_phase_items ppi ON ppi.id = iei.phase_item_id
            INNER JOIN projects p2 ON p2.id = ieb.project_id
            WHERE ieb.status IN ('approved', 'pm_approved') 
              AND p2.deleted_at IS NULL AND (1=1)
              AND ppi.phase_id IN (
                  SELECT subpp.id FROM project_phases subpp WHERE subpp.phase_name = pp.phase_name
              )
        ), 0)
        +
        COALESCE((
            SELECT SUM(iel.rate_per_sqft * iel.area_sqft)
            FROM initial_estimate_labors iel
            INNER JOIN initial_estimate_budgets ieb ON ieb.id = iel.budget_id
            INNER JOIN project_phase_items ppi ON ppi.id = iel.phase_item_id
            INNER JOIN projects p2 ON p2.id = ieb.project_id
            WHERE ieb.status IN ('approved', 'pm_approved')
              AND p2.deleted_at IS NULL AND (1=1)
              AND ppi.phase_id IN (
                  SELECT subpp.id FROM project_phases subpp WHERE subpp.phase_name = pp.phase_name
              )
        ), 0)
    ) AS total_budget
FROM project_phases pp
INNER JOIN projects p ON p.id = pp.project_id
WHERE p.deleted_at IS NULL AND {$roleFilter}
GROUP BY pp.phase_name
ORDER BY MIN(pp.phase_order) ASC
";

$res = $db->query($sql);
if ($res) {
    print_r($res->fetch_all(MYSQLI_ASSOC));
} else {
    echo "Query error: " . $db->error;
}
