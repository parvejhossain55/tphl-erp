<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');

$vendorName = 'Global Tech Supplies';
$projectName = 'The Premium Signature Heights';

// Get Project ID
$res = $conn->query("SELECT id FROM projects WHERE name = '$projectName'");
$projectId = $res->fetch_assoc()['id'] ?? 1;

// Get Vendor ID
$res = $conn->query("SELECT id FROM vendors WHERE name = '$vendorName'");
$vendorId = $res->fetch_assoc()['id'] ?? 1;

// Create Purchase Request 1
$conn->query("INSERT INTO purchase_requests (project_id, created_by, status, notes) VALUES ($projectId, 1, 'PO Issued', 'Dummy Request 1')");
$prId1 = $conn->insert_id;

// Create Purchase Order 1 (for 50,000, created on May 1)
$conn->query("INSERT INTO purchase_orders (request_id, created_by, status, order_date, created_at) VALUES ($prId1, 1, 'Issued', '2026-05-01', '2026-05-01 10:00:00')");
$poId1 = $conn->insert_id;

// Add item for PO 1
$conn->query("INSERT INTO purchase_order_items (purchase_order_id, material_id, selected_vendor_id, quantity, unit_price, created_by) VALUES ($poId1, 1, $vendorId, 100, 500, 1)");


// Create Purchase Request 2
$conn->query("INSERT INTO purchase_requests (project_id, created_by, status, notes) VALUES ($projectId, 1, 'PO Issued', 'Dummy Request 2')");
$prId2 = $conn->insert_id;

// Create Purchase Order 2 (for 25,000, created on May 3)
$conn->query("INSERT INTO purchase_orders (request_id, created_by, status, order_date, created_at) VALUES ($prId2, 1, 'Issued', '2026-05-03', '2026-05-03 10:00:00')");
$poId2 = $conn->insert_id;

// Add item for PO 2
$conn->query("INSERT INTO purchase_order_items (purchase_order_id, material_id, selected_vendor_id, quantity, unit_price, created_by) VALUES ($poId2, 1, $vendorId, 50, 500, 1)");

echo "Created Dummy PO $poId1 (Total: 50000, Date: May 1) for vendor $vendorId, project $projectId\n";
echo "Created Dummy PO $poId2 (Total: 25000, Date: May 3) for vendor $vendorId, project $projectId\n";

$conn->close();
?>
