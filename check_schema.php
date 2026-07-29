<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$result = $db->query("SHOW COLUMNS FROM journals LIKE 'status'");
print_r($result->fetch_assoc());
