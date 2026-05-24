<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== Table: target_fixed ===\n";
$res = $db->query("DESCRIBE target_fixed");
while ($row = $res->fetch_assoc()) {
    printf("  %-25s %-20s %-10s %-10s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key']);
}
