<?php
require_once __DIR__ . '/../config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$result = $db->query("DESCRIBE leads");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " " . $row['Type'] . "\n";
}
