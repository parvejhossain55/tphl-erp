<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require dirname(__DIR__) . "/app/config/config.php";
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$res = $conn->query("SHOW COLUMNS FROM fund_requisitions LIKE 'accounts_note'");
if ($res->num_rows == 0) {
    $alterSql = "ALTER TABLE fund_requisitions ADD COLUMN accounts_note TEXT NULL AFTER approval_note";
    if ($conn->query($alterSql)) {
        echo "Added 'accounts_note' column to 'fund_requisitions' table.\n";
    } else {
        echo "Error altering table: " . $conn->error . "\n";
    }
} else {
    echo "'accounts_note' column already exists in 'fund_requisitions'.\n";
}

$conn->close();
?>
