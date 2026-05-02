<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$config = require __DIR__ . '/app/config/config.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("DESCRIBE lead_payments");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
