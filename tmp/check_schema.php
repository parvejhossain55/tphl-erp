<?php
$mysqli = new mysqli("localhost", "root", "", "tphl_db");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

function dumpTable($mysqli, $table) {
    echo "--- Table: $table ---\n";
    $result = $mysqli->query("DESCRIBE $table");
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . "\n";
    }
}

dumpTable($mysqli, 'users');
dumpTable($mysqli, 'employees');
$mysqli->close();
