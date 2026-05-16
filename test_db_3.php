<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $mysqli->query("SELECT id, agreement_value, discount_amount FROM lead_bookings");
while($row = $res->fetch_assoc()) { print_r($row); }
$res = $mysqli->query("SELECT id, booking_id, amount FROM lead_payments");
while($row = $res->fetch_assoc()) { print_r($row); }
