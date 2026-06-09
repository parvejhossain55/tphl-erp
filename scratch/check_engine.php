<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SHOW TABLE STATUS WHERE Name IN ('design_types', 'project_drawings', 'work_types')");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "Table: " . $row['Name'] . " | Engine: " . $row['Engine'] . "\n";
    }
}
