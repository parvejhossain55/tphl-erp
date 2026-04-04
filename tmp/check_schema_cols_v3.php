<?php
require_once 'c:/xampp/htdocs/TPHL-ERP/app/core/Database.php';

$db = (new Database())->connect();

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "--- Users Table Columns ---\n";
$result = $db->query("DESCRIBE users");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n--- Employees Table Columns ---\n";
$result = $db->query("DESCRIBE employees");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n--- Role 26 exists? ---\n";
$result = $db->query("SELECT id, name FROM roles WHERE id = 26");
if ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", Name: " . $row['name'] . "\n";
} else {
    echo "Role 26 NOT found.\n";
}

echo "\n--- Employee 16 Data ---\n";
$result = $db->query("SELECT id, user_id, full_name, team_id, email FROM employees WHERE id = 16");
print_r($result->fetch_assoc());

echo "\n--- User with employee_id = 16 Data ---\n";
$result = $db->query("SELECT id, employee_id, role_id, team_id, email FROM users WHERE employee_id = 16");
print_r($result->fetch_assoc());
