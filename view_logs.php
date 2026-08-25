<?php
// Let's create a script that includes PayrollController and calls saveWorkspacePayslips directly or mimics it.
// To bypass session/auth, we can just look at the php_errors.log
$logFile = 'c:\xampp\php\logs\php_error_log';
if (!file_exists($logFile)) {
    echo "No log file found at $logFile.";
} else {
    // Tail the last 20 lines
    $lines = file($logFile);
    $last_lines = array_slice($lines, -30);
    echo implode("", $last_lines);
}
