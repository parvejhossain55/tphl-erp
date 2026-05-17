<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $db->query('DESC con_work_orders');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
