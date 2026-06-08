<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$pid = 13;

echo "=== PROJECT PHASES ===\n";
$res = $db->query("SELECT id, phase_name, weight_percentage FROM project_phases WHERE project_id = $pid");
foreach($res->fetch_all(MYSQLI_ASSOC) as $r) {
    echo "Phase ID: {$r['id']}, Name: {$r['phase_name']}, Weight: {$r['weight_percentage']}%\n";
}

echo "\n=== MONTHLY TARGETS ===\n";
$res = $db->query("SELECT id, phase_id, target_month, target_year FROM phase_monthly_targets WHERE project_id = $pid");
foreach($res->fetch_all(MYSQLI_ASSOC) as $r) {
    echo "Target ID: {$r['id']}, Phase ID: {$r['phase_id']}, Month: {$r['target_month']}, Year: {$r['target_year']}\n";
}

echo "\n=== MONTHLY TARGET ITEMS ===\n";
$res = $db->query("SELECT pmti.id, pmti.target_id, pmti.phase_item_id, pmti.work_item_name, pmti.work_type_id, pmti.monthly_target_qty, pmti.planned_progress_pct FROM phase_monthly_target_items pmti INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id WHERE pmt.project_id = $pid");
foreach($res->fetch_all(MYSQLI_ASSOC) as $r) {
    echo "Item ID: {$r['id']}, Target ID: {$r['target_id']}, Phase Item ID: {$r['phase_item_id']}, Name: {$r['work_item_name']}, Type: {$r['work_type_id']}, Qty: {$r['monthly_target_qty']}, Pct: {$r['planned_progress_pct']}\n";
}

echo "\n=== WEEKLY PROGRESS RECORD ===\n";
$res = $db->query("SELECT id, target_id, week_number, week_start_date, week_end_date, qc_status, pm_status FROM phase_weekly_progress WHERE project_id = $pid");
foreach($res->fetch_all(MYSQLI_ASSOC) as $r) {
    echo "Weekly Prog ID: {$r['id']}, Target ID: {$r['target_id']}, Week: {$r['week_number']}, Range: {$r['week_start_date']} to {$r['week_end_date']}, QC: {$r['qc_status']}, PM: {$r['pm_status']}\n";
}

echo "\n=== WEEKLY PROGRESS ITEMS ===\n";
$res = $db->query("SELECT pwpi.id, pwpi.weekly_progress_id, pwpi.target_item_id, pwpi.achieved_qty FROM phase_weekly_progress_items pwpi INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id WHERE pwp.project_id = $pid");
foreach($res->fetch_all(MYSQLI_ASSOC) as $r) {
    echo "Prog Item ID: {$r['id']}, Weekly Prog ID: {$r['weekly_progress_id']}, Target Item ID: {$r['target_item_id']}, Achieved Qty: {$r['achieved_qty']}\n";
}
