<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

$content = str_replace(
    'remarks, attendance_remarks = ?, is_finalized = 1 WHERE id = ?',
    'remarks, attendance_remarks = ?, is_excluded = ?, is_finalized = 1 WHERE id = ?',
    $content
);
$content = str_replace(
    'attendance_remarks = ?, is_finalized = 1 WHERE id = ?',
    'attendance_remarks = ?, is_excluded = ?, is_finalized = 1 WHERE id = ?',
    $content
);

$content = str_replace(
    '$totalEarning, $totalDeduction, $netSalary, $salaryHoldString, $bankAmount, $cashAmount, $lateUnpaidAbsentDays, $remarks, $attRemarks, $payslipId);',
    '$totalEarning, $totalDeduction, $netSalary, $salaryHoldString, $bankAmount, $cashAmount, $lateUnpaidAbsentDays, $remarks, $attRemarks, $isExcluded, $payslipId);',
    $content
);
$content = str_replace(
    'bind_param("dddsdddssi", $totalEarning',
    'bind_param("dddsdddsii", $totalEarning',
    $content
);


$content = str_replace(
    'late_unpaid_absent_days, remarks, attendance_remarks) VALUES',
    'late_unpaid_absent_days, remarks, attendance_remarks, is_excluded) VALUES',
    $content
);
$content = str_replace(
    '?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    '?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    $content
);

$content = str_replace(
    '$bankAmount, $cashAmount, $lateUnpaidAbsentDays, $remarks, $attRemarks);',
    '$bankAmount, $cashAmount, $lateUnpaidAbsentDays, $remarks, $attRemarks, $isExcluded);',
    $content
);
$content = str_replace(
    'bind_param("iisdddsdddss", $employeeId',
    'bind_param("iisdddsdddsi", $employeeId',
    $content
);

file_put_contents($file, $content);
echo "Fixed mappings piecemeal.";
