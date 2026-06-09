<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "--- COLUMNS in design_types ---\n";
$res = $db->query("DESCRIBE design_types");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}

echo "\n--- FOREIGN KEYS in project_drawings referencing design_types ---\n";
$res2 = $db->query("
    SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE REFERENCED_TABLE_NAME = 'design_types'
");
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        print_r($row);
    }
}
