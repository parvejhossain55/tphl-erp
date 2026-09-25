<?php
$config = require 'e:\xampp\htdocs\TPHL-ERP\app\config\config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("DESCRIBE flats");
while($row = $res->fetch_assoc()){
    if ($row['Field'] == 'sqft') {
        echo $row['Field'] . " " . $row['Type'] . "\n";
    }
}
