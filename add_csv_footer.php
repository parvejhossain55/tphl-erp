<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

$searchCSV = "                        title: 'Payroll_Workplace_Export',
                        exportOptions: {";
$replaceCSV = "                        title: 'Payroll_Workplace_Export',
                        footer: true,
                        exportOptions: {";
$content = str_replace($searchCSV, $replaceCSV, $content);

file_put_contents($file, $content);
echo "CSV footer config added.";
