<?php
$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$query = "SHOW TABLES LIKE '%account%'";
$res = $db->query($query);
while ($row = $res->fetch_array()) {
    echo "Table: " . $row[0] . "\n";
}
