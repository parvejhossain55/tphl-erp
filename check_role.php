<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SELECT name FROM roles WHERE id = 17");
if ($row = $res->fetch_assoc()) {
    echo "Role 17: " . $row['name'] . "\n";
} else {
    echo "Role 17 not found\n";
}
$db->close();
