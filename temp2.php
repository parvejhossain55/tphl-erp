<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $conn->query('DESCRIBE employees');
while ($row = $res->fetch_assoc())
    echo $row['Field'] . ' | ';
?>