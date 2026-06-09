<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== purchase_order_items columns ===\n";
$res = $db->query("DESCRIBE purchase_order_items");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n=== material_receiving_items columns ===\n";
$res = $db->query("DESCRIBE material_receiving_items");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n=== Data in purchase_order_items ===\n";
$res = $db->query("SELECT * FROM purchase_order_items");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
