<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$pid = 13;
$matRes = $db->query("
    SELECT COALESCE(SUM(mri.received_quantity * poi.unit_price), 0) AS total
    FROM material_receiving_items mri
    INNER JOIN material_receiving mr ON mr.id = mri.receiving_id
    INNER JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
    INNER JOIN purchase_orders po ON po.id = mr.purchase_order_id
    INNER JOIN purchase_requests pr ON pr.id = po.request_id
    WHERE mr.overall_status = 'Accepted' AND pr.project_id = $pid
");
$matCost = $matRes ? (float)$matRes->fetch_assoc()['total'] : 0.0;
echo "matCost: " . $matCost . "\n";

$conRes = $db->query("
    SELECT COALESCE(SUM(amount_paid), 0) AS total
    FROM con_financial_transactions
    WHERE ref_type = 'contractor_bill' AND update_status = 'Completed' AND project_id = $pid
");
$conCost = $conRes ? (float)$conRes->fetch_assoc()['total'] : 0.0;
echo "conCost (contractor bill): " . $conCost . "\n";

echo "totalBudgetUsed: " . ($matCost + $conCost) . "\n";

$db->close();
