<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$content = file_get_contents($file);
preg_match('/private function getMonthlyCashOutQuery.*?\) combined\s*";/s', $content, $matches);
$query = $matches[0];
$count = substr_count($query, '?');
echo "Number of ? in getMonthlyCashOutQuery block: " . $count . "\n";
file_put_contents('monthly_query_debug.txt', $query);
