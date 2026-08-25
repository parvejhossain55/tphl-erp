<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

// Replace insert query
$searchInsert = 'remarks, attendance_remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
$replaceInsert = 'remarks, attendance_remarks, is_excluded) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
$content = str_replace($searchInsert, $replaceInsert, $content);

// Replace insert bindings
$searchInsertBind = '$stmt->bind_param("iisdddsdddss", $employeeId, $projectId, $payslipMonth, $totalEarning, $totalDeduction, $netSalary, $salaryHoldString, $bankAmount, $cashAmount, $lateUnpaidAbsentDays, $remarks, $attRemarks);';
$replaceInsertBind = '$stmt->bind_param("iisdddsdddsi", $employeeId, $projectId, $payslipMonth, $totalEarning, $totalDeduction, $netSalary, $salaryHoldString, $bankAmount, $cashAmount, $lateUnpaidAbsentDays, $remarks, $attRemarks, $isExcluded);';
$content = str_replace($searchInsertBind, $replaceInsertBind, $content);

// Replace update query
$searchUpdate = 'remarks = ?, attendance_remarks = ?, is_finalized = 1 WHERE id = ?';
$replaceUpdate = 'remarks = ?, attendance_remarks = ?, is_excluded = ?, is_finalized = 1 WHERE id = ?';
$content = str_replace($searchUpdate, $replaceUpdate, $content);

// Replace update bindings
$searchUpdateBind = '$stmt->bind_param("dddsdddssi", $totalEarning, $totalDeduction, $netSalary, $salaryHoldString, $bankAmount, $cashAmount, $lateUnpaidAbsentDays, $remarks, $attRemarks, $payslipId);';
$replaceUpdateBind = '$stmt->bind_param("dddsdddsiii", $totalEarning, $totalDeduction, $netSalary, $salaryHoldString, $bankAmount, $cashAmount, $lateUnpaidAbsentDays, $remarks, $attRemarks, $isExcluded, $payslipId);';
$content = str_replace($searchUpdateBind, $replaceUpdateBind, $content);

file_put_contents($file, $content);
echo "PHP fixed with str_replace.";
