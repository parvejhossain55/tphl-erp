<?php
$config = require 'e:\xampp\htdocs\TPHL-ERP\app\config\config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("SELECT id, payment_type, amount FROM lead_payments WHERE booking_id = 2083");
while($row = $res->fetch_assoc()){
    echo "ID: " . $row['id'] . " Type: " . $row['payment_type'] . " Amount: " . $row['amount'] . "\n";
}
