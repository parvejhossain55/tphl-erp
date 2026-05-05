<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$db->query("ALTER TABLE qc_material_tests MODIFY COLUMN test_status ENUM('pass', 'fail', 'partially_passed') DEFAULT 'fail'");
echo $db->error;
