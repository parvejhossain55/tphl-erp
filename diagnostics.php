<?php
require 'app/config/config.php';
$cfg = require 'app/config/config.php';
$db = new mysqli($cfg['db_host'], $cfg['db_user'], $cfg['db_pass'], $cfg['db_name']);
$db->query("DELETE FROM payslips WHERE YEAR(payslip_month) = 0");
echo json_encode(["status" => "cleaned"]);
