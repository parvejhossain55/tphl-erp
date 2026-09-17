<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$userQuery = "
    SELECT u.id, u.full_name, u.email, dept.name as department_name, e.employee_id
    FROM users u
    LEFT JOIN employees e ON e.user_id = u.id
    LEFT JOIN designations des ON des.id = COALESCE(e.designation_id, u.designation_id)
    LEFT JOIN departments dept ON dept.id = COALESCE(e.department_id, des.department_id)
    WHERE (e.employee_status IS NULL OR e.employee_status NOT IN ('Inactive', 'Resigned', 'Terminated'))
    ORDER BY u.full_name ASC
";
$userResult = $db->query($userQuery);
$allUsers = $userResult ? $userResult->fetch_all(MYSQLI_ASSOC) : [];

echo "<select name='user_id' class='form-select form-select-sm select2'>\n";
echo "    <option value=''>Search employee...</option>\n";
foreach ($allUsers as $u) {
    echo "    <option value='{$u['id']}'>". htmlspecialchars($u['full_name'] . (!empty($u['employee_id']) ? ' (' . $u['employee_id'] . ')' : '')) ."</option>\n";
}
echo "</select>\n";
