<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("DESCRIBE journals");
echo "--- journals ---\n";
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

$res = $db->query("DESCRIBE journal_details");
echo "\n--- journal_details ---\n";
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

$res = $db->query("SHOW TABLES LIKE '%project%'");
echo "\n--- project tables ---\n";
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
