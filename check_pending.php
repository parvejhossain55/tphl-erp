<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res=$db->query('SELECT COUNT(*) as c FROM target_fixed WHERE approved_by_manager=0');
echo "Pending PM targets: " . $res->fetch_assoc()['c'] . "\n";
