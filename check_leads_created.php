<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("SELECT COUNT(*) as c FROM leads WHERE created_at >= '2026-09-26 00:00:00'");
$row = $res->fetch_assoc();
echo "Total leads created since Sep 26: " . $row['c'] . "\n";

$res2 = $db->query("SELECT id, assigned_id, assigned_at, created_at FROM leads WHERE created_at >= '2026-09-26 00:00:00' LIMIT 5");
while($r = $res2->fetch_assoc()) { 
    print_r($r); 
}
