<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
// Show all tables that might have payment_type
$res = $db->query("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'tphl_db' AND COLUMN_NAME = 'payment_type'");
while($row = $res->fetch_assoc()){
    print_r($row);
}
