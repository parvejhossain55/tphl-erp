<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$row = $db->query("SHOW COLUMNS FROM office_asset_products")->fetch_all(MYSQLI_ASSOC);
echo json_encode($row, JSON_PRETTY_PRINT);
