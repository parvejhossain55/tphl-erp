<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/Dashboard.php';
$content = file_get_contents($file);

$content = preg_replace("/SELECT paid_at as date, paid_amount as amount, project_id\s*FROM project_extra_assets/", "SELECT paid_at as date, (paid_amount - COALESCE(return_amount, 0)) as amount, project_id\n                      FROM project_extra_assets", $content);

$content = preg_replace("/pea\.paid_amount as amount,/", "(pea.paid_amount - COALESCE(pea.return_amount, 0)) as amount,", $content);

file_put_contents('temp_dashboard_2.php', $content);
