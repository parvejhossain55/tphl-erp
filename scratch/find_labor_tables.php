<?php
require 'app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SHOW TABLES LIKE '%labor%'");
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
echo "---\n";
$res2 = $db->query("SHOW TABLES LIKE '%req%'");
while($row2 = $res2->fetch_array()) {
    echo $row2[0] . "\n";
}
