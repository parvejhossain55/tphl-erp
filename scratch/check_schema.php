<?php
$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) { die("Connection failed: " . $db->connect_error); }

$res = $db->query('DESCRIBE flats');
echo "=== flats table column types ===\n";
while($r = $res->fetch_assoc()) {
    echo sprintf("%-30s %-20s %-8s\n", $r['Field'], $r['Type'], $r['Null']);
}
