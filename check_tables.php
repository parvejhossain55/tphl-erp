<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$result = $mysqli->query('SHOW TABLES LIKE "%bank%"');
while ($row = $result->fetch_array()) {
    echo $row[0] . "\n";
}