<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

function showTableSchema($db, $tableName) {
    echo "=== Table: $tableName ===\n";
    $res = $db->query("DESCRIBE $tableName");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            printf("  %-25s %-20s %-10s %-10s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key']);
        }
    } else {
        echo "Table not found or error.\n";
    }
    echo "\n";
}

showTableSchema($db, 'approval_requests');
?>
