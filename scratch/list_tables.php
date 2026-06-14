<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$res = $db->query('SHOW TABLES');
$tables = $res->fetch_all();
foreach ($tables as $t) {
    echo $t[0] . "\n";
}
$db->close();
