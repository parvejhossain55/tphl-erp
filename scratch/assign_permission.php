<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$q = "INSERT INTO role_permissions (role_id, permission_id) VALUES (31, 35)";
echo "Running: $q\n";
if ($db->query($q)) {
    echo "Success\n";
} else {
    echo "Error: " . $db->error . "\n";
}
