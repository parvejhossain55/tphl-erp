<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) { die("Connection failed: " . $db->connect_error); }

$q = "INSERT INTO cashout_requests (project_id, requested_by, amount, description, status, qc_approved_by_id, created_at) VALUES 
(1, 1, 5000.00, 'Dummy Cashout 1', 'qc_approved', 1, '2026-05-20 10:00:00'),
(1, 1, 15000.00, 'Dummy Cashout 2', 'pending', NULL, '2026-05-21 12:00:00');";
if(!$db->query($q)) echo $db->error . "\n";

$q = "INSERT INTO vendor_payment_requests (vendor_id, project_id, invoice_number, net_payable_amount, status, qc_approved_by_id, created_at) VALUES 
(1, 1, 'INV-1001', 25000.00, 'QC Approved', 1, '2026-05-18 11:30:00'),
(1, 1, 'INV-1002', 45000.00, 'Draft', NULL, '2026-05-19 14:20:00');";
if(!$db->query($q)) echo $db->error . "\n";

$q = "INSERT INTO contractor_bill_requests (project_id, operator_id, bill_type, bill_amount, status, qc_approved_by_id, created_at) VALUES 
(1, 1, 'service', 12000.00, 'verified', NULL, '2026-05-17 09:15:00'),
(1, 1, 'service', 35000.00, 'qc_approved', 1, '2026-05-16 16:45:00');";
if(!$db->query($q)) echo $db->error . "\n";

echo "Dummy data inserted!\n";
