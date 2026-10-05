<?php
$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Update all flats to booked if they have an active booking
$sql1 = "
UPDATE flats f
JOIN lead_bookings lb ON f.id = lb.flat_id
SET f.status = 'booked', f.is_booked = 1
WHERE lb.booking_status IN ('booked', 'completed')
AND lb.deleted_at IS NULL
AND lb.id NOT IN (
    SELECT crr.booking_id FROM client_refund_requests crr 
    WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded')
)
";

$db->query($sql1);
echo "Updated booked flats: " . $db->affected_rows . "\n";

// Update all flats to available if they don't have an active booking but are marked as booked
$sql2 = "
UPDATE flats f
LEFT JOIN lead_bookings lb ON f.id = lb.flat_id AND lb.booking_status IN ('booked', 'completed') AND lb.deleted_at IS NULL 
AND lb.id NOT IN (
    SELECT crr.booking_id FROM client_refund_requests crr 
    WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded')
)
SET f.status = 'available', f.is_booked = 0
WHERE f.is_booked = 1 AND lb.id IS NULL
";

$db->query($sql2);
echo "Updated available flats: " . $db->affected_rows . "\n";

$db->close();
