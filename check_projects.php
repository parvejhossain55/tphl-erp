<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$result = $db->query("SELECT id, name FROM projects");
while($row = $result->fetch_assoc()) {
    print_r($row);
}
