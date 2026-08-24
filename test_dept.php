<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("SELECT e.id FROM employees e LEFT JOIN departments dept ON dept.id = e.department_id WHERE dept.name = 'Sales'");
echo "Employees with e.department_id: " . ($res ? $res->num_rows : 'Error: ' . $db->error) . "\n";

$res = $db->query("SELECT e.id FROM employees e LEFT JOIN designations des ON des.id = e.designation_id LEFT JOIN departments dept ON dept.id = des.department_id WHERE dept.name = 'Sales'");
echo "Employees via designation_id: " . ($res ? $res->num_rows : 'Error: ' . $db->error) . "\n";
