<?php

$mysqli = new mysqli("127.0.0.1", "root", "", "tphl_db");

$res = $mysqli->query("SHOW COLUMNS FROM departments");
while ($r = $res->fetch_assoc()) {
    echo $r['Field'] . "\n";
}
