<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

function showColumns($db, $table) {
    echo "--- Table: $table ---\n";
    $res = $db->query("DESCRIBE `$table`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "  Error: " . $db->error . "\n";
    }
}

showColumns($db, 'purchase_requests');
showColumns($db, 'purchase_orders');
showColumns($db, 'purchase_order_items');
showColumns($db, 'material_receiving');
showColumns($db, 'material_receiving_items');

$db->close();
