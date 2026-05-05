<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
$res=$conn->query('SHOW CREATE TABLE vendor_payment_requests');
echo $res->fetch_row()[1];
?>
