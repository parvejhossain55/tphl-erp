<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$content = file_get_contents($file);

$old_office = "SUM(paid_amount) as total_amount\n              FROM project_extra_assets";
$new_office = "SUM(paid_amount - COALESCE(return_amount, 0)) as total_amount\n              FROM project_extra_assets";
$content = str_replace($old_office, $new_office, $content);

file_put_contents('temp_statement_2.php', $content);
