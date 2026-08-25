<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

// 1. Remove payment_status
$content = str_replace(
    'AND (p.status != \'paid\' OR p.payment_status != \'paid\')',
    'AND p.status != \'paid\'',
    $content
);
$content = str_replace(
    'SET status = \'paid\', payment_status = \'paid\', hr_status = \'approved\', mgmt_status = \'approved\',',
    'SET status = \'paid\', paid_via_account_id = ?, disbursed_by = ?, disbursed_at = NOW(), journal_id = ?',
    $content
);

// 2. Add is_excluded logic to saveWorkspacePayslips
$content = str_replace(
    '$attRemarks = $row[\'attendance_remarks\'] ?? \'\';',
    '$attRemarks = $row[\'attendance_remarks\'] ?? \'\';
                $isExcluded = isset($row[\'is_excluded\']) ? (int)$row[\'is_excluded\'] : 0;',
    $content
);
$content = str_replace(
    'UPDATE payslips SET total_earning = ?, total_deduction = ?, net_salary = ?, salary_hold = ?, bank_amount = ?, cash_amount = ?, late_unpaid_absent_days = ?, remarks = ?, attendance_remarks = ?, is_finalized = 1 WHERE id = ?',
    'UPDATE payslips SET total_earning = ?, total_deduction = ?, net_salary = ?, salary_hold = ?, bank_amount = ?, cash_amount = ?, late_unpaid_absent_days = ?, remarks = ?, attendance_remarks = ?, is_excluded = ?, is_finalized = 1 WHERE id = ?',
    $content
);
$content = str_replace(
    'bind_param("dddsdddssi", $totalEarning',
    'bind_param("dddsdddsii", $totalEarning',
    $content
);
$content = str_replace(
    '$lateUnpaidAbsentDays, $remarks, $attRemarks, $payslipId);',
    '$lateUnpaidAbsentDays, $remarks, $attRemarks, $isExcluded, $payslipId);',
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
    'bind_param("iisdddsdddss", $employeeId',
    'bind_param("iisdddsdddsi", $employeeId',
    $content
);
$content = str_replace(
    '$lateUnpaidAbsentDays, $remarks, $attRemarks);',
    '$lateUnpaidAbsentDays, $remarks, $attRemarks, $isExcluded);',
    $content
);

file_put_contents($file, $content);
echo "PayrollController restored and successfully patched.";
