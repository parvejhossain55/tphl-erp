<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$res = $db->query("SELECT id, name, workflow_version FROM projects");
$projects = $res->fetch_all(MYSQLI_ASSOC);
echo "=== PROJECTS ===\n";
print_r($projects);
