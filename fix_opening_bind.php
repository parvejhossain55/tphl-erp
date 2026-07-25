<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$content = file_get_contents($file);

$content = str_replace(
    '$stmt->bind_param("sss", $startDate, $startDate, $startDate);',
    '$stmt->bind_param("sssssss", $startDate, $startDate, $startDate, $startDate, $startDate, $startDate, $startDate);',
    $content
);

file_put_contents('temp_fix3.php', $content);
