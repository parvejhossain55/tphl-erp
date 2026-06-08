<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$res = $db->query("SELECT project_id, COUNT(*) as count FROM project_phases GROUP BY project_id");
print_r($res->fetch_all(MYSQLI_ASSOC));
