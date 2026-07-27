<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$content = file_get_contents($file);

// 1. In getStatementData (starts around line 1180), change '$category === 'all'' to '($category === 'all' || $category === 'cashout')' 
// for the cashout arrays:
$content = preg_replace("/\\\$category === 'all'/i", "(\$category === 'all' || \$category === 'cashout')", $content);
file_put_contents('temp.php', $content);
