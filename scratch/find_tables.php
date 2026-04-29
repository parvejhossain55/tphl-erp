<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $mysqli->query('SHOW TABLES');
while($row = $res->fetch_row()) {
    $table = $row[0];
    if (strpos($table, 'con_') === 0 || strpos($table, 'soil') !== false || strpos($table, 'architect') !== false || strpos($table, 'struct') !== false || strpos($table, 'draft') !== false || strpos($table, 'estimat') !== false || strpos($table, 'land') !== false) {
        echo $table . PHP_EOL;
    }
}
