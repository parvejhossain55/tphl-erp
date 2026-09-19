<?php
$host = 'localhost';
$db = 'tphl_erp';
$user = 'root';
$pass = '';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "Roles:\n";
$res = $mysqli->query("SELECT id, name FROM roles WHERE name LIKE '%sales%'");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo $row['id'] . " - " . $row['name'] . "\n";
    }
} else {
    echo "Query failed: " . $mysqli->error;
}

echo "\nColumns in leads table:\n";
$res = $mysqli->query("SHOW COLUMNS FROM leads");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
}
$mysqli->close();
