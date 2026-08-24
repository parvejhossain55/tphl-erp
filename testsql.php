<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die('Connection failed: ' . $db->connect_error);
}
$sql = "INSERT INTO attendance_monthly_update 
(employee_id, payroll_project_id, target_month, working_days, present, absent, leave_days, dayoff, late, approved_lates, final_late, deduction, late_unpaid_absent_days) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
working_days = VALUES(working_days),
present = VALUES(present),
absent = VALUES(absent),
leave_days = VALUES(leave_days),
dayoff = VALUES(dayoff),
late = VALUES(late),
approved_lates = VALUES(approved_lates),
final_late = VALUES(final_late),
deduction = VALUES(deduction),
late_unpaid_absent_days = VALUES(late_unpaid_absent_days)";
$stmt = $db->prepare($sql);
if (!$stmt) {
    echo 'PREPARE ERROR: ' . $db->error . "\n";
} else {
    echo 'PREPARE SUCCESS' . "\n";
}
echo 'DONE!';
?>