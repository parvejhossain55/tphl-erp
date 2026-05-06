<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $conn->query('SHOW TABLES LIKE "%purchase%"');
while($r = $res->fetch_row()) echo "Table: " . $r[0] . "\n";

$res = $conn->query('SHOW TABLES LIKE "%request%"');
while($r = $res->fetch_row()) echo "Table: " . $r[0] . "\n";
$conn->close();
?>
