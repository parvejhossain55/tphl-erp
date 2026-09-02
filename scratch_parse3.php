<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/schema/custom-migration/tphl_db.sql';
$fp = fopen($file, 'r');
if (!$fp) die("Cannot open file");

while (($line = fgets($fp)) !== false) {
    if (stripos($line, 'INSERT INTO `flats`') !== false) {
        $data = substr($line, strpos($line, 'VALUES ') + 7);
        $data = rtrim(trim($data), ";\r\n");
        preg_match_all("/\((.*?)\)(?:,|$)/", $data, $matches);
        foreach ($matches[1] as $row) {
            $cols = str_getcsv($row, ",", "'");
            if (isset($cols[0]) && trim($cols[0]) == '4598') {
                $val = trim($cols[12], "'");
                echo "Value for 4598: $val\n";
                for($i=0; $i<strlen($val); $i++) {
                    echo "Char $i: " . $val[$i] . " (" . ord($val[$i]) . ")\n";
                }
                break 2;
            }
        }
    }
}
fclose($fp);
