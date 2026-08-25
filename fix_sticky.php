<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

$search = "                autoWidth: false,
                responsive: true,
                scrollX: true,";

$replace = "                autoWidth: false,
                responsive: true,
                scrollX: true,
                scrollY: '65vh',
                scrollCollapse: true,";

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Added scrollY configuration to DataTables.";
