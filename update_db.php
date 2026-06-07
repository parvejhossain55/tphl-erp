<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$sql = "ALTER TABLE con_wastage ADD COLUMN wastage_type ENUM('storage_damage', 'selling_scrap') NOT NULL DEFAULT 'selling_scrap' AFTER warehouse_id";
if ($db->query($sql) === TRUE) {
    echo "Table updated successfully\n";
} else {
    echo "Error updating table: " . $db->error . "\n";
}
$db->close();
