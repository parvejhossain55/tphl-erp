<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Add project_id and office to journals table
$sql1 = "ALTER TABLE journals ADD COLUMN project_id INT NULL DEFAULT NULL AFTER voucher_type";
if ($db->query($sql1) === TRUE) {
    echo "Added project_id column to journals.\n";
} else {
    echo "Error adding project_id: " . $db->error . "\n";
}

$sql2 = "ALTER TABLE journals ADD COLUMN office VARCHAR(100) NULL DEFAULT NULL AFTER project_id";
if ($db->query($sql2) === TRUE) {
    echo "Added office column to journals.\n";
} else {
    echo "Error adding office: " . $db->error . "\n";
}

$db->close();
?>
