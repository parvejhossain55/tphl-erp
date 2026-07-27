<?php

$mysqli = new mysqli("127.0.0.1", "root", "", "tphl_db");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

function showColumns($mysqli, $table) {
    echo "=== Columns for `$table` ===\n";
    $res = $mysqli->query("SHOW COLUMNS FROM `$table`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo " - {$row['Field']} ({$row['Type']})\n";
        }
    } else {
        echo "Table `$table` does not exist or error: " . $mysqli->error . "\n";
    }
    echo "\n";
}

showColumns($mysqli, "lead_bookings");
showColumns($mysqli, "flats");
showColumns($mysqli, "project_categories");
showColumns($mysqli, "leads");
showColumns($mysqli, "employees");
showColumns($mysqli, "users");
showColumns($mysqli, "approval_requests");
