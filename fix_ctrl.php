<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

$search1 = "                \$lateUnpaidAbsentDays = (float) (\$r['late_unpaid_absent_days'] ?? 0);
                \$remarks = \$this->db->real_escape_string(\$r['remarks'] ?? '');
                \$attRemarks = \$this->db->real_escape_string(\$r['attendance_remarks'] ?? '');
                \$details = \$r['details'] ?? [];";

$replace1 = "                \$lateUnpaidAbsentDays = (float) (\$r['late_unpaid_absent_days'] ?? 0);
                \$remarks = \$this->db->real_escape_string(\$r['remarks'] ?? '');
                \$attRemarks = \$this->db->real_escape_string(\$r['attendance_remarks'] ?? '');
                \$isExcluded = isset(\$r['is_excluded']) ? (int)\$r['is_excluded'] : 0;
                \$details = \$r['details'] ?? [];";

$content = str_replace($search1, $replace1, $content);

$search2 = "                    \$stmt = \$this->db->prepare(\"UPDATE payslips SET employee_id=?, payroll_project_id=?, payslip_month=?, emp_gross=?, emp_deduction=?, net_salary=?, salary_hold=?, bank_amount=?, cash_amount=?, late_unpaid_absent_days=?, remarks=?, attendance_remarks=? WHERE id=?\");
                    if (\$stmt) {
                        \$stmt->bind_param(\"iisdddsdddssi\", \$employeeId, \$projectId, \$payslipMonth, \$totalEarning, \$totalDeduction, \$netSalary, \$salaryHoldString, \$bankAmount, \$cashAmount, \$lateUnpaidAbsentDays, \$remarks, \$attRemarks, \$payslipId);";

$replace2 = "                    \$stmt = \$this->db->prepare(\"UPDATE payslips SET employee_id=?, payroll_project_id=?, payslip_month=?, emp_gross=?, emp_deduction=?, net_salary=?, salary_hold=?, bank_amount=?, cash_amount=?, late_unpaid_absent_days=?, remarks=?, attendance_remarks=?, is_excluded=? WHERE id=?\");
                    if (\$stmt) {
                        \$stmt->bind_param(\"iisdddsdddssii\", \$employeeId, \$projectId, \$payslipMonth, \$totalEarning, \$totalDeduction, \$netSalary, \$salaryHoldString, \$bankAmount, \$cashAmount, \$lateUnpaidAbsentDays, \$remarks, \$attRemarks, \$isExcluded, \$payslipId);";

$content = str_replace($search2, $replace2, $content);

$search3 = "                    \$stmt = \$this->db->prepare(\"INSERT INTO payslips (employee_id, payroll_project_id, payslip_month, emp_gross, emp_deduction, net_salary, salary_hold, bank_amount, cash_amount, late_unpaid_absent_days, remarks, attendance_remarks, is_finalized) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)\");
                    if (\$stmt) {
                        \$stmt->bind_param(\"iisdddsdddss\", \$employeeId, \$projectId, \$payslipMonth, \$totalEarning, \$totalDeduction, \$netSalary, \$salaryHoldString, \$bankAmount, \$cashAmount, \$lateUnpaidAbsentDays, \$remarks, \$attRemarks);";

$replace3 = "                    \$stmt = \$this->db->prepare(\"INSERT INTO payslips (employee_id, payroll_project_id, payslip_month, emp_gross, emp_deduction, net_salary, salary_hold, bank_amount, cash_amount, late_unpaid_absent_days, remarks, attendance_remarks, is_excluded, is_finalized) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)\");
                    if (\$stmt) {
                        \$stmt->bind_param(\"iisdddsdddsis\", \$employeeId, \$projectId, \$payslipMonth, \$totalEarning, \$totalDeduction, \$netSalary, \$salaryHoldString, \$bankAmount, \$cashAmount, \$lateUnpaidAbsentDays, \$remarks, \$attRemarks, \$isExcluded);";

// Wait, the bind param for replace3 has 13 params. "iisdddsdddsis": i (employeeId), i(projectId), s(payslipMonth), d(emp_gross), d(emp_deduction), d(net_salary), s(salary_hold), d(bank_amount), d(cash_amount), d(late), s(remarks), s(attRemarks), i(is_excluded)
// That's 13.
$replace3 = "                    \$stmt = \$this->db->prepare(\"INSERT INTO payslips (employee_id, payroll_project_id, payslip_month, emp_gross, emp_deduction, net_salary, salary_hold, bank_amount, cash_amount, late_unpaid_absent_days, remarks, attendance_remarks, is_excluded, is_finalized) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)\");
                    if (\$stmt) {
                        \$stmt->bind_param(\"iisdddsdddssi\", \$employeeId, \$projectId, \$payslipMonth, \$totalEarning, \$totalDeduction, \$netSalary, \$salaryHoldString, \$bankAmount, \$cashAmount, \$lateUnpaidAbsentDays, \$remarks, \$attRemarks, \$isExcluded);";

$content = str_replace($search3, $replace3, $content);
file_put_contents($file, $content);
echo "Backend payslip controller replaced successfully.";
