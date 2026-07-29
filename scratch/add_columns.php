<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$queries = [
    "ALTER TABLE purchase_orders ADD COLUMN purchase_head_approved_by_id INT NULL DEFAULT NULL",
    "ALTER TABLE purchase_orders ADD COLUMN purchase_head_approved_at DATETIME NULL DEFAULT NULL",
    "ALTER TABLE purchase_orders ADD COLUMN purchase_head_note TEXT NULL DEFAULT NULL",
    "ALTER TABLE purchase_orders ADD COLUMN op_head_approved_by_id INT NULL DEFAULT NULL",
    "ALTER TABLE purchase_orders ADD COLUMN op_head_approved_at DATETIME NULL DEFAULT NULL",
    "ALTER TABLE purchase_orders ADD COLUMN op_head_note TEXT NULL DEFAULT NULL"
];

foreach ($queries as $q) {
    echo "Running: $q\n";
    if ($db->query($q)) {
        echo "Success\n";
    } else {
        echo "Error: " . $db->error . "\n";
    }
}
