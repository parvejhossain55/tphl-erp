<?php
$c = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($c['db_host'], $c['db_user'], $c['db_pass'], $c['db_name']);

echo "=== projects columns ===\n";
$r = $db->query('SHOW COLUMNS FROM projects');
while ($row = $r->fetch_assoc()) echo $row['Field'] . ' - ' . $row['Type'] . "\n";

echo "\n=== Tables with 'project' and 'user' ===\n";
$r2 = $db->query("SHOW TABLES LIKE '%project%'");
while ($row = $r2->fetch_row()) echo 'TABLE: ' . $row[0] . "\n";
