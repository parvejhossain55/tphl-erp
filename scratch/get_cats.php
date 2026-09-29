<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SELECT id, name FROM con_material_categories");
while($row = $res->fetch_assoc()) {
    echo $row['name'] . "\n";
}
