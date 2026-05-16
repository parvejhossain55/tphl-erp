<?php
$config = require 'app/config/config.php';
$mysqli = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $mysqli->query("SHOW COLUMNS FROM lead_payments LIKE 'amount'");
print_r($res->fetch_assoc());
