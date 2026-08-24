<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$result = $mysqli->query("SELECT id, bank_name, branch_name FROM banks WHERE status='active' ORDER BY bank_name");
$count = 0;
while ($row = $result->fetch_assoc()) {
    echo $row['id'] . ' ' . $row['bank_name'] . ' - ' . $row['branch_name'] . "\n";
    $count++;
}
echo "Total: $count\n";
