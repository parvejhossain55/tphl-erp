<?php
$host = 'localhost';
$db = 'tphl_db';
$user = 'root';
$pass = '';

$mysqli = new mysqli($host, $user, $pass, $db);
echo "Columns in users table:\n";
$res = $mysqli->query("SHOW COLUMNS FROM users");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
}
$mysqli->close();
