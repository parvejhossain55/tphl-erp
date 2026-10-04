<?php
$config = include 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$db->query("ALTER TABLE contractor_bill_requests ADD COLUMN rejection_reason TEXT NULL AFTER approved_by");
echo 'rejection_reason column added successfully';
