<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$res = $db->query("DESCRIBE lead_payments");
while($row = $res->fetch_assoc()){
    if($row['Field'] == 'payment_type') print_r($row);
}
