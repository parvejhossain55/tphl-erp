<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$userQuery = "
            SELECT u.id, u.full_name, u.email, dept.name as department_name
            FROM users u
            LEFT JOIN employees e ON e.user_id = u.id
            LEFT JOIN designations des ON des.id = COALESCE(e.designation_id, u.designation_id)
            LEFT JOIN departments dept ON dept.id = COALESCE(e.department_id, des.department_id)
            WHERE e.employee_status IS NULL OR e.employee_status NOT IN ('Inactive', 'Resigned', 'Terminated')
            ORDER BY u.full_name ASC
";
$res = $db->query($userQuery);
if (!$res) echo "Error: " . $db->error . "\n";
else echo "Success, count: " . $res->num_rows . "\n";
