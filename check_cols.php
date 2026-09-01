<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$result = $db->query('SHOW COLUMNS FROM attendance_monthly_update');
while($row = $result->fetch_assoc()){
    echo $row['Field'] . "\n";
}
