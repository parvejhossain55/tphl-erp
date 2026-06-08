<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$date = '2026-06-08';
$projectId = 13;

echo "=== phase_monthly_targets ===\n";
echo "=== basic phase items and progress ===\n";
$res = $db->query("
    SELECT pp.id AS phase_id, pp.actual_progress, pp.planned_progress, pp.weight_percentage,
           ppi.id AS item_id, ppi.item_name, ppi.weight_pct
    FROM project_phases pp
    LEFT JOIN project_phase_items ppi ON ppi.phase_id = pp.id
    WHERE pp.project_id = 13
");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "======================================\n";




$sql = "
    SELECT 
        SUM(
            (
                COALESCE(
                    (
                        SELECT SUM(
                            LEAST(1.0, (
                                SELECT COALESCE(SUM(dwil.qty_done_today), 0)
                                FROM daily_work_item_logs dwil
                                INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                                WHERE dwil.target_item_id = pmti.id
                                  AND dpl.qc_status = 'Approved'
                                  AND dpl.pm_status = 'Approved'
                                  AND dpl.log_date <= '{$date}'
                            ) / NULLIF(pmti.monthly_target_qty, 0)) * pmti.planned_progress_pct
                        )
                        FROM phase_monthly_target_items pmti
                        INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                        WHERE pmt.phase_id = pp.id
                          AND pmt.target_month <= DATE_FORMAT('{$date}', '%Y-%m-01')
                          AND (
                              pmti.work_item_name = ppi.item_name COLLATE utf8mb4_general_ci
                              OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, ' - %')
                              OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, '%')
                          )
                    ),
                    pp.actual_progress
                ) / 100
            ) * COALESCE(ppi.weight_pct, 100) / 100 * COALESCE(pp.weight_percentage, 0)
        ) AS actual_progress_pct,
        
        SUM(
            (
                COALESCE(
                    (
                        SELECT SUM(
                            CASE 
                                WHEN pmt.target_month < DATE_FORMAT('{$date}', '%Y-%m-01') THEN pmti.planned_progress_pct
                                WHEN pmt.target_month = DATE_FORMAT('{$date}', '%Y-%m-01') THEN 
                                    LEAST(1.0, (
                                        CASE 
                                            WHEN DAY('{$date}') <= 7 THEN pmti.week_1_qty
                                            WHEN DAY('{$date}') <= 14 THEN pmti.week_1_qty + pmti.week_2_qty
                                            WHEN DAY('{$date}') <= 21 THEN pmti.week_1_qty + pmti.week_2_qty + pmti.week_3_qty
                                            ELSE pmti.week_1_qty + pmti.week_2_qty + pmti.week_3_qty + pmti.week_4_qty
                                        END
                                    ) / NULLIF(pmti.monthly_target_qty, 0)) * pmti.planned_progress_pct
                                ELSE 0
                            END
                        )
                        FROM phase_monthly_target_items pmti
                        INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                        WHERE pmt.phase_id = pp.id
                          AND pmt.target_month <= DATE_FORMAT('{$date}', '%Y-%m-01')
                          AND (
                              pmti.work_item_name = ppi.item_name COLLATE utf8mb4_general_ci
                              OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, ' - %')
                              OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, '%')
                          )
                    ),
                    pp.planned_progress
                ) / 100
            ) * COALESCE(ppi.weight_pct, 100) / 100 * COALESCE(pp.weight_percentage, 0)
        ) AS planned_progress_pct
    FROM project_phases pp
    LEFT JOIN project_phase_items ppi ON ppi.phase_id = pp.id
    WHERE pp.project_id = {$projectId}
";

$res = $db->query($sql)->fetch_assoc();
print_r($res);
