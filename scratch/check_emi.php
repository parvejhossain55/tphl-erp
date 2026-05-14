<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $db->query("SELECT * FROM lead_payments WHERE booking_id = 2 AND payment_type = 'emi'");
while($row = $res->fetch_assoc()) print_r($row);

$res2 = $db->query("SELECT * FROM emi_schedules WHERE lead_booking_id = 2");
while($row = $res2->fetch_assoc()) print_r($row);
