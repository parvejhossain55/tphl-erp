<?php
require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "Connected successfully to database: " . $config['db_name'] . "\n\n";

// 1. Check Abu Naym
$sql = "SELECT id, full_name, team_id, role_id, employee_id FROM users WHERE full_name LIKE '%Abu Naym%' OR full_name LIKE '%Naym%'";
$res = $db->query($sql);
echo "Users matching Abu Naym:\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

$sql2 = "SELECT id, full_name, team_id, user_id, employee_id FROM employees WHERE full_name LIKE '%Abu Naym%' OR full_name LIKE '%Naym%'";
$res2 = $db->query($sql2);
echo "\nEmployees matching Abu Naym:\n";
while ($row = $res2->fetch_assoc()) {
    print_r($row);
}

// 2. Check Rakib Hasan Rubai (user_id = 68)
$u68 = $db->query("SELECT id, full_name, team_id, role_id FROM users WHERE id = 68 OR full_name LIKE '%Rubai%'")->fetch_assoc();
echo "\nRakib Hasan Rubai (Manager) in database:\n";
print_r($u68);

// 3. Check all users in team 7
$res7 = $db->query("SELECT id, full_name, team_id, role_id FROM users WHERE team_id = 7");
echo "\nAll users in Team 7:\n";
while ($row = $res7->fetch_assoc()) {
    print_r($row);
}
