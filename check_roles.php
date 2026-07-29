<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$query = "SELECT id, name FROM roles";
$result = $db->query($query);
while ($row = $result->fetch_assoc()) {
    echo $row['id'] . " - " . $row['name'] . "\n";
}
