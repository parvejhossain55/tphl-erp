<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$q = "ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('Draft','Issued','Partially Received','Fully Received','Cancelled','PM Approved','QC Approved','Approved','Purchase Head Approved') NOT NULL DEFAULT 'Draft'";

echo "Running: $q\n";
if ($db->query($q)) {
    echo "Success\n";
} else {
    echo "Error: " . $db->error . "\n";
}
