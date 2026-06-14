<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SELECT * FROM con_materials");
echo "con_materials:\n";
print_r($res->fetch_all(MYSQLI_ASSOC));

$res2 = $db->query("SELECT * FROM con_material_categories");
echo "con_material_categories:\n";
print_r($res2->fetch_all(MYSQLI_ASSOC));

$db->close();
