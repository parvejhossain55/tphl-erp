<?php
require_once __DIR__ . '/../app/config/database.php';

$payslipId = 1;
global $db;
if (!isset($db)) {
    $db = (new Database())->getConnection();
}

$updateStmt = $db->prepare("UPDATE payslips SET salary_hold = '' WHERE id = ?");
$updateStmt->bind_param("i", $payslipId);
$result = $updateStmt->execute();

if ($result) {
    echo "Success: Salary Hold removed.\n";
} else {
    echo "Fail: Failed to remove Salary Hold.\n";
}
$updateStmt->close();

$check = $db->query("SELECT id, salary_hold FROM payslips WHERE id = 1");
$row = $check->fetch_assoc();
print_r($row);
?>