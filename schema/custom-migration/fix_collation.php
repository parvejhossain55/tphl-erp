<?php
$inputFile = 'c:\xampp\htdocs\TPHL-ERP\schema\custom-migration\tphl_db.sql';
$outputFile = 'c:\xampp\htdocs\TPHL-ERP\schema\custom-migration\tphl_db_fixed.sql';

$in = fopen($inputFile, 'r');
$out = fopen($outputFile, 'w');

if ($in && $out) {
    while (($line = fgets($in)) !== false) {
        $line = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_unicode_ci', $line);
        fwrite($out, $line);
    }
    fclose($in);
    fclose($out);
    echo "Done replacing collation.";
} else {
    echo "Failed to open file.";
}
