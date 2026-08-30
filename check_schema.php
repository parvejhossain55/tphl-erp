<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SHOW CREATE TABLE flats");
if ($res) {
    $row = $res->fetch_assoc();
    echo $row['Create Table'] . "\n\n";
}
