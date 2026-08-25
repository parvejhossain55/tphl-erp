
<?php
$file = "c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php";
$content = file_get_contents($file);

$searchBreak = "top: px !important;";
$replaceBreak = "top: ${navHeight}px !important;";
$content = str_replace($searchBreak, $replaceBreak, $content);

file_put_contents($file, $content);
echo "Fixed PHP string evaluation.";

