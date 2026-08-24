<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$lead_ids = [30664, 30663, 17606];
$lead_ids_str = implode(',', $lead_ids);

echo "Starting Cleanup...\n";

// Get booking IDs
$res = $db->query("SELECT id, flat_id FROM lead_bookings WHERE lead_id IN ($lead_ids_str)");
$booking_ids = [];
$flat_ids = [];
while($row = $res->fetch_assoc()) {
    $booking_ids[] = $row['id'];
    $flat_ids[] = $row['flat_id'];
}

if (!empty($booking_ids)) {
    $b_ids_str = implode(',', $booking_ids);
    
    // 1. Delete from lead_payments
    $db->query("DELETE FROM lead_payments WHERE booking_id IN ($b_ids_str)");
    echo "Deleted lead_payments: " . $db->affected_rows . "\n";
    
    // 2. Delete from approval_requests
    $db->query("DELETE FROM approval_requests WHERE source_table = 'lead_bookings' AND source_id IN ($b_ids_str)");
    echo "Deleted approval_requests: " . $db->affected_rows . "\n";
    
    // 3. Delete from lead_bookings
    $db->query("DELETE FROM lead_bookings WHERE id IN ($b_ids_str)");
    echo "Deleted lead_bookings: " . $db->affected_rows . "\n";
}

// 4. Reset flats is_booked = 0
if (!empty($flat_ids)) {
    $f_ids_str = implode(',', array_unique($flat_ids));
    $db->query("UPDATE flats SET is_booked = 0 WHERE id IN ($f_ids_str)");
    echo "Updated flats (is_booked = 0): " . $db->affected_rows . "\n";
} else {
    echo "No flats to update based on bookings.\n";
}

echo "Cleanup completed successfully!\n";
