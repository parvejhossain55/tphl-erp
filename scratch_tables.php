<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$tables = $db->query("SHOW TABLES LIKE '%asset%'")->fetch_all(MYSQLI_ASSOC);
echo json_encode($tables, JSON_PRETTY_PRINT);
