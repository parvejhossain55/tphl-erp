<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    $db = new mysqli('localhost', 'root', '', 'tphl_erp');
}

$projectId = 13;

echo "--- Project Drawings Query ---\n";
$sql = "SELECT pd.id,
               pd.design_type_id,
               pd.phase_id,
               pd.design_link,
               pd.version,
               pd.status,
               dt.name AS design_type,
               pp.phase_name,
               pp.weight_percentage AS phase_weight
        FROM project_drawings pd
        LEFT JOIN design_types dt ON dt.id = pd.design_type_id
        LEFT JOIN project_phases pp ON pp.id = pd.phase_id
        WHERE pd.project_id = $projectId";
$res = $db->query($sql);
if ($res) {
    var_dump($res->fetch_all(MYSQLI_ASSOC));
} else {
    echo "Query failed: " . $db->error . "\n";
}

echo "\n--- Phase Items for project 13 ---\n";
$sql2 = "SELECT ppi.*,
                pp.phase_name,
                dt.name AS design_type_name
         FROM project_phase_items ppi
         INNER JOIN project_phases pp ON pp.id = ppi.phase_id
         LEFT JOIN  design_types   dt ON dt.id = ppi.design_type_id
         WHERE pp.project_id = $projectId";
$res2 = $db->query($sql2);
if ($res2) {
    var_dump($res2->fetch_all(MYSQLI_ASSOC));
} else {
    echo "Query 2 failed: " . $db->error . "\n";
}
