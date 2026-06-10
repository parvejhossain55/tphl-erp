<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$sql = "ALTER TABLE projects ADD COLUMN under_construction TINYINT(1) DEFAULT 0;";

if ($db->query($sql) === TRUE) {
    echo "Column under_construction added successfully\n";
} else {
    echo "Error adding column: " . $db->error . "\n";
}

$db->close();
?>
