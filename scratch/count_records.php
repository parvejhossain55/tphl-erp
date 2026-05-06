<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $conn->query("SELECT 
    (SELECT COUNT(*) FROM cashout_requests) as general_count,
    (SELECT COUNT(*) FROM vendor_payment_requests) as vendor_count,
    (SELECT COUNT(*) FROM contractor_bill_requests) as contractor_count
");
$r = $res->fetch_assoc();
echo "General: {$r['general_count']}, Vendor: {$r['vendor_count']}, Contractor: {$r['contractor_count']}\n";
echo "Total: " . ($r['general_count'] + $r['vendor_count'] + $r['contractor_count']) . "\n";
$conn->close();
?>
