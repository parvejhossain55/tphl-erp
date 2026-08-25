<?php
require_once 'c:\xampp\htdocs\TPHL-ERP\app\config\config.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$db->query("ALTER TABLE payslips ADD COLUMN is_excluded TINYINT(1) DEFAULT 0");
echo "DB Updated.";
