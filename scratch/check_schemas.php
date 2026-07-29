<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$tables = ['expense_adjustments', 'fund_requisitions', 'cashout_requests', 'vendor_payment_requests', 'contractor_bill_requests', 'lead_payments'];
foreach($tables as $table) {
    echo "--- $table ---\n";
    $res = $db->query("SHOW COLUMNS FROM $table");
    if ($res) {
        while($row = $res->fetch_assoc()) echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}
