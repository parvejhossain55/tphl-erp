<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$query = "ALTER TABLE initial_estimate_labors ADD COLUMN description TEXT NULL AFTER area_sqft";
if ($db->query($query)) {
    echo "Column 'description' added successfully.\n";
} else {
    echo "Error or column already exists: " . $db->error . "\n";
}

// Also let's update drop_table.sql and tphl_db.sql so CI/CD works!
