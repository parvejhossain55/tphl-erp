<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $mysqli->query('SHOW TABLES');
while($row = $res->fetch_row()) {
    $table = $row[0];
    if (strpos($table, 'draft') !== false || strpos($table, 'qc') !== false || strpos($table, 'operat') !== false || strpos($table, 'site') !== false || strpos($table, 'work') !== false || strpos($table, 'budget') !== false || strpos($table, 'approval') !== false) {
        echo $table . PHP_EOL;
    }
}
