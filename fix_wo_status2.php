<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) { die('Connection failed: ' . $db->connect_error); }

$r = $db->query("SELECT id, HEX(status) as hex_status, LENGTH(status) as len, status FROM con_work_orders");
while ($row = $r->fetch_assoc()) {
    echo "ID={$row['id']}: status='{$row['status']}' | hex=" . ($row['hex_status'] ?? 'NULL') . " | length=" . ($row['len'] ?? 'NULL') . PHP_EOL;
}

// Try direct update
$upd = $db->query("UPDATE con_work_orders SET status='approved' WHERE id=1 AND approved_by IS NOT NULL");
echo PHP_EOL . "Direct update result: " . ($upd ? "OK" : "FAILED: ".$db->error) . " | Affected: " . $db->affected_rows . PHP_EOL;

// Verify
$check = $db->query("SELECT id, status, approved_by FROM con_work_orders WHERE id=1")->fetch_assoc();
echo "After update: status='{$check['status']}'" . PHP_EOL;
