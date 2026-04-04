<?php
require_once __DIR__ . '/config/Database.php';
$db = (new Database())->connect();
$result = $db->query("DESCRIBE employees");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
echo "\n--- Data for ID 16 ---\n";
$result = $db->query("SELECT * FROM employees WHERE id = 16");
print_r($result->fetch_assoc());

echo "\n--- Data for User 24 ---\n";
$result = $db->query("SELECT * FROM users WHERE id = 24");
print_r($result->fetch_assoc());
