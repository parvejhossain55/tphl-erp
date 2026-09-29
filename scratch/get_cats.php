<?php
require 'app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SELECT id, name, total_lead_days FROM con_material_categories");
while($row = $res->fetch_assoc()) {
    echo $row['id'] . " - " . $row['name'] . " - " . $row['total_lead_days'] . "\n";
}
