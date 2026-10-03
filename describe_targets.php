<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("DESCRIBE sales_targets");
while($r = $res->fetch_assoc()) { 
    echo $r['Field'] . " - " . $r['Type'] . "\n";
}
