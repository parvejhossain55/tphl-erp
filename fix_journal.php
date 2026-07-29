<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

// Get the user ID for Management
$res = $db->query("SELECT id FROM users WHERE user_name = 'management' OR full_name LIKE '%management%' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $mgtId = $row['id'];
    $db->query("UPDATE journals SET created_by = $mgtId WHERE id = 2");
    echo "Updated journal 2 to user ID $mgtId\n";
} else {
    echo "Management user not found\n";
}
