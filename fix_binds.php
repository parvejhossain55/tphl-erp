<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

// Fix UPDATE Bind
$content = str_replace(
    'bind_param("dddsdddsii", $totalEarning',
    'bind_param("dddsdddssii", $totalEarning',
    $content
);

// Fix INSERT Bind
$content = str_replace(
    'bind_param("iisdddsdddsi", $employeeId',
    'bind_param("iisdddsdddssi", $employeeId',
    $content
);

file_put_contents($file, $content);
echo "Fixed mysqli bindings perfectly.";
