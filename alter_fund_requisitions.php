<?php
$config = require 'app/config/config.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$queries = [
    "ALTER TABLE fund_requisitions ADD COLUMN is_received TINYINT(1) DEFAULT 0 AFTER payment_attachment_path",
    "ALTER TABLE fund_requisitions ADD COLUMN received_at DATETIME NULL AFTER is_received"
];

foreach ($queries as $query) {
    if ($db->query($query) === TRUE) {
        echo "Successfully ran: $query\n";
    } else {
        echo "Error running: $query - " . $db->error . "\n";
    }
}
$db->close();
