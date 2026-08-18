<?php
require 'app/config/config.php';
require 'app/core/Database.php';

$config = require 'app/config/config.php';
$database = new Database($config);
$db = $database->conn;

$res = $db->query("DESCRIBE employees");
while ($r = $res->fetch_assoc()) {
    echo $r['Field'] . " - " . $r['Type'] . "\n";
}
