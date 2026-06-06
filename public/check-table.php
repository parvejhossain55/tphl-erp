<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) { die('Connection failed: ' . $db->connect_error); }

// Make cashout_request_id nullable
$r1 = $db->query("ALTER TABLE expense_adjustments MODIFY COLUMN cashout_request_id INT(11) DEFAULT NULL");
echo $r1 ? "OK: cashout_request_id now nullable\n" : "Error: " . $db->error . "\n";

// Verify structure
$r = $db->query('DESCRIBE expense_adjustments');
echo "\n=== Final table structure ===\n";
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Null:' . $row['Null'] . "\n";
}

echo "\nDone! Delete public/check-table.php when finished.\n";
