<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

$content = str_replace('<input type="checkbox"', '<input type="checkbox" autocomplete="off"', $content);

// Let's also namespace the storage key to the project, just in case they filter between projects
$search = "const EXCLUDED_STORAGE_KEY = 'tphl_payroll_excluded_employees';";
$replace = "const EXCLUDED_STORAGE_KEY = 'tphl_payroll_excluded_' + (document.getElementById('payrollProject') ? (document.getElementById('payrollProject').value || 'all') : 'all');";
$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Fixed autocomplete and namespaces.";
