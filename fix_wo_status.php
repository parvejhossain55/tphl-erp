<?php
// Fix work orders with empty/null status but have approved_by set (they are actually approved)
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) { die('Connection failed: ' . $db->connect_error); }

// Fix: Set status='approved' where approved_by IS NOT NULL but status is empty/null
$result = $db->query("UPDATE con_work_orders SET status='approved' WHERE (status IS NULL OR TRIM(status) = '') AND approved_by IS NOT NULL");
echo "✅ Fixed " . $db->affected_rows . " work order(s): Set status='approved' where approved_by was set." . PHP_EOL;

// Fix: Set status='draft' where status is empty/null and no approval
$result2 = $db->query("UPDATE con_work_orders SET status='draft' WHERE (status IS NULL OR TRIM(status) = '') AND approved_by IS NULL");
echo "✅ Fixed " . $db->affected_rows . " work order(s): Set status='draft' where no approval." . PHP_EOL;

// Verify
echo PHP_EOL . "=== Final Status ===" . PHP_EOL;
$rows = $db->query("SELECT id, project_id, title, status, approved_by FROM con_work_orders")->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $r) {
    echo "ID={$r['id']}: status='{$r['status']}', approved_by=" . ($r['approved_by'] ?? 'NULL') . PHP_EOL;
}
