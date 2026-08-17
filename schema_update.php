<?php
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Check current enum of fund_requisitions
$res = $db->query("SHOW COLUMNS FROM fund_requisitions LIKE 'status'");
$row = $res->fetch_assoc();
echo "fund_requisitions status: " . $row['Type'] . "\n";

// Alter fund_requisitions
$sql1 = "ALTER TABLE fund_requisitions 
ADD COLUMN IF NOT EXISTS approved_amount DECIMAL(12,2) DEFAULT 0 AFTER amount,
ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(12,2) DEFAULT 0 AFTER approved_amount,
MODIFY COLUMN status ENUM('pending_dept','pending','approved','rejected','paid','partially_approved','partially_paid') DEFAULT 'pending'";

if ($db->query($sql1) === TRUE) {
    echo "fund_requisitions altered successfully.\n";
} else {
    echo "Error altering fund_requisitions: " . $db->error . "\n";
}

// Check approval_requests
$res = $db->query("SHOW COLUMNS FROM approval_requests LIKE 'status'");
$row = $res->fetch_assoc();
echo "approval_requests status: " . $row['Type'] . "\n";

// if it's enum, alter it
if (strpos($row['Type'], 'enum') !== false) {
    // Let's get the current enum values and append the new ones
    preg_match("/^enum\((.*)\)$/", $row['Type'], $matches);
    $enums = explode(',', $matches[1]);
    $newEnums = array_unique(array_merge($enums, ["'partially_approved'", "'partially_paid'", "'partial'"]));
    $enumStr = implode(',', $newEnums);
    $sql2 = "ALTER TABLE approval_requests MODIFY COLUMN status ENUM($enumStr) DEFAULT 'pending'";
    if ($db->query($sql2) === TRUE) {
        echo "approval_requests altered successfully.\n";
    } else {
        echo "Error altering approval_requests: " . $db->error . "\n";
    }
}
