<?php
parse_str('department_id=4&date_from=2026-09-01&date_to=2026-09-17', $_GET);

$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$filterDeptId = 4;
$filterUserId = null;

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

echo "<select name=\"user_id\" class=\"form-select form-select-sm select2\" style=\"width: 200px;\" onchange=\"this.form.submit()\" data-placeholder=\"Select Employee... (" . count($allUsers) . ")\">\n";
echo "    <option value=\"\">Select Employee... (" . count($allUsers) . ")</option>\n";
foreach ($allUsers as $u) {
    echo "    <option value=\"" . $u['id'] . "\" " . (($filterUserId == $u['id']) ? 'selected' : '') . ">" . htmlspecialchars($u['full_name'] . (!empty($u['employee_id']) ? ' (' . $u['employee_id'] . ')' : '')) . "</option>\n";
}
echo "</select>";
