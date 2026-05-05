<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');

$sql = "
SELECT * FROM (
    SELECT 
        cr.id,
        'general' AS cashout_type,
        cr.amount,
        cr.status,
        cr.purpose_paid_to,
        cr.created_at
    FROM cashout_requests cr

    UNION ALL

    SELECT 
        vpr.id,
        'vendor' AS cashout_type,
        vpr.invoice_amount AS amount,
        vpr.status,
        v.name AS purpose_paid_to,
        vpr.created_at
    FROM vendor_payment_requests vpr
    LEFT JOIN vendors v ON vpr.vendor_id = v.id

    UNION ALL

    SELECT 
        cbr.id,
        'contractor bill' AS cashout_type,
        cbr.bill_amount AS amount,
        cbr.status,
        cbr.work_description AS purpose_paid_to,
        cbr.created_at
    FROM contractor_bill_requests cbr
) AS cr
ORDER BY cr.created_at DESC
";

$res = $conn->query($sql);
echo "Total rows: " . $res->num_rows . "\n\n";
while($r = $res->fetch_assoc()) {
    echo "ID:{$r['id']} TYPE:{$r['cashout_type']} AMOUNT:{$r['amount']} STATUS:{$r['status']} PURPOSE:{$r['purpose_paid_to']}\n";
}

$conn->close();
?>
