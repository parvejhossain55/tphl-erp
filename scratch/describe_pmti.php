<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$res = $db->query("DESCRIBE phase_monthly_target_items");
print_r($res->fetch_all(MYSQLI_ASSOC));
