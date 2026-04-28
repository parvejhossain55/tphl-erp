<?php
require_once __DIR__ . '/app/config/config.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) die("Connection failed: " . $db->connect_error);

$q = $db->query("SELECT COUNT(*) as c FROM con_financial_transactions");
echo "con_financial_transactions count: " . $q->fetch_assoc()['c'] . "\n";

$q2 = $db->query("SELECT COUNT(*) as c FROM contractor_bill_requests WHERE status IN ('approved', 'paid', 'partially_paid', 'fully_paid')");
echo "contractor_bill_requests (approved/paid) count: " . $q2->fetch_assoc()['c'] . "\n";

$q3 = $db->query("SELECT COUNT(*) as c FROM vendor_payment_requests WHERE status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid')");
echo "vendor_payment_requests (approved/paid) count: " . $q3->fetch_assoc()['c'] . "\n";
