<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) { die('Connection failed: ' . $db->connect_error); }

$sql = "ALTER TABLE lead_payments ADD COLUMN bulk_emi_discount DECIMAL(12,2) DEFAULT 0.00 AFTER due_emi_amount";
if ($db->query($sql)) {
    echo "OK: bulk_emi_discount column added.\n";
} else {
    echo "ERR: " . $db->error . "\n";
}
$db->close();
