<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$result = $mysqli->query("SELECT id, name FROM vendors LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']} | Name: {$row['name']}\n";
}
