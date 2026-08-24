<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("SELECT count(*) as c FROM employees e LEFT JOIN designations des ON des.id = e.designation_id LEFT JOIN departments dept ON dept.id = des.department_id WHERE dept.name = 'Sales'");
echo "Employees in Sales: " . $res->fetch_assoc()['c'] . "\n";

$res = $db->query("SELECT count(*) as c FROM users u LEFT JOIN designations des ON des.id = u.designation_id LEFT JOIN departments dept ON dept.id = des.department_id WHERE dept.name = 'Sales'");
echo "Users in Sales: " . $res->fetch_assoc()['c'] . "\n";

$res = $db->query("SELECT count(*) as c FROM users u LEFT JOIN employees e ON e.user_id = u.id LEFT JOIN designations des ON des.id = e.designation_id LEFT JOIN departments dept ON dept.id = des.department_id WHERE dept.name = 'Sales'");
echo "Users with Employee in Sales: " . $res->fetch_assoc()['c'] . "\n";
