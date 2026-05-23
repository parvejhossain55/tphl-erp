<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$result = $mysqli->query('SELECT status FROM purchase_requests WHERE id = 1');
if ($result) {
    $row = $result->fetch_assoc();
    echo 'Status: ' . ($row['status'] ?? 'Not found');
} else {
    echo "No result";
}
