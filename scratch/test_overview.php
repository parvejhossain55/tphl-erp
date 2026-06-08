<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$roleFilter = "1=1";
$sql = "
    SELECT  p.id,
            p.name,
            p.workflow_version,
            COALESCE(prog.progress_pct, 0)    AS progress_pct,
            COALESCE(prog.planned_progress_pct, 0) AS planned_progress_pct
    FROM projects p
    LEFT JOIN (
        SELECT 
            pp.project_id,
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
                                ) / NULLIF(pmti.monthly_target_qty, 0)) * pmti.planned_progress_pct
                            )
                            FROM phase_monthly_target_items pmti
                            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                            WHERE pmt.phase_id = pp.id
                              AND (
                                  pmti.work_item_name = ppi.item_name COLLATE utf8mb4_general_ci
                                  OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, ' - %')
                                  OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, '%')
                              )
                        ),
                        pp.actual_progress
                    ) / 100
                ) * COALESCE(ppi.weight_pct, 100) / 100 * COALESCE(pp.weight_percentage, 0)
            ) AS progress_pct,
            
            SUM(
                (
                    COALESCE(
                        (
                            SELECT SUM(pmti.planned_progress_pct)
                            FROM phase_monthly_target_items pmti
                            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                            WHERE pmt.phase_id = pp.id
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
        GROUP BY pp.project_id
    ) prog ON prog.project_id = p.id
    WHERE p.deleted_at IS NULL AND {$roleFilter}
    ORDER BY p.id DESC
";

$res = $db->query($sql);
if ($res) {
    echo "Success!\n";
} else {
    echo "Error: " . $db->error . "\n";
}
