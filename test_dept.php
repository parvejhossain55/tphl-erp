<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$filterDeptId = 5; // Replace with a valid department ID

$userQuery = "
    SELECT u.id, u.full_name, u.email, dept.name as department_name, e.employee_id
    FROM users u
    LEFT JOIN employees e ON e.user_id = u.id
    LEFT JOIN designations des ON des.id = COALESCE(e.designation_id, u.designation_id)
    LEFT JOIN departments dept ON dept.id = COALESCE(e.department_id, des.department_id)
    WHERE (e.employee_status IS NULL OR e.employee_status NOT IN ('Inactive', 'Resigned', 'Terminated'))
";
$userQuery .= " AND dept.id = ?";
$userQuery .= " ORDER BY u.full_name ASC";
$stmtU = $db->prepare($userQuery);
$stmtU->bind_param('i', $filterDeptId);
$stmtU->execute();
$userResult = $stmtU->get_result();
$allUsers = $userResult ? $userResult->fetch_all(MYSQLI_ASSOC) : [];
$stmtU->close();

echo "Count for Dept 5: " . count($allUsers) . "\n";
if (count($allUsers) > 0) {
    echo "First user dept: " . $allUsers[0]['department_name'] . "\n";
}
