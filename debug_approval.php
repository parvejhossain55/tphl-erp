<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $conn->query("SELECT id, department, request_type, title, source_table, source_id, approve_role, status, op_head_status, request_amount, approved_amount FROM approval_requests WHERE source_table IN ('client_refund_requests', 'cashout_requests') OR request_type LIKE '%refund%' ORDER BY id DESC LIMIT 10");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "\n--- Recent client_refund_requests ---\n";
$res2 = $conn->query("SELECT id, booking_id, status, total_paid, retention_amount, refund_amount, cashout_request_id, created_at, updated_at FROM client_refund_requests ORDER BY id DESC LIMIT 10");
while ($r2 = $res2->fetch_assoc()) {
    print_r($r2);
}

