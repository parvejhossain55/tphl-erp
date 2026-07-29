<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$stmt = $db->prepare("UPDATE journals SET status = 'APPROVED' WHERE id = 2");
if (!$stmt->execute()) {
    echo "Error: " . $stmt->error . "\n";
} else {
    echo "Success! Affected rows: " . $stmt->affected_rows . "\n";
}
