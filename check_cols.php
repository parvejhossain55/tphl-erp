<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("SHOW COLUMNS FROM initial_estimate_items");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
