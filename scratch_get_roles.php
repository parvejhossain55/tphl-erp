<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("SELECT id, name FROM roles");
while ($row = $res->fetch_assoc()) {
    echo $row['id'] . ": '" . $row['name'] . "'\n";
}
