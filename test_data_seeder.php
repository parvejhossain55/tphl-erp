<?php
$conn = new mysqli("127.0.0.1", "root", "", "tphl_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// 1. Find the Project ID
$res = $conn->query("SELECT id FROM projects WHERE name LIKE '%Harmony%' LIMIT 1");
if ($res && $res->num_rows > 0) {
    $projectId = $res->fetch_assoc()['id'];
} else {
    // Insert dummy project if not exists
    $conn->query("INSERT INTO projects (name, status, created_at) VALUES ('The Premium Harmony Residence', 'active', NOW())");
    $projectId = $conn->insert_id;
}

// 2. Find or create a Vendor
$res = $conn->query("SELECT id FROM vendors LIMIT 1");
if ($res && $res->num_rows > 0) {
    $vendorId = $res->fetch_assoc()['id'];
} else {
    $conn->query("INSERT INTO vendors (name, phone, status, created_at) VALUES ('Test Cement Supplier', '01711111111', 'active', NOW())");
    $vendorId = $conn->insert_id;
}

// 3. Create a Pending Vendor Payment Request
$stmt = $conn->prepare("
    INSERT INTO vendor_payment_requests 
    (vendor_id, project_id, invoice_number, invoice_amount, net_payable_amount, net_payable, status, created_by, created_at) 
    VALUES (?, ?, 'INV-TEST-001', 50000.00, 50000.00, 50000.00, 'Submitted', 1, NOW())
");
$stmt->bind_param("ii", $vendorId, $projectId);
$stmt->execute();
$vprId = $stmt->insert_id;

// 4. Create an Approval Request for the MD
$stmt = $conn->prepare("
    INSERT INTO approval_requests 
    (source_table, source_id, status, requested_by, created_at) 
    VALUES ('vendor_payment_requests', ?, 'pending', 1, NOW())
");
$stmt->bind_param("i", $vprId);
$stmt->execute();

echo "Test Data Created!\n";
echo "Project ID: $projectId\n";
echo "Vendor ID: $vendorId\n";
echo "Vendor Payment Request ID: $vprId\n";
echo "\nYou can now log in, go to Vendor Payments, click 'Approve' (as MD), and then go to Pending Payments and 'Process Payment' (as Accounts) to see the journals auto-generate!\n";

$conn->close();
