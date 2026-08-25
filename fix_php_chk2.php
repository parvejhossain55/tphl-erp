<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

// Update UPDATE statement
$patternUpdateStr = '/UPDATE payslips SET total_earning = \?, total_deduction = \?, net_salary = \?, salary_hold = \?, bank_amount = \?, cash_amount = \?, late_unpaid_absent_days = \?, remarks = \?, attendance_remarks = \?, is_finalized = 1 WHERE id = \?/';
$replacementUpdateStr = 'UPDATE payslips SET total_earning = ?, total_deduction = ?, net_salary = ?, salary_hold = ?, bank_amount = ?, cash_amount = ?, late_unpaid_absent_days = ?, remarks = ?, attendance_remarks = ?, is_excluded = ?, is_finalized = 1 WHERE id = ?';
$content = preg_replace($patternUpdateStr, $replacementUpdateStr, $content);

// Update INSERT statement
$patternInsertStr = '/INSERT INTO payslips \(employee_id, payroll_project_id, payslip_month, total_earning, total_deduction, net_salary, salary_hold, bank_amount, cash_amount, late_unpaid_absent_days, remarks, attendance_remarks\) VALUES \(\?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?, \?\)/';
$replacementInsertStr = 'INSERT INTO payslips (employee_id, payroll_project_id, payslip_month, total_earning, total_deduction, net_salary, salary_hold, bank_amount, cash_amount, late_unpaid_absent_days, remarks, attendance_remarks, is_excluded) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
$content = preg_replace($patternInsertStr, $replacementInsertStr, $content);

file_put_contents($file, $content);
echo "Backend SQL queries correctly updated.";
