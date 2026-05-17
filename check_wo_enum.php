<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) { die('Connection failed: ' . $db->connect_error); }

// Check column definition
$r = $db->query("SHOW COLUMNS FROM con_work_orders LIKE 'status'");
$col = $r->fetch_assoc();
echo "Column: " . $col['Field'] . PHP_EOL;
echo "Type: " . $col['Type'] . PHP_EOL;
echo "Default: " . ($col['Default'] ?? 'NULL') . PHP_EOL;
echo PHP_EOL;

// The fix: ALTER TABLE to accept 'approved' etc OR change to VARCHAR
// First let's see current ENUM values and add missing ones
echo "=== Current status values in DB ===" . PHP_EOL;
$r2 = $db->query("SELECT id, HEX(status) as hex, status FROM con_work_orders");
while ($row = $r2->fetch_assoc()) {
    echo "ID={$row['id']}: hex={$row['hex']}, displayed='{$row['status']}'" . PHP_EOL;
}
