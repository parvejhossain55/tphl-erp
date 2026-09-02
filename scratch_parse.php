<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/schema/custom-migration/tphl_db.sql';
$fp = fopen($file, 'r');
if (!$fp) die("Cannot open file");

$flats = [];
$bookings = [];

while (($line = fgets($fp)) !== false) {
    if (stripos($line, 'INSERT INTO `flats`') !== false) {
        $data = substr($line, strpos($line, 'VALUES ') + 7);
        $data = rtrim(trim($data), ";\r\n"); // remove trailing semi-colon
        // a simple trick to split on ),( without messing up strings that might have ),(
        // wait, let's just use a regex for values:
        preg_match_all("/\((.*?)\)(?:,|$)/", $data, $matches);
        foreach ($matches[1] as $row) {
            $cols = str_getcsv($row, ",", "'");
            if (isset($cols[1]) && trim($cols[1]) == '66') {
                $flats[trim($cols[0])] = isset($cols[12]) ? trim($cols[12], "'") : 'unknown';
            }
        }
    }
    if (stripos($line, 'INSERT INTO `lead_bookings`') !== false) {
        $data = substr($line, strpos($line, 'VALUES ') + 7);
        $data = rtrim(trim($data), ";\r\n");
        preg_match_all("/\((.*?)\)(?:,|$)/", $data, $matches);
        foreach ($matches[1] as $row) {
            $cols = str_getcsv($row, ",", "'");
            if (isset($cols[2]) && trim($cols[2]) == '66') {
                $bookings[] = [
                    'booking_id' => trim($cols[0]),
                    'lead_id' => trim($cols[1]),
                    'flat_id' => trim($cols[3])
                ];
            }
        }
    }
}
fclose($fp);

echo "Flats in Project 66:\n";
print_r($flats);

echo "\nBookings in Project 66:\n";
foreach ($bookings as $b) {
    $flat_name = isset($flats[$b['flat_id']]) ? $flats[$b['flat_id']] : 'Unknown ('.$b['flat_id'].')';
    echo "Booking ID: {$b['booking_id']}, Lead ID: {$b['lead_id']}, Flat: {$flat_name} (ID: {$b['flat_id']})\n";
}
