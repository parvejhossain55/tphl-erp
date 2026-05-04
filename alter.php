<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$db->query("ALTER TABLE qc_material_tests MODIFY COLUMN attachment VARCHAR(255) NULL");
echo "Done modifying. " . $db->error;
