<?php
$config = require __DIR__ . '/../app/config/config.php';
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$res1 = $conn->query("SHOW CREATE TABLE fund_requisitions");
if ($res1) {
    echo "--- FUND REQUISITIONS TABLE ---\n";
    echo $res1->fetch_assoc()['Create Table'] . ";\n\n";
}

$res2 = $conn->query("SHOW CREATE TABLE fund_requisition_expenses");
if ($res2) {
    echo "--- FUND REQUISITION EXPENSES TABLE ---\n";
    echo $res2->fetch_assoc()['Create Table'] . ";\n\n";
}

$conn->close();
