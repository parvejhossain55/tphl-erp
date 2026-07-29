<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("DESCRIBE projects");
echo "--- projects ---\n";
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

$res = $db->query("SELECT id, name FROM projects LIMIT 10");
echo "\n--- projects data ---\n";
while($row = $res->fetch_assoc()) {
    echo $row['id'] . " - " . $row['name'] . "\n";
}
