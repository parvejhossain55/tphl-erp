<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $db->query('SHOW COLUMNS FROM purchase_orders');
while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";
