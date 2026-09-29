<?php
require 'app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');

echo "Checking related records for con_labor_requisitions ID = 35...\n\n";

// 1. con_labor_payment_logs
$res = $db->query("SELECT * FROM con_labor_payment_logs WHERE requisition_id = 35");
echo "Found " . $res->num_rows . " records in con_labor_payment_logs.\n";

// 2. approval_requests
$res2 = $db->query("SELECT * FROM approval_requests WHERE source_table = 'con_labor_requisitions' AND source_id = 35");
echo "Found " . $res2->num_rows . " records in approval_requests.\n";

