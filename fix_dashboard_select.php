<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/Dashboard.php';
$content = file_get_contents($file);

$old_query = "SELECT paid_at as date, paid_amount as amount, project_id\n                      FROM project_extra_assets";
$new_query = "SELECT paid_at as date, (paid_amount - COALESCE(return_amount, 0)) as amount, project_id\n                      FROM project_extra_assets";
$content = str_replace($old_query, $new_query, $content);

// Also look for "pea.paid_amount as amount" 
$old_query2 = "SELECT \n                  'Office Asset Procurement' as category,\n                  pea.paid_amount as amount,\n                  pea.paid_at as date,";
$new_query2 = "SELECT \n                  'Office Asset Procurement' as category,\n                  (pea.paid_amount - COALESCE(pea.return_amount, 0)) as amount,\n                  pea.paid_at as date,";
$content = str_replace($old_query2, $new_query2, $content);

file_put_contents('temp_dashboard.php', $content);
