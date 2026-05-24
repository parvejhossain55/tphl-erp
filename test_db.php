<?php
require_once 'app/core/Controller.php';
require_once 'app/core/Model.php';
require_once 'app/core/Database.php';

$config = ['db_host'=>'127.0.0.1','db_user'=>'root','db_pass'=>'','db_name'=>'tphl_db'];
$db = new Database($config);
$res = $db->conn->query("SELECT mr.id, mr.purchase_order_id, mr.overall_status, mri.received_quantity, mri.status FROM material_receiving mr JOIN material_receiving_items mri ON mri.receiving_id = mr.id");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
