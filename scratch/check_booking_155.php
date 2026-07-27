<?php

$mysqli = new mysqli("127.0.0.1", "root", "", "tphl_db");

echo "=== Lead Booking #155 / Lead 18472 ===\n";
$res = $mysqli->query("SELECT lb.*, l.full_name, p.name AS project_name, p.category_id, p.incentive_percentage AS proj_inc, pc.incentive_percentage AS cat_inc, pc.name AS cat_name
                       FROM lead_bookings lb
                       LEFT JOIN leads l ON lb.lead_id = l.id
                       LEFT JOIN projects p ON lb.project_id = p.id
                       LEFT JOIN project_categories pc ON p.category_id = pc.id
                       WHERE lb.id = 155 OR lb.lead_id = 18472");

while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n=== Payments for Booking 155 ===\n";
$resPay = $mysqli->query("SELECT * FROM lead_payments WHERE booking_id = 155 OR booking_id IN (SELECT id FROM lead_bookings WHERE lead_id = 18472)");
while ($p = $resPay->fetch_assoc()) {
    print_r($p);
}
