<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SHOW CREATE TABLE sales_targets");
if ($res) {
    $row = $res->fetch_assoc();
    echo $row['Create Table'] . "\n\n";
} else {
    echo "Error sales_targets: " . $db->error . "\n\n";
}

$res2 = $db->query("SHOW TABLES LIKE '%incentive%'");
if ($res2) {
    while($row = $res2->fetch_row()) {
        echo $row[0] . "\n";
    }
} else {
    echo "Error checking tables: " . $db->error;
}
$db->close();
