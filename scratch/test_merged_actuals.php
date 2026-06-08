<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$idsList = '13';

// 1. Approved weekly progress items
$weeklyActRes = $db->query("
    SELECT pmti.phase_item_id, pmti.work_type_id, pmt.project_id,
           SUM(pwpi.achieved_qty) AS total_actual_qty
    FROM phase_weekly_progress_items pwpi
    INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
    INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
    INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
    WHERE pmt.project_id IN ($idsList)
      AND pwp.qc_status = 'Approved'
      AND pwp.pm_status = 'Approved'
    GROUP BY pmt.project_id, pmti.phase_item_id, pmti.work_type_id
");
$weeklyActuals = $weeklyActRes ? $weeklyActRes->fetch_all(MYSQLI_ASSOC) : [];
echo "Weekly Approved Actuals:\n";
print_r($weeklyActuals);

// 2. Approved daily logs for non-approved weeks
$dailyActRes = $db->query("
    SELECT pmti.phase_item_id, pmti.work_type_id, dpl.project_id,
           SUM(dwil.qty_done_today) AS total_actual_qty
    FROM daily_work_item_logs dwil
    INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
    INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
    LEFT JOIN phase_monthly_target_items pmti ON pmti.id = dwil.target_item_id
    WHERE dpl.project_id IN ($idsList)
      AND dpl.qc_status = 'Approved'
      AND dpl.pm_status = 'Approved'
      AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
    GROUP BY dpl.project_id, pmti.phase_item_id, pmti.work_type_id
");
$dailyActuals = $dailyActRes ? $dailyActRes->fetch_all(MYSQLI_ASSOC) : [];
echo "\nDaily Approved Actuals for Pending/Draft weeks:\n";
print_r($dailyActuals);

// Merge
$actualsMap = [];
foreach ($weeklyActuals as $q) {
    $pid = (int)$q['project_id'];
    $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
    $actualsMap[$pid][$key] = ($actualsMap[$pid][$key] ?? 0.0) + (float)$q['total_actual_qty'];
}
foreach ($dailyActuals as $q) {
    $pid = (int)$q['project_id'];
    if ($q['phase_item_id'] !== null) {
        $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
        $actualsMap[$pid][$key] = ($actualsMap[$pid][$key] ?? 0.0) + (float)$q['total_actual_qty'];
    }
}
echo "\nMerged Actuals Map:\n";
print_r($actualsMap);
