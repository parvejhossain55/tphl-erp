<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');

// Check booking 5's project service charge
$r = $db->query("
    SELECT lb.id as booking_id, p.id as project_id, p.name, p.service_charge_per_emi 
    FROM lead_bookings lb 
    JOIN projects p ON p.id = lb.project_id 
    WHERE lb.id = 5
");
$row = $r->fetch_assoc();
echo "Booking #5 → Project: {$row['name']} | service_charge_per_emi: '{$row['service_charge_per_emi']}'\n";

// Check if the column value is actually NULL or 0
$r2 = $db->query("SELECT service_charge_per_emi, IFNULL(service_charge_per_emi, 'NULL') as val FROM projects WHERE id = {$row['project_id']}");
$row2 = $r2->fetch_assoc();
echo "Raw value: '{$row2['service_charge_per_emi']}' | IFNULL: '{$row2['val']}'\n";
echo "Type: " . gettype($row2['service_charge_per_emi']) . "\n";

// Check the getClientByBookingId for this specific booking
$r3 = $db->query("
    SELECT COALESCE(p.service_charge_per_emi, 0) as service_charge_per_emi
    FROM lead_bookings lb
    JOIN projects p ON p.id = lb.project_id  
    WHERE lb.id = 5
");
$row3 = $r3->fetch_assoc();
echo "COALESCE result: '{$row3['service_charge_per_emi']}'\n";

// What SHOULD the service fee be for a 121979 EMI at 12%?
$amount = 121979;
$rate = (float)$row3['service_charge_per_emi'];
echo "\nFor amount={$amount}, rate={$rate}%:\n";
echo "Expected service_fee = round({$amount} * ({$rate} / 100)) = " . round($amount * ($rate / 100)) . "\n";

$db->close();
