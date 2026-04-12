<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$db->query("UPDATE lead_payments SET payment_status='paid' WHERE verification_status='verified' AND payment_status IN ('pending','partially_paid')");
echo "Rows fixed: " . $db->affected_rows . "\n";
$db->close();
