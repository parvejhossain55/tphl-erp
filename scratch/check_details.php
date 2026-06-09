<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$res = $db->query("SELECT * FROM project_phase_item_details WHERE phase_item_id = 2");
print_r($res->fetch_all(MYSQLI_ASSOC));
