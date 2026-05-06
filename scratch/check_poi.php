<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $conn->query("SELECT poi.*, m.name as material_name FROM purchase_order_items poi LEFT JOIN materials m ON poi.material_id = m.id LIMIT 5");
if ($res) {
    while($r = $res->fetch_assoc()) echo json_encode($r)."\n";
} else {
    echo $conn->error;
}
$conn->close();
?>
