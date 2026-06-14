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

showColumns($db, 'con_materials');
showColumns($db, 'con_material_categories');
showColumns($db, 'boq_items');
showColumns($db, 'boq_master');

$db->close();
