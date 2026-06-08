<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== PROJECT PHASES ===\n";
$res = $db->query("SELECT * FROM project_phases WHERE project_id = 13");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n=== PROJECT PHASE ITEMS ===\n";
$res = $db->query("
    SELECT ppi.*, pp.phase_name 
    FROM project_phase_items ppi
    JOIN project_phases pp ON pp.id = ppi.phase_id
    WHERE pp.project_id = 13
");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n=== PROJECT PHASE ITEM DETAILS ===\n";
$res = $db->query("
    SELECT ppid.*, ppi.item_name, pp.phase_name 
    FROM project_phase_item_details ppid
    JOIN project_phase_items ppi ON ppi.id = ppid.phase_item_id
    JOIN project_phases pp ON pp.id = ppi.phase_id
    WHERE pp.project_id = 13
");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n=== PHASE MONTHLY TARGET ITEMS ===\n";
$res = $db->query("
    SELECT pmti.*, pmt.target_month, pp.phase_name
    FROM phase_monthly_target_items pmti
    JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
    JOIN project_phases pp ON pp.id = pmt.phase_id
    WHERE pmt.project_id = 13
");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n=== DAILY PROGRESS LOGS & ITEM LOGS ===\n";
$res = $db->query("
    SELECT dwil.*, dpl.log_date, dpl.qc_status, dpl.pm_status
    FROM daily_work_item_logs dwil
    JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
    WHERE dpl.project_id = 13
");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
