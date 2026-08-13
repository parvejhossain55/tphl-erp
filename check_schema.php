<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SELECT COLUMN_TYPE, TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'tphl_db' AND COLUMN_NAME = 'payment_status'");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
