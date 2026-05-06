<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $conn->query("SHOW CREATE TABLE purchase_orders");
if ($res) echo $res->fetch_assoc()['Create Table']."\n\n";

$res = $conn->query("SHOW CREATE TABLE vendors");
if ($res) echo $res->fetch_assoc()['Create Table']."\n\n";
$conn->close();
?>
