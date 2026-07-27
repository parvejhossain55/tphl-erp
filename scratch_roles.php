<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$roles = $db->query("SELECT id, name FROM roles")->fetch_all(MYSQLI_ASSOC);
echo json_encode($roles, JSON_PRETTY_PRINT);
