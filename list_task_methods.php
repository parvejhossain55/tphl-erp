<?php
$lines = file('c:/xampp/htdocs/TPHL-ERP/app/controllers/TaskController.php');
foreach ($lines as $i => $line) {
    if (preg_match('/function\s+([a-zA-Z0-9_]+)\s*\(/', $line, $m)) {
        echo ($i + 1) . ": " . $m[1] . "\n";
    }
}
