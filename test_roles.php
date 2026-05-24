<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$result = $mysqli->query('SELECT id, name FROM roles');
while($row = $result->fetch_assoc()) {
    echo $row['id'] . ' - ' . $row['name'] . "\n";
}
