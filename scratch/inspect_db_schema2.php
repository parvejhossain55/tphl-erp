<?php

$mysqli = new mysqli("127.0.0.1", "root", "", "tphl_db");

function showCols($mysqli, $table) {
    echo "=== Columns for `$table` ===\n";
    $res = $mysqli->query("SHOW COLUMNS FROM `$table`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo " - {$row['Field']} ({$row['Type']})\n";
        }
    }
    echo "\n";
}

showCols($mysqli, "projects");
showCols($mysqli, "lead_payments");
