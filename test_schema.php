<?php
$mysqli = new mysqli("localhost", "root", "", "tphl_db");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}
$res = $mysqli->query("SHOW COLUMNS FROM lead_payments LIKE 'payment_status'");
$row = $res->fetch_assoc();
print_r($row);
$mysqli->close();
