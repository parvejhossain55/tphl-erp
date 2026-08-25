<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

\ = new Database();
\ = \->getConnection();

\ = '2026-08-01';
\ = '2026-08-31';
\ = 2;

\ = "SELECT e.id as real_emp_id_fk, e.employee_id, e.full_name, desg.name AS designation, dept.name AS department,
                    e.workplace, e.joining_date, e.gross_salary, e.basic_salary
             FROM employees e
             LEFT JOIN designations desg ON e.designation_id = desg.id
             LEFT JOIN departments dept ON e.department_id = dept.id
             WHERE e.joining_date >= ? AND e.joining_date <= ?
             ORDER BY e.joining_date ASC";

\ = \->prepare(\);
\->bind_param("ss", \, \);
\->execute();
\ = \->get_result();

\ = [];
if (\) {
    while (\ = \->fetch_assoc()) {
        \[] = \;
    }
}

\ = [];
if (\) {
    \ = \->query("SELECT employee_id FROM payslips WHERE payroll_project_id = \ 
                               UNION 
                               SELECT employee_id FROM attendance_monthly_update WHERE payroll_project_id = \");
    if (\) {
        while (\ = \->fetch_assoc()) {
            \[\['employee_id']] = true;
        }
    }
}

\ = [];
\ = 1;

foreach (\ as \) {
    if (\ && !isset(\[\['real_emp_id_fk']])) {
        continue;
    }
    \[] = \;
}

echo json_encode([
    'success' => true,
    'localEmployeesCount' => count(\),
    'projectEmployeeIdsCount' => count(\),
    'projectEmployeeIds' => \,
    'localEmployees' => \,
    'data' => \
], JSON_PRETTY_PRINT);
?>
