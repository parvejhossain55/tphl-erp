<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $mysqli->query("SELECT agreement_value, discount_amount FROM lead_bookings WHERE id=3");
print_r($res->fetch_assoc());
$res = $mysqli->query("SELECT amount FROM lead_payments WHERE booking_id=3");
while($row = $res->fetch_assoc()) { print_r($row); }
