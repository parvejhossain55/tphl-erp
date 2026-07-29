<?php
$conn = new mysqli("127.0.0.1", "root", "", "tphl_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// 1. Find the Project ID
$res = $conn->query("SELECT id FROM projects WHERE name LIKE '%Harmony%' LIMIT 1");
if ($res && $res->num_rows > 0) {
    $projectId = $res->fetch_assoc()['id'];
} else {
    $conn->query("INSERT INTO projects (name, status, created_at) VALUES ('The Premium Harmony Residence', 'active', NOW())");
    $projectId = $conn->insert_id;
}

// 2. Find or create a Contractor
$res = $conn->query("SELECT id FROM contractors LIMIT 1");
if ($res && $res->num_rows > 0) {
    $contractorId = $res->fetch_assoc()['id'];
} else {
    $conn->query("INSERT INTO contractors (name, phone, status, created_at) VALUES ('Test Civil Contractor', '01722222222', 'active', NOW())");
    $contractorId = $conn->insert_id;
}

// 3. Create a Pending Contractor Bill Request
$stmt = $conn->prepare("
    INSERT INTO contractor_bill_requests 
    (operator_id, project_id, work_description, bill_amount, status, created_by, created_at) 
    VALUES (?, ?, 'Roof Casting Work', 75000.00, 'submitted', 1, NOW())
");
$stmt->bind_param("ii", $contractorId, $projectId);
$stmt->execute();
$cbrId = $stmt->insert_id;

// 4. Create an Approval Request for the MD
$stmt = $conn->prepare("
    INSERT INTO approval_requests 
    (source_table, source_id, status, requested_by, created_at) 
    VALUES ('contractor_bill_requests', ?, 'pending', 1, NOW())
");
$stmt->bind_param("i", $cbrId);
$stmt->execute();

echo "Contractor Test Data Created!\n";
echo "Project ID: $projectId\n";
echo "Contractor ID: $contractorId\n";
echo "Contractor Bill Request ID: $cbrId\n";
echo "\nYou can now log in, go to Contractor Bills, click 'Approve' (as MD), to see the journals auto-generate!\n";

$conn->close();
