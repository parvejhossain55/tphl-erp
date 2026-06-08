<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$res = $db->query("SELECT id, project_id, phase_name, weight_percentage, actual_progress, planned_progress FROM project_phases WHERE project_id = 13");
$phases = $res->fetch_all(MYSQLI_ASSOC);
echo "=== PHASES FOR PROJECT 13 ===\n";
print_r($phases);
