<?php
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
$res = $db->query("SELECT id, amount, status, purpose, created_at FROM fund_requisitions ORDER BY id DESC LIMIT 15");
while($row = $res->fetch_assoc()) {
    echo "FR-{$row['id']} | Amount: ৳{$row['amount']} | Status: '{$row['status']}' | Purpose: {$row['purpose']}\n";
}
