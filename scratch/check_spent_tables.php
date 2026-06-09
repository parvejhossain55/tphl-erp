<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== con_financial_transactions schema ===\n";
$res = $db->query('DESCRIBE con_financial_transactions');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== con_financial_transactions types & sums ===\n";
$res = $db->query('SELECT ref_type, COUNT(*) as cnt, SUM(amount_paid) as sum_paid FROM con_financial_transactions GROUP BY ref_type');
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo "Ref Type: {$row['ref_type']} | Count: {$row['cnt']} | Sum Paid: {$row['sum_paid']}\n";
    }
}

echo "\n=== material_receiving count & cost ===\n";
$res = $db->query("
    SELECT mr.overall_status, COUNT(*) as cnt, COALESCE(SUM(mri.received_quantity * poi.unit_price), 0) as total_val
    FROM material_receiving_items mri
    INNER JOIN material_receiving mr ON mr.id = mri.receiving_id
    INNER JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
    GROUP BY mr.overall_status
");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo "Status: {$row['overall_status']} | Count: {$row['cnt']} | Total Value: {$row['total_val']}\n";
    }
}

echo "\n=== contractor_bill_requests status & sums ===\n";
$res = $db->query('SELECT status, COUNT(*) as cnt, SUM(bill_amount) as sum_bill, SUM(approved_amount) as sum_appr FROM contractor_bill_requests GROUP BY status');
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo "Status: {$row['status']} | Count: {$row['cnt']} | Sum Bill: {$row['sum_bill']} | Sum Approved: {$row['sum_appr']}\n";
    }
}
