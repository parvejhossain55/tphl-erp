<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$res = $db->query("SHOW COLUMNS FROM projects");
$cols = $res->fetch_all(MYSQLI_ASSOC);
echo "=== ALL COLUMNS OF PROJECTS ===\n";
foreach ($cols as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}
