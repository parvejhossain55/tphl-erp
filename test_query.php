<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$rPendingReturns = $db->query("
            SELECT mr.*, p.name AS project_name, v.name AS vendor_name, u.full_name AS created_by_name
            FROM material_returns mr
            LEFT JOIN projects p ON p.id = mr.project_id
            LEFT JOIN vendors v ON v.id = mr.vendor_id
            LEFT JOIN users u ON u.id = mr.created_by
            WHERE mr.status = 'pending'
            ORDER BY mr.id DESC
            LIMIT 10
        ");
if ($rPendingReturns) {
    echo "Success. Num rows: " . $rPendingReturns->num_rows;
} else {
    echo "Error: " . $db->error;
}
