<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $conn->query('SHOW TABLES');
while($row = $res->fetch_row()) echo $row[0] . "\n";
$conn->close();
?>
