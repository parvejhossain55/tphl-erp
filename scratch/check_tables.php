<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SHOW TABLES LIKE '%follow%'");
while($row = $res->fetch_row()) {
    echo $row[0] . "\n";
}
