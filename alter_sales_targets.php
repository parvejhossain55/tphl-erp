<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Add from_date and to_date to sales_targets
$sql = "ALTER TABLE sales_targets 
        ADD COLUMN from_date DATE NULL AFTER month,
        ADD COLUMN to_date DATE NULL AFTER from_date";

if ($db->query($sql)) {
    echo "Successfully added from_date and to_date to sales_targets.\n";
} else {
    echo "Error altering table: " . $db->error . "\n";
}

$db->close();
