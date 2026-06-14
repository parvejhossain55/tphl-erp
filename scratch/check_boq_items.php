<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SELECT * FROM boq_items");
print_r($res->fetch_all(MYSQLI_ASSOC));

$db->close();
