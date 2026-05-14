<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Fix settlement_type for all payments that are NOT actually EMI settlements
$sql = "UPDATE lead_payments 
        SET settlement_type = NULL 
        WHERE payment_type != 'emi_due_settlement' AND settlement_type = 'emi_due_settlement'";

if ($db->query($sql) === TRUE) {
    echo "Successfully fixed " . $db->affected_rows . " records with wrong settlement_type.\n";
} else {
    echo "Error updating records: " . $db->error . "\n";
}

// Also let's fix transaction ID 6 which has empty payment type. Let's make it shop_service_charge if it was supposed to be!
// It was inserted with amount 10000 on booking 2. Booking 2 is the shop!
$sql2 = "UPDATE lead_payments SET payment_type = 'shop_service_charge' WHERE payment_type = '' AND amount = 10000 AND booking_id = 2";
if ($db->query($sql2) === TRUE) {
    echo "Fixed " . $db->affected_rows . " records with empty payment type.\n";
}

$db->close();
