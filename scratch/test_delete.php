<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// 1. Insert a test record
$db->query("INSERT INTO design_types (name, status) VALUES ('TEST_DESIGN_TYPE', 'active')");
$id = $db->insert_id;
echo "Inserted test design type with ID: $id\n";

// 2. Try to run DELETE on it using mysqli
$stmt = $db->prepare("DELETE FROM design_types WHERE id = ?");
$stmt->bind_param("i", $id);
$res = $stmt->execute();
if ($res) {
    echo "Deleted successfully!\n";
} else {
    echo "Failed to delete: " . $db->error . "\n";
}
