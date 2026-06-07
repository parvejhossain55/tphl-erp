<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SELECT id, project_id, phase_id, target_id, week_number, overall_progress_pct, total_planned_qty, total_achieved_qty, pm_status, entry_status FROM phase_weekly_progress WHERE project_id = 13");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
$db->close();
