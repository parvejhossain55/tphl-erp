<?php
$content = file_get_contents('c:\xampp\htdocs\TPHL-ERP\app\models\Con_InitialEstimate.php');
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (strpos($line, 'function addItems') !== false || strpos($line, 'function addLabors') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
