<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "ROLES:\n";
$res = $conn->query("SELECT id, name FROM roles");
while ($row = $res->fetch_assoc()) {
    echo "  " . $row['id'] . " => " . $row['name'] . "\n";
}

echo "\nDESIGNATIONS:\n";
$res = $conn->query("SELECT id, name FROM designations LIMIT 50");
while ($row = $res->fetch_assoc()) {
    echo "  " . $row['id'] . " => " . $row['name'] . "\n";
}
$conn->close();
