<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

// Check structure
echo "=== con_financial_transactions columns ===\n";
$res = $db->query('DESCRIBE con_financial_transactions');
while($r = $res->fetch_assoc()) echo $r['Field'].': '.$r['Type']."\n";

echo "\n=== Sample data for project 13 ===\n";
$res2 = $db->query('SELECT * FROM con_financial_transactions WHERE project_id = 13 LIMIT 5');
while($r = $res2->fetch_assoc()) print_r($r);
