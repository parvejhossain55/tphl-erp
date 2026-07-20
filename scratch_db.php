<?php
$config = require __DIR__ . "/app/config/config.php";
require_once __DIR__ . '/app/core/Database.php';
$database = new Database($config);
$db = $database->conn;
$res = $db->query("DESCRIBE lead_payments");
while($row=$res->fetch_assoc()) echo $row['Field'].', ';
