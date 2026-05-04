<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$db->query("INSERT INTO qc_material_tests (material_receiving_id, test_name, result_value, test_status, lab_report_link, created_by, attachment) VALUES (1, 'test', 'test', 'fail', '', 1, NULL)");
echo "ERROR: " . $db->error;
