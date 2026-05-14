<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');

$sql = "UPDATE emi_schedules es
        JOIN lead_payments lp ON es.lead_payment_id = lp.id
        SET es.due_emi_amount = lp.due_emi_amount
        WHERE lp.due_emi_amount > 0 AND es.due_emi_amount = 0";

if ($db->query($sql) === TRUE) {
    echo "Fixed " . $db->affected_rows . " EMI schedules with incorrect due amounts.\n";
} else {
    echo "Error: " . $db->error . "\n";
}
