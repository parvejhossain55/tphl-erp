<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$sql = "
SELECT 
    cmc.name AS category_name,
    cm.name AS material_name,
    bi.quantity,
    bi.unit_cost,
    bi.total_cost
FROM boq_items bi
JOIN boq_master bm ON bi.boq_id = bm.id
LEFT JOIN con_materials cm ON bi.material_id = cm.id
LEFT JOIN con_material_categories cmc ON cm.category_id = cmc.id
WHERE bm.project_id = 13;
";

$res = $db->query($sql);
if ($res) {
    print_r($res->fetch_all(MYSQLI_ASSOC));
} else {
    echo "Error: " . $db->error . "\n";
}

$db->close();
