<?php
$root = dirname(__DIR__);
require_once $root . "/app/config/config.php";
$config = require $root . "/app/config/config.php";

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "--- con_wastage table columns ---\n";
$res = $db->query("DESCRIBE con_wastage");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']} - Null: {$row['Null']} - Key: {$row['Key']} - Default: {$row['Default']}\n";
    }
} else {
    echo "Error describing con_wastage: " . $db->error . "\n";
}

echo "\n--- con_wastage_items table columns ---\n";
$res2 = $db->query("DESCRIBE con_wastage_items");
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']} - Null: {$row['Null']} - Key: {$row['Key']} - Default: {$row['Default']}\n";
    }
} else {
    echo "Error describing con_wastage_items: " . $db->error . "\n";
}
