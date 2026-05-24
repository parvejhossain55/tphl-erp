<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== BOQ Masters ===\n";
$resMaster = $db->query("SELECT id, project_id, target_id, version, status, created_at FROM boq_master");
while ($row = $resMaster->fetch_assoc()) {
    print_r($row);
}

echo "\n=== BOQ Items ===\n";
$resItems = $db->query("SELECT bi.id, bi.boq_id, bi.material_id, m.name AS material_name, bi.quantity 
                        FROM boq_items bi 
                        JOIN con_materials m ON m.id = bi.material_id");
while ($row = $resItems->fetch_assoc()) {
    print_r($row);
}

echo "\n=== Purchase Requests ===\n";
$resPR = $db->query("SELECT id, project_id, bom_id, status FROM purchase_requests");
while ($row = $resPR->fetch_assoc()) {
    print_r($row);
}

echo "\n=== Purchase Request Items ===\n";
$resPRItems = $db->query("SELECT pri.id, pri.request_id, pri.material_id, m.name AS material_name, pri.quantity 
                          FROM purchase_request_items pri
                          JOIN con_materials m ON m.id = pri.material_id");
while ($row = $resPRItems->fetch_assoc()) {
    print_r($row);
}
?>
