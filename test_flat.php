<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("SELECT id, land_share_value, registration_money FROM flats WHERE id = 6107")->fetch_assoc();
print_r($res);
