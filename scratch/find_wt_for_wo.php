<?php
$root = dirname(__DIR__);
$config = require $root . '/app/config/config.php';
require $root . '/app/core/Database.php';
$db = (new Database($config))->conn;

$designId = 2;
$dr = $db->query("SELECT pd.design_type_id, pd.phase_id, pp.phase_name FROM project_drawings pd LEFT JOIN project_phases pp ON pp.id = pd.phase_id WHERE pd.id = {$designId}");
$row = $dr->fetch_assoc();
echo "Design ID: $designId | Design Type ID: {$row['design_type_id']} | Phase ID: {$row['phase_id']} | Phase Name: {$row['phase_name']}\n\n";

echo "--- Phase Items for Phase ID {$row['phase_id']} ---\n";
$piRes = $db->query("SELECT id, item_name FROM project_phase_items WHERE phase_id = {$row['phase_id']}");
while($r = $piRes->fetch_assoc()) {
    echo "ID: {$r['id']} | Item Name: {$r['item_name']}\n";
}

echo "\n--- Work Types for Phase Name '{$row['phase_name']}' or Design Type ID {$row['design_type_id']} ---\n";
$wtRes = $db->query("SELECT id, work_type_name FROM work_types WHERE default_phase_name = '{$row['phase_name']}' OR design_type_id = {$row['design_type_id']}");
while($r = $wtRes->fetch_assoc()) {
    echo "ID: {$r['id']} | Work Type Name: {$r['work_type_name']}\n";
}
