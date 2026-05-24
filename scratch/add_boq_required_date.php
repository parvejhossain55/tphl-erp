<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
// Add required_date column
$res = $db->query("ALTER TABLE boq_items ADD COLUMN IF NOT EXISTS required_date DATE NULL");
if ($res) {
    echo "Column required_date added to boq_items successfully!\n";
} else {
    echo "Error adding column: " . $db->error . "\n";
}
// Describe boq_items to verify
$res2 = $db->query("DESCRIBE boq_items");
while ($row = $res2->fetch_assoc()) {
    print_r($row);
}
