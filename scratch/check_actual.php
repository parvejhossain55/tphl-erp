<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== DAILY PROGRESS LOGS FOR PROJECT 13 ===\n";
$logsRes = $db->query("SELECT id, log_date, qc_status, pm_status FROM daily_progress_logs WHERE project_id = 13");
$logs = $logsRes ? $logsRes->fetch_all(MYSQLI_ASSOC) : [];
print_r($logs);

echo "\n=== DAILY WORK ITEM LOGS FOR PROJECT 13 ===\n";
$itemLogsRes = $db->query("
    SELECT dwil.*, dpl.qc_status, dpl.pm_status, dpl.log_date 
    FROM daily_work_item_logs dwil
    INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
    WHERE dpl.project_id = 13
");
$itemLogs = $itemLogsRes ? $itemLogsRes->fetch_all(MYSQLI_ASSOC) : [];
print_r($itemLogs);

echo "\n=== PROJECT PHASE ITEM DETAILS FOR phase_item_id = 2, work_type_id = 2 ===\n";
$ppidRes = $db->query("SELECT * FROM project_phase_item_details WHERE phase_item_id = 2 AND work_type_id = 2");
print_r($ppidRes->fetch_all(MYSQLI_ASSOC));


