<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("SELECT COUNT(*) as c FROM lead_follow_ups WHERE created_at >= '2026-09-26 00:00:00'");
$row = $res->fetch_assoc();
echo "Total follow-ups since Sep 26: " . $row['c'] . "\n";
