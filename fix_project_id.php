<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

$content = str_replace(
    "WHERE p.payroll_project_id = ?",
    "WHERE p.payroll_project_id = ? AND e.employee_status = 'active'",
    $content
);

file_put_contents($file, $content);
echo "Replaced successfully.";
