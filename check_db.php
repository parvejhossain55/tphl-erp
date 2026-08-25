<?php
require_once 'c:\xampp\htdocs\TPHL-ERP\app\config\config.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) { die("Connection failed: " . $db->connect_error); }
$res = $db->query("SHOW TABLES LIKE 'company_bank_accounts'");
echo "company_bank_accounts: " . ($res->num_rows > 0 ? "exists" : "missing") . "\n";
$res2 = $db->query("SHOW TABLES LIKE 'chart_of_accounts'");
echo "chart_of_accounts: " . ($res2->num_rows > 0 ? "exists" : "missing") . "\n";
