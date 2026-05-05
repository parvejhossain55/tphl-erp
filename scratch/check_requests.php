<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $conn->query('SELECT id, purpose_paid_to, amount, created_at FROM cashout_requests ORDER BY id DESC LIMIT 5');
while($r = $res->fetch_assoc()) echo json_encode($r)."\n";
echo "== Vendor ==\n";
$res = $conn->query('SELECT id, vendor_id, invoice_amount FROM vendor_payment_requests ORDER BY id DESC LIMIT 5');
while($r = $res->fetch_assoc()) echo json_encode($r)."\n";
?>
