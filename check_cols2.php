<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$r = $mysqli->query("SHOW COLUMNS FROM purchase_orders");
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
