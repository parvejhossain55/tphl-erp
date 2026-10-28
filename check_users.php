<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/app/helpers/helper.php';
require_once __DIR__ . '/app/core/Database.php';
$config = require __DIR__ . '/app/config/config.php';
$database = new Database($config);
$db = $database->conn;

$res = $db->query("SELECT u.id, u.employee_id, u.full_name, u.user_name, u.role_id, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.employee_id = '1309' OR u.full_name LIKE '%Mamun%'");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Emp: {$row['employee_id']} | User: {$row['user_name']} | Full: {$row['full_name']} | Role ID: {$row['role_id']} | Role: {$row['role_name']}\n";
}

