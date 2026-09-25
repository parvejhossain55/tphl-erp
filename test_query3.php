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
$res = $db->query("SELECT id, payment_type, amount, payment_status, verification_status FROM lead_payments WHERE booking_id = 2083");
while($row = $res->fetch_assoc()){
    print_r($row);
}
