<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

$content = preg_replace(
    "/title:\s*'Payroll_Workplace_Export',\s*exportOptions:\s*\{/",
    "title: 'Payroll_Workplace_Export',\r\n                          footer: true,\r\n                          exportOptions: {",
    $content
);

file_put_contents($file, $content);
echo "Added footer: true flag.";
