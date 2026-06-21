<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) die($db->connect_error);

echo "=== phase_monthly_target_items ===\n";
$res = $db->query("SELECT id, target_id, work_item_name, unit FROM phase_monthly_target_items");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>

