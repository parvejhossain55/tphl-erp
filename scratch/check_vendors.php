<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
$res=$conn->query('SELECT name FROM vendors');
while($r=$res->fetch_assoc()) echo $r['name']."\n";
echo "== Contractors ==\n";
$res=$conn->query('SELECT name FROM contractors');
while($r=$res->fetch_assoc()) echo $r['name']."\n";
?>
