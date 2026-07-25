<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$sql = "ALTER TABLE fund_requisitions ADD COLUMN asset_id INT(11) NULL AFTER project_id";
if ($db->query($sql)) {
    echo "Added asset_id to fund_requisitions\n";
} else {
    echo "Error: " . $db->error . "\n";
}
