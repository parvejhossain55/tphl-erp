<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$content = file_get_contents($file);

// Replace the SELECT for project_extra_assets
$old_select = "SELECT paid_amount as total_amount\n            FROM project_extra_assets";
$new_select = "SELECT (paid_amount - COALESCE(return_amount, 0)) as total_amount\n            FROM project_extra_assets";
$content = str_replace($old_select, $new_select, $content);

// And wait, what about getStatementData array building?
// Let's check getStatementData for project_extra_assets
file_put_contents('temp_statement_1.php', $content);
