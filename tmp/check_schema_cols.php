<?php
require_once __DIR__ . '/config/Database.php';
$db = (new Database())->connect();

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
