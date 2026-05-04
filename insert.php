<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$query = "INSERT INTO material_returns (receiving_id, vendor_id, project_id, return_date, return_reason, status, created_by) VALUES (1, 1, 1, '2026-05-04', 'QC Test Failed: GRN-1 Test material - Result: 5', 'pending', 126)";
if($db->query($query)) {
    echo "Inserted dummy material return.";
} else {
    echo "ERROR: " . $db->error;
}
