<?php
$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("DESCRIBE chart_of_accounts");
while($row = $res->fetch_assoc()){
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
