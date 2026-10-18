<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SELECT id, lead_booking_id, lead_payment_id, emi_no, due_date, emi_amount, due_emi_amount, status FROM emi_schedules WHERE lead_booking_id = 3484 ORDER BY emi_no ASC");
while ($r = $res->fetch_assoc()) {
    echo json_encode($r) . "\n";
}
