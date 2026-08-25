<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

// Replace class="table__wrapper table-responsive" with just class="table__wrapper"
$content = str_replace('class="table__wrapper table-responsive"', 'class="table__wrapper" style="overflow: visible;"', $content);

file_put_contents($file, $content);
echo "Table responsive wrapper modified.";
