<?php
require 'app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');

echo "1. Checking con_labor_requisitions for ID=35:\n";
$res1 = $db->query("SELECT id, status, created_at FROM con_labor_requisitions WHERE id=35");
if ($row1 = $res1->fetch_assoc()) {
    print_r($row1);
} else {
    echo "ID 35 NOT FOUND in con_labor_requisitions.\n";
}

echo "\n2. Checking approval_requests for ANY source_id=35:\n";
$res2 = $db->query("SELECT id, source_table, request_type, status FROM approval_requests WHERE source_id=35");
while ($row2 = $res2->fetch_assoc()) {
    print_r($row2);
}

echo "\n3. Checking what source_table is typically used for labor requisitions:\n";
$res3 = $db->query("SELECT DISTINCT source_table, request_type FROM approval_requests WHERE request_type LIKE '%labor%' OR source_table LIKE '%labor%' LIMIT 10");
while ($row3 = $res3->fetch_assoc()) {
    print_r($row3);
}
