<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$filterDeptId = 7;

$baseUsersSql = "
    SELECT 
        u.id, u.full_name, u.email, e.employee_id,
        dept.id AS department_id, dept.name AS department_name, 
        des.name AS designation_name,
        MAX(ual.login_time) as last_login_ever
    FROM users u
    LEFT JOIN employees e ON e.user_id = u.id
    LEFT JOIN designations des ON des.id = COALESCE(e.designation_id, u.designation_id)
    LEFT JOIN departments dept ON dept.id = COALESCE(e.department_id, des.department_id)
    LEFT JOIN user_activity_logs ual ON ual.user_id = u.id
    WHERE (e.employee_status IS NULL OR e.employee_status NOT IN ('Inactive', 'Resigned', 'Terminated'))
";
$baseUsersSql .= " AND dept.id = ?";
$baseUsersSql .= " GROUP BY u.id, u.full_name, u.email, e.employee_id, dept.id, dept.name, des.name";

$stmtUsers = $db->prepare($baseUsersSql);
$stmtUsers->bind_param('i', $filterDeptId);
$stmtUsers->execute();
$targetUsers = $stmtUsers->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtUsers->close();

echo "Count for baseUsersSql Dept 7: " . count($targetUsers) . "\n";
