<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require dirname(__DIR__) . "/app/config/config.php";

$db_host = $config['db_host'];
$db_user = $config['db_user'];
$db_pass = $config['db_pass'];
$db_name = $config['db_name'];

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully to {$db_name}.\n";

$sql = "DELETE FROM `land_bayana_payments` WHERE `remarks` LIKE 'Auto-generated%'";
if ($conn->query($sql) === TRUE) {
    $affected = $conn->affected_rows;
    echo "Successfully deleted $affected auto-generated record(s).\n";
} else {
    echo "Error executing query: " . $conn->error . "\n";
}

$conn->close();
echo "Clean-up complete.\n";
