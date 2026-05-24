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

// Check if columns exist, if not, add them
$columns_to_add = [
    "bayana_status" => "ENUM('pending', 'cancel', 'complete') DEFAULT 'pending'",
    "bayana_complete_date" => "DATE DEFAULT NULL",
    "registration_status" => "ENUM('pending', 'cancel', 'complete') DEFAULT 'pending'",
    "registration_complete_date" => "DATE DEFAULT NULL"
];

foreach ($columns_to_add as $col => $definition) {
    $result = $conn->query("SHOW COLUMNS FROM `land_acquisitions` LIKE '$col'");
    if ($result && $result->num_rows > 0) {
        echo "Column '$col' already exists.\n";
    } else {
        $alter_sql = "ALTER TABLE `land_acquisitions` ADD COLUMN `$col` $definition";
        if ($conn->query($alter_sql) === TRUE) {
            echo "Successfully added column '$col'.\n";
        } else {
            echo "Error adding column '$col': " . $conn->error . "\n";
        }
    }
}

$conn->close();
echo "Script finished.\n";
