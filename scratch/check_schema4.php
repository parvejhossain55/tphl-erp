<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $conn->query("SHOW CREATE TABLE purchase_requests");
if ($res) echo $res->fetch_assoc()['Create Table']."\n\n";

$res = $conn->query("SHOW CREATE TABLE purchase_order_items");
if ($res) echo $res->fetch_assoc()['Create Table']."\n\n";
$conn->close();
?>
