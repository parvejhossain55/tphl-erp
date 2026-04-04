<?php
require_once __DIR__ . '/config/Database.php';
$db = (new Database())->connect();
$result = $db->query("SELECT id, name FROM roles WHERE id = 5");
if ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", Name: " . $row['name'] . "\n";
} else {
    echo "Role 5 not found.\n";
}
