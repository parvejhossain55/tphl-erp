<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SELECT id, name FROM roles WHERE name LIKE '%Purchase%'");
while($r = $res->fetch_assoc()) {
    echo $r['id'] . " - " . $r['name'] . "\n";
}
