<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $db->query("SELECT lp.*, es.emi_no FROM lead_payments lp LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id WHERE lp.booking_id = 2 ORDER BY lp.id DESC LIMIT 5");
while($row = $res->fetch_assoc()){
    print_r([
        'id' => $row['id'],
        'payment_type' => $row['payment_type'],
        'settlement_type' => $row['settlement_type']
    ]);
}
