<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $mysqli->query("SHOW COLUMNS FROM lead_payments LIKE 'payment_type'");
print_r($res->fetch_assoc());
