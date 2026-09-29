<?php
require 'app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SHOW CREATE TABLE con_material_categories");
if ($row = $res->fetch_assoc()) {
    echo $row['Create Table'];
}
