<?php
require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("SELECT * FROM roles WHERE id=20 OR name LIKE '%Site Operator%'");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
