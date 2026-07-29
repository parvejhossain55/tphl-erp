<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$queries = [
    "ALTER TABLE employees ADD COLUMN reporting_user_id INT NULL AFTER user_id",
    "ALTER TABLE employees ADD COLUMN is_dept_head TINYINT(1) DEFAULT 0 AFTER reporting_user_id"
];

foreach ($queries as $query) {
    if ($db->query($query) === TRUE) {
        echo "Successfully executed: $query\n";
    } else {
        echo "Error executing $query: " . $db->error . "\n";
    }
}
