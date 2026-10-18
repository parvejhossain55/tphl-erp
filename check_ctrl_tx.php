<?php
$lines = file(__DIR__ . '/app/controllers/AccountClientController.php');
$inMethod = false;
foreach ($lines as $i => $line) {
    if (strpos($line, 'function indexTwoView') !== false) {
        $inMethod = true;
    }
    if ($inMethod) {
        if (strpos($line, '$transactions') !== false) {
            echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
        }
        if (strpos($line, 'function ') !== false && strpos($line, 'function indexTwoView') === false) {
            break;
        }
    }
}
