<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("SELECT employee_id, joining_date FROM employees WHERE employee_id IS NOT NULL LIMIT 5");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
