<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
$sql = "ALTER TABLE employees ADD COLUMN gross_salary DECIMAL(10,2) AFTER basic_salary";
if ($mysqli->query($sql) === TRUE) {
    echo "Column gross_salary added successfully";
} else {
    echo "Error: " . $mysqli->error;
}
$mysqli->close();
?>
