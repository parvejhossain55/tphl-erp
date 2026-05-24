<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SHOW TABLES");
if ($res) {
    echo "=== DATABASE TABLES ===\n";
    while ($row = $res->fetch_row()) {
        echo $row[0] . "\n";
    }
} else {
    echo "Error showing tables.\n";
}
