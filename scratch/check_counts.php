<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SELECT COUNT(*) AS total FROM approval_requests WHERE status IN ('pending', 'partially_approved', '') AND (LOWER(approve_role) IN ('management', 'operations') OR approve_role IS NULL OR approve_role = '') AND (op_head_status != 'approved' OR op_head_status IS NULL)");
echo "Role 20 count: " . $res->fetch_assoc()['total'] . PHP_EOL;

$resMgmt = $db->query("SELECT COUNT(*) AS total FROM approval_requests WHERE status IN ('pending', 'partially_approved', '') AND (LOWER(approve_role) IN ('management', 'operations') OR approve_role IS NULL OR approve_role = '') AND (request_amount <= 100000.01 OR request_amount IS NULL OR op_head_status = 'approved')");
echo "Mgmt count: " . $resMgmt->fetch_assoc()['total'] . PHP_EOL;
