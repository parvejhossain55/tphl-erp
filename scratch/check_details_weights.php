<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("
    SELECT ppi.phase_id, ppi.id AS phase_item_id, ppi.weight_pct AS item_weight,
           ppid.id AS detail_id, ppid.work_type_id, ppid.weight_pct AS detail_weight
    FROM project_phase_items ppi
    INNER JOIN project_phase_item_details ppid ON ppid.phase_item_id = ppi.id
    INNER JOIN project_phases pp ON pp.id = ppi.phase_id
    WHERE pp.project_id = 13
");
print_r($res->fetch_all(MYSQLI_ASSOC));
