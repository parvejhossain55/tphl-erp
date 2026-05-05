<?php
$c = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($c['db_host'], $c['db_user'], $c['db_pass'], $c['db_name']);
$res = $db->query("SHOW COLUMNS FROM material_receiving");
while ($row = $res->fetch_assoc()) {
    print_r($row['Field'] . "\n");
}
