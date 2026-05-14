<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $db->query("SHOW COLUMNS FROM lead_payments");
while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";
