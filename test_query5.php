<?php
$config = require 'e:\xampp\htdocs\TPHL-ERP\app\config\config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("DESCRIBE lead_bookings");
while($row = $res->fetch_assoc()){
    echo $row['Field'] . "\n";
}
