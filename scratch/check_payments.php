<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$res = $db->query("SELECT id, booking_id, payment_type, amount, created_at FROM lead_payments ORDER BY id DESC LIMIT 5");
while($row = $res->fetch_assoc()){
    print_r($row);
}
