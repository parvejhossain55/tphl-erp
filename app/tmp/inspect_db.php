<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
if ($db->connect_error) { die("Connection failed"); }
$res = $db->query("DESCRIBE lead_bookings");
if ($res) {
    while($row = $res->fetch_assoc()) echo $row['Field'] . ' ' . $row['Type'] . "\n";
} else { echo "query failed"; }
