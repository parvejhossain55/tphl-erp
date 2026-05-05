<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$result = $db->query("SELECT * FROM material_returns");
if($result) {
    echo "Count: " . $result->num_rows . "\n";
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "ERROR: " . $db->error;
}
