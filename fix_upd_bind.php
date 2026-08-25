<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

$searchUpdate = 'attendance_remarks=? WHERE id=?';
$replaceUpdate = 'attendance_remarks=?, is_excluded=? WHERE id=?';
$content = str_replace($searchUpdate, $replaceUpdate, $content);

file_put_contents($file, $content);
echo "Added is_excluded target parameter back into SQL Update query.";
