<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== FIRST 14 COLUMNS OF PROJECTS TABLE ===\n";
$res = $db->query("SHOW COLUMNS FROM projects");
$cols = $res->fetch_all(MYSQLI_ASSOC);
print_r(array_slice($cols, 0, 15));
?>
