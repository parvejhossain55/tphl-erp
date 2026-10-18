<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SELECT id, payment_date, payment_type, settlement_type, parent_payment_id, emi_no, amount FROM lead_payments WHERE booking_id = 3484 ORDER BY id ASC");
while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Date: " . $row['payment_date'] . " | Type: " . $row['payment_type'] . " | Settle: " . ($row['settlement_type'] ?? 'NULL') . " | ParentID: " . ($row['parent_payment_id'] ?? 'NULL') . " | emi_no: " . ($row['emi_no'] ?? 'NULL') . " | Amt: " . $row['amount'] . "\n";
}
