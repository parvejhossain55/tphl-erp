<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) die($db->connect_error);

echo "=== phase_weekly_progress ===\n";
$res = $db->query("SELECT * FROM phase_weekly_progress WHERE target_id = 1");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n=== phase_weekly_progress_items ===\n";
$res = $db->query("SELECT wpi.*, ti.work_item_name FROM phase_weekly_progress_items wpi JOIN phase_monthly_target_items ti ON ti.id = wpi.target_item_id WHERE wpi.weekly_progress_id IN (SELECT id FROM phase_weekly_progress WHERE target_id = 1)");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
