<?php
$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$query = "SELECT j.*, u.name as created_by_name FROM journals j LEFT JOIN users u ON j.created_by = u.id WHERE j.id = ?";
$stmt = $db->prepare($query);
if (!$stmt) {
    echo "Prepare failed: (" . $db->errno . ") " . $db->error . "\n";
} else {
    echo "Prepare succeeded.\n";
}
