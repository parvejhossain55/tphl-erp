<?php
$file = 'c:\\xampp\\htdocs\\TPHL-ERP\\app\\views\\layouts\\sidebar.php';
$content = file_get_contents($file);

// Find Meal Tracker start
$mealStart = strpos($content, '<!-- Meal Tracker Menu');
$mealEnd = strpos($content, '<!--Payroll CATEGORY -->');

$mealBlock = substr($content, $mealStart, $mealEnd - $mealStart);

$payrollStart = $mealEnd;
$payrollEnd = strpos($content, '<?php if (hasPermission("User Manage")');

$payrollBlock = substr($content, $payrollStart, $payrollEnd - $payrollStart);

if ($mealStart !== false && $mealEnd !== false && $payrollEnd !== false) {
    // Now replace both blocks with Payroll then Meal
    $newContent = substr($content, 0, $mealStart) . $payrollBlock . $mealBlock . substr($content, $payrollEnd);
    file_put_contents($file, $newContent);
    echo "Swapped successfully.";
} else {
    echo "Failed to find boundaries.";
}
?>
