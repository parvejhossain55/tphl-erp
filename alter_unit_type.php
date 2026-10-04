<?php
$config = include 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) die('Connection failed');
$db->query("ALTER TABLE flats MODIFY COLUMN unit_type VARCHAR(50) NULL DEFAULT 'residential'");
echo 'Table altered successfully';
