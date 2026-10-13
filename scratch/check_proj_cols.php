<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("DESCRIBE projects");
while($r = $res->fetch_assoc()) {
    echo $r['Field'] . "\n";
}
