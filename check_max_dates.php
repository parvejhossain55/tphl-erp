<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("SELECT MAX(assigned_at) as m1, MAX(created_at) as m2 FROM leads");
$row = $res->fetch_assoc();
echo "Max assigned_at: " . $row['m1'] . "\n";
echo "Max created_at: " . $row['m2'] . "\n";

$res2 = $db->query("SELECT MAX(created_at) as m1 FROM lead_follow_ups");
$row2 = $res2->fetch_assoc();
echo "Max follow-up created_at: " . $row2['m1'] . "\n";
