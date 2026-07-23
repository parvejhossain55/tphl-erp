<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query('SELECT * FROM roles');
while($r=$res->fetch_assoc()){
    echo json_encode($r) . PHP_EOL;
}
