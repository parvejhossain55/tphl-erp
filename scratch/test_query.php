<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $db->query("
    SELECT ppi.item_name, ppid.*
    FROM project_phase_items ppi
    INNER JOIN project_phase_item_details ppid ON ppid.phase_item_id = ppi.id
    WHERE ppi.phase_id = 1
");
print_r($res->fetch_all(MYSQLI_ASSOC));
