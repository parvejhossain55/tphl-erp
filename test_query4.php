<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli('localhost', 'root', '', 'tphl_erp'); // Or wait, let's use final_erp_db or whatever their config says.
} catch(Exception $e) {
    try {
        $db = new mysqli('localhost', 'root', '', 'final_erp_db');
    } catch(Exception $e) {
        die($e->getMessage());
    }
}
$res = $db->query("DESCRIBE lead_bookings");
while($row = $res->fetch_assoc()){
    echo $row['Field'] . "\n";
}
