<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$result = $db->query("SELECT id, user_name, full_name, role_id FROM users WHERE user_name = 'accounts' OR full_name = 'accounts'");
while($row = $result->fetch_assoc()) {
    print_r($row);
}
