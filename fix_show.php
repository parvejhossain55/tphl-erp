<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

$content = str_replace(
    "$('#workspaceTable tfoot').hide();",
    "$('#workspaceTable tfoot').show();",
    $content
);

file_put_contents($file, $content);
echo "Set tfoot to show() in workspace.php";
