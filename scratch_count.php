<?php
$config = require __DIR__ . "/app/config/config.php";
require_once __DIR__ . '/app/core/Database.php';
$database = new Database($config);
$db = $database->conn;
$res = $db->query("SELECT verification_status, COUNT(*) as c FROM lead_payments GROUP BY verification_status");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
