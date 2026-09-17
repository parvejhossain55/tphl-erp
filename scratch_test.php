<?php
require 'app/config/config.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$userQuery = "
    SELECT u.id, u.full_name, u.email, dept.name as department_name, e.employee_id
    FROM users u
    LEFT JOIN employees e ON e.user_id = u.id
    LEFT JOIN designations des ON des.id = COALESCE(e.designation_id, u.designation_id)
    LEFT JOIN departments dept ON dept.id = COALESCE(e.department_id, des.department_id)
    WHERE (e.employee_status IS NULL OR e.employee_status NOT IN ('Inactive', 'Resigned', 'Terminated'))
";
$stmtU = $db->prepare($userQuery);
if (!$stmtU) {
    echo "Prepare failed: " . $db->error . "\n";
    exit;
}
$stmtU->execute();
$res = $stmtU->get_result();
if ($res) {
    echo "Rows: " . $res->num_rows . "\n";
} else {
    echo "Get result failed\n";
}
