<?php
// Test script to invoke PayrollController::saveWorkspacePayslips logic manually
require_once __DIR__ . '/app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
if ($db->connect_error) die("DB Error: " . $db->connect_error);

// 1. Get a valid payslip ID to test with
$res = $db->query("SELECT id, employee_id, payroll_project_id, payslip_month FROM payslips LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $payslipId = $row['id'];
    echo "Testing update on payslip ID: $payslipId\n";
    
    $stmt = $db->prepare("UPDATE payslips SET subsidized_lunch=?, unpaid_absent_amount=?, arrears_deduction=?, others_deduction=?, punctuality_bonus=?, overtime=?, arrears_earning=?, duty_allowance=?, tax=? WHERE id=?");
    if ($stmt) {
        $val1=100.5; $val2=200; $val3=300; $val4=400; $val5=500; $val6=600; $val7=700; $val8=800; $val9=900;
        $stmt->bind_param("dddddddddi", $val1, $val2, $val3, $val4, $val5, $val6, $val7, $val8, $val9, $payslipId);
        if ($stmt->execute()) {
            echo "Success! Rows affected: " . $stmt->affected_rows . "\n";
            $chk = $db->query("SELECT subsidized_lunch FROM payslips WHERE id=$payslipId")->fetch_assoc();
            echo "Value in DB is now: " . $chk['subsidized_lunch'] . "\n";
        } else {
            echo "Execute error: " . $stmt->error . "\n";
        }
    } else {
        echo "Prepare error: " . $db->error . "\n";
    }
} else {
    echo "No payslips found in DB.";
}
