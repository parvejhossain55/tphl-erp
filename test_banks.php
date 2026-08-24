<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query('SHOW TABLES LIKE "%bank%"');
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
