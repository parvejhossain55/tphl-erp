<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SHOW COLUMNS FROM emp_attendances");
while ($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
?>