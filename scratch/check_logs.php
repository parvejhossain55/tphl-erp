<?php
echo "PHP Error Log Path: " . ini_get('error_log') . "\n";
$logPath = ini_get('error_log');
if ($logPath && file_exists($logPath)) {
    echo "Last 30 lines of PHP Error Log:\n";
    $lines = file($logPath);
    $last_lines = array_slice($lines, -30);
    echo implode("", $last_lines);
} else {
    echo "No PHP error log file found or path not configured.\n";
}

$apacheLog = 'C:/xampp/apache/logs/error.log';
if (file_exists($apacheLog)) {
    echo "\nLast 30 lines of Apache Error Log:\n";
    $lines = file($apacheLog);
    $last_lines = array_slice($lines, -30);
    echo implode("", $last_lines);
} else {
    echo "No Apache error log found at standard path.\n";
}
?>
