<?php
$config = require __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
$db = (new Database($config))->conn;

$res = $db->query('SELECT id, booking_id, status, total_paid, retention_amount, refund_amount FROM client_refund_requests ORDER BY id ASC');
while($row = $res->fetch_assoc()){ 
    echo sprintf("#%02d | Status: %-10s | Total: %12.2f | Ret: %10.2f | Ref: %12.2f\n", $row['id'], $row['status'], $row['total_paid'], $row['retention_amount'], $row['refund_amount']); 
}

$sumRes = $db->query("SELECT 
    status,
    COUNT(*) as cnt,
    SUM(total_paid) as sum_paid,
    SUM(retention_amount) as sum_ret,
    SUM(refund_amount) as sum_ref
    FROM client_refund_requests
    GROUP BY status
");
echo "\n--- SUMMARY BY STATUS ---\n";
while($s = $sumRes->fetch_assoc()) {
    print_r($s);
}
