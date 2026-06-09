<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// 1. Weekly progress items sum
$weeklySql = "
    SELECT SUM(pwpi.achieved_qty) AS sum_weekly
    FROM phase_weekly_progress_items pwpi
    INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
    INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
    INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
    WHERE pmt.project_id = 13
      AND pwp.qc_status = 'Approved'
      AND pwp.pm_status = 'Approved'
";
$r = $db->query($weeklySql);
$w = $r->fetch_assoc();
echo "Weekly Approved Qty: " . ($w['sum_weekly'] ?? 0) . "\n";

// 2. Daily logs sum
$dailySql = "
    SELECT SUM(dwil.qty_done_today) AS sum_daily
    FROM daily_work_item_logs dwil
    INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
    INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
    LEFT JOIN phase_monthly_target_items pmti ON pmti.id = dwil.target_item_id
    WHERE dpl.project_id = 13
      AND dpl.qc_status = 'Approved'
      AND dpl.pm_status = 'Approved'
      AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
";
$r = $db->query($dailySql);
$d = $r->fetch_assoc();
echo "Daily Approved Qty: " . ($d['sum_daily'] ?? 0) . "\n";

// 3. Let's see if there are other daily logs not associated with a week, or with draft weeks
$dailySql2 = "
    SELECT SUM(dwil.qty_done_today) AS sum_daily_any
    FROM daily_work_item_logs dwil
    INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
    WHERE dpl.project_id = 13
      AND dpl.qc_status = 'Approved'
      AND dpl.pm_status = 'Approved'
";
$r = $db->query($dailySql2);
$d2 = $r->fetch_assoc();
echo "Total Daily Approved Qty: " . ($d2['sum_daily_any'] ?? 0) . "\n";
