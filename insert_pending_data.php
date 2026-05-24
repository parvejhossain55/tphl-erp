<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) { die("Connection failed: " . $db->connect_error); }

// Insert Pending Material Requisition
$q1 = "INSERT INTO purchase_requests (project_id, created_by, status, created_at) VALUES 
(1, 1, 'pending', '2026-05-21 16:00:00')";
$db->query($q1);

// Insert Pending Cashout Request
$q2 = "INSERT INTO cashout_requests (project_id, requested_by, amount, description, status, created_at) VALUES 
(1, 1, 7500.50, 'Pending Cashout for Site Expenses', 'pending', '2026-05-21 16:30:00')";
$db->query($q2);

// Insert Pending Vendor Payment Request
$q3 = "INSERT INTO vendor_payment_requests (vendor_id, project_id, invoice_number, net_payable_amount, status, created_at) VALUES 
(1, 1, 'INV-PENDING-999', 54000.00, 'pending', '2026-05-21 17:00:00')";
$db->query($q3);

// Insert Pending Contractor Bill Request
$q4 = "INSERT INTO contractor_bill_requests (project_id, operator_id, bill_type, bill_amount, status, created_at) VALUES 
(1, 1, 'labor', 28000.00, 'pending', '2026-05-21 17:15:00')";
$db->query($q4);

echo "Successfully inserted 4 new 'pending' requests!\n";
