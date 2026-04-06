<?php
$c = require 'app/config/config.php';
$db = new mysqli($c['db_host'], $c['db_user'], $c['db_pass'], $c['db_name']);
$r = $db->query("SELECT id, request_type, approve_role, status, created_at FROM approval_requests ORDER BY created_at DESC LIMIT 10");
$rows = [];
while($row = $r->fetch_assoc()) $rows[] = $row;
header('Content-Type: application/json');
echo json_encode($rows, JSON_PRETTY_PRINT);
