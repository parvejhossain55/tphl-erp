<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("SELECT count(u.id) as c FROM employees e LEFT JOIN departments dept ON dept.id = e.department_id LEFT JOIN users u ON u.id = e.user_id WHERE dept.name = 'Sales'");
echo "Sales employees with user accounts: " . $res->fetch_assoc()['c'] . "\n";
