<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db'); 
if ($db->connect_error) die('Connection failed: ' . $db->connect_error); 

$res = $db->query("ALTER TABLE client_refund_requests ADD COLUMN document VARCHAR(255) NULL AFTER reason"); 
if (!$res) {
    echo 'Error ALTER TABLE: ' . $db->error . "\n";
} else {
    echo "Column 'document' added successfully.\n";
}
