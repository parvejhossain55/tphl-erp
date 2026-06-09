<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== Columns of contractor_bill_requests ===\n";
$res = $db->query("DESCRIBE contractor_bill_requests");
if ($res) {
    print_r($res->fetch_all(MYSQLI_ASSOC));
}

echo "\n=== Foreign Keys on contractor_bill_requests ===\n";
$res = $db->query("
    SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE REFERENCED_TABLE_SCHEMA = 'tphl_db' AND TABLE_NAME = 'contractor_bill_requests'
");
if ($res) {
    print_r($res->fetch_all(MYSQLI_ASSOC));
}
