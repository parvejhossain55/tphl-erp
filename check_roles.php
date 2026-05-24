<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$r = $mysqli->query("SELECT * FROM roles WHERE name LIKE '%Manager%'");
while($row = $r->fetch_assoc()) {
    print_r($row);
}
