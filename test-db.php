<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/app/config/config.php';
$config = require __DIR__ . '/app/config/config.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Check enum for lead_bookings payment_status
$res = $db->query("SHOW COLUMNS FROM lead_bookings LIKE 'payment_status'");
$col1 = $res->fetch_assoc();
echo "lead_bookings.payment_status: \n";
print_r($col1);

// Check enum for lead_payments payment_status
$res2 = $db->query("SHOW COLUMNS FROM lead_payments LIKE 'payment_status'");
$col2 = $res2->fetch_assoc();
echo "\nlead_payments.payment_status: \n";
print_r($col2);
