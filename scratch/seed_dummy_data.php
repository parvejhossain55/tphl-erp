<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Seed Vendor
$vendorName = 'Global Tech Supplies';
$conn->query("INSERT IGNORE INTO vendors (name, company_name, phone, status, created_by) VALUES ('$vendorName', 'Global Tech Ltd', '01999999991', 'active', 1)");

// Seed Contractor
$contractorName = 'Alpha Builders';
$conn->query("INSERT IGNORE INTO contractors (name, role, trade, phone, status, created_by) VALUES ('$contractorName', 'Contractor', 'Civil Works', '01999999992', 'active', 1)");

echo "Dummy vendor and contractor seeded successfully!\n";
?>
