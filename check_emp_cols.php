<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("SHOW COLUMNS FROM employees");
while($row = $res->fetch_assoc()) {
    if (in_array(strtolower($row['Field']), ['status', 'deleted_at', 'is_active'])) {
        print_r($row);
    }
}
