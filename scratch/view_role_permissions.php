<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$q = "SELECT r.id AS role_id, r.name AS role_name, p.id AS permission_id, p.name AS permission_name 
      FROM roles r 
      JOIN role_permissions rp ON rp.role_id = r.id 
      JOIN permissions p ON p.id = rp.permission_id 
      WHERE r.id IN (17, 31)
      ORDER BY r.id, p.name";

$rows = $db->query($q)->fetch_all(MYSQLI_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
