<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("DESCRIBE advance_salaries");
if ($res) {
    while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";
} else {
    echo $db->error;
}
