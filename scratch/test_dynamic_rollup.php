<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SELECT id, name FROM projects WHERE workflow_version = 'phase_based'");
$projects = $res->fetch_all(MYSQLI_ASSOC);
echo "=== PHASE BASED PROJECTS ===\n";
print_r($projects);

foreach ($projects as $pr) {
    $pid = $pr['id'];
    $resPh = $db->query("SELECT id, phase_name, weight_percentage, actual_progress, planned_progress FROM project_phases WHERE project_id = $pid");
    echo "Phases for project {$pid} ({$pr['name']}):\n";
    print_r($resPh->fetch_all(MYSQLI_ASSOC));
}
?>
