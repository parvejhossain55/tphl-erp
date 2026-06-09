<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== material_receiving schema ===\n";
$res = $db->query('DESCRIBE material_receiving');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== purchase_orders schema ===\n";
$res = $db->query('DESCRIBE purchase_orders');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
