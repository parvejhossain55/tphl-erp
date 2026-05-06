<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');

$operatorName = 'Alpha Builders';

// Check if contractor exists
$res = $conn->query("SELECT id FROM contractors WHERE name = '$operatorName'");
if ($res && $res->num_rows == 0) {
    // Insert contractor
    $conn->query("INSERT INTO contractors (name, phone, created_by, status) VALUES ('$operatorName', '01700000000', 1, 'active')");
    echo "Inserted Contractor: $operatorName\n";
} else {
    echo "Contractor already exists.\n";
}

$conn->close();
?>
