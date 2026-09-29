<?php
require 'app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SHOW CREATE TABLE approval_requests");
$row = $res->fetch_array();
echo $row[1] . "\n\n";

$res2 = $db->query("SHOW CREATE TABLE con_labor_payment_logs");
$row2 = $res2->fetch_array();
echo $row2[1] . "\n\n";
