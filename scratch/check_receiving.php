<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $conn->query("SHOW CREATE TABLE material_receiving");
if ($res) echo $res->fetch_assoc()['Create Table']."\n\n";
$conn->close();
?>
