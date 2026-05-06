<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');

// Keep ID 1 (original), delete imported duplicates (ID 4-9 for cashout, ID 2-7 for vendor/contractor)
$conn->query("DELETE FROM cashout_payments WHERE cashout_request_id > 1");
$conn->query("DELETE FROM approval_requests WHERE source_table = 'cashout_requests' AND source_id > 1");
$conn->query("DELETE FROM cashout_requests WHERE id > 1");

$conn->query("DELETE FROM con_financial_transactions WHERE ref_type = 'vendor_payment' AND ref_id > 1");
$conn->query("DELETE FROM approval_requests WHERE source_table = 'vendor_payment_requests' AND source_id > 1");
$conn->query("DELETE FROM vendor_payment_requests WHERE id > 1");

$conn->query("DELETE FROM con_financial_transactions WHERE ref_type = 'contractor_bill' AND ref_id > 1");
$conn->query("DELETE FROM approval_requests WHERE source_table = 'contractor_bill_requests' AND source_id > 1");
$conn->query("DELETE FROM contractor_bill_requests WHERE id > 1");

echo "Cleaned up duplicate test data.\n";

// Verify
$res = $conn->query("SELECT 
    (SELECT COUNT(*) FROM cashout_requests) as general_count,
    (SELECT COUNT(*) FROM vendor_payment_requests) as vendor_count,
    (SELECT COUNT(*) FROM contractor_bill_requests) as contractor_count
");
$r = $res->fetch_assoc();
echo "Remaining - General: {$r['general_count']}, Vendor: {$r['vendor_count']}, Contractor: {$r['contractor_count']}\n";

$conn->close();
?>
