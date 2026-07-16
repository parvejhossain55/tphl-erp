<?php
$config = require __DIR__ . "/app/config/config.php";
require_once __DIR__ . '/app/core/Database.php';
$database = new Database($config);
$db = $database->conn;
$res = $db->query('DESCRIBE departments'); 
while($row=$res->fetch_assoc()) echo $row['Field'].', '; 
echo "\n"; 
$res = $db->query('DESCRIBE employees'); 
while($row=$res->fetch_assoc()) echo $row['Field'].', '; 
echo "\n"; 
$res = $db->query('DESCRIBE users'); 
while($row=$res->fetch_assoc()) echo $row['Field'].', ';
