<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SHOW COLUMNS FROM employees");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
