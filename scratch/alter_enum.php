<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
// Add shop_service_charge to payment_type enum
$sql = "ALTER TABLE lead_payments MODIFY COLUMN payment_type ENUM('booking_money', 'registration', 'emi', 'land_share', 'development_charge', 'emi_due_settlement', 'shop_service_charge') NOT NULL";
if ($db->query($sql) === TRUE) {
    echo "ENUM updated successfully";
} else {
    echo "Error updating ENUM: " . $db->error;
}
