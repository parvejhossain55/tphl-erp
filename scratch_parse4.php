<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/schema/custom-migration/tphl_db.sql';
$fp = fopen($file, 'r');
if (!$fp) die("Cannot open file");
$count = 0;
while (($line = fgets($fp)) !== false) {
    if (stripos($line, 'Jahangir Alam') !== false || stripos($line, 'Al Mamun') !== false || stripos($line, '9715021254') !== false) {
        echo substr($line, 0, 500) . "\n";
        $count++;
        if ($count > 10) break;
    }
}
fclose($fp);
