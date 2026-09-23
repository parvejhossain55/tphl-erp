<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$r = $db->query("SELECT id, name FROM roles WHERE id IN (1, 3, 17, 20, 23, 31) OR name LIKE '%purchase%'");
while ($row = $r->fetch_assoc()) {
    echo $row['id'] . ': ' . $row['name'] . PHP_EOL;
}
