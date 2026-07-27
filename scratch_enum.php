<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$tables = ['cashout_requests', 'purchase_orders', 'vendor_payment_requests', 'contractor_bill_requests'];
foreach ($tables as $t) {
    $row = $db->query("SHOW COLUMNS FROM $t LIKE 'status'")->fetch_assoc();
    echo $t . ": " . $row['Type'] . "\n";
}
