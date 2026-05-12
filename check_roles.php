<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res=$db->query('SELECT id, name FROM roles WHERE name LIKE "%Manager%" OR id IN (15,22)');
while($row=$res->fetch_assoc()) {
    echo $row['id'] . " - " . $row['name'] . "\n";
}
