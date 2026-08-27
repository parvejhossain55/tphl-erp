<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $db->query('SELECT DISTINCT upload_from FROM purchase_orders');
while($row = $res->fetch_assoc()) echo $row['upload_from'] . "\n";
