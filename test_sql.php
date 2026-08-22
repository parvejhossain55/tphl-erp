<?php
require 'app/config/config.php';
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$sql = "SELECT u.id, u.full_name, u.email, dept.id AS department_id, dept.name AS department_name, des.name AS designation_name, MAX(ual.login_time) as last_login_ever FROM users u LEFT JOIN designations des ON des.id = u.designation_id LEFT JOIN departments dept ON dept.id = des.department_id LEFT JOIN user_activity_logs ual ON ual.user_id = u.id WHERE (u.employee_status IS NULL OR u.employee_status NOT IN ('Inactive', 'Resigned', 'Terminated')) GROUP BY u.id, u.full_name, u.email, dept.id, dept.name, des.name";
$stmt = $db->prepare($sql);
if (!$stmt) echo 'Error 1: ' . $db->error . "\n";
else echo "OK 1\n";

$logsSql = "
SELECT 
    ual.user_id,
    DATE(ual.login_time) as log_date,
    ual.login_time,
    ual.last_active_time,
    TIMESTAMPDIFF(MINUTE, ual.login_time, ual.last_active_time) as active_minutes
FROM user_activity_logs ual
WHERE DATE(ual.login_time) >= ? AND DATE(ual.login_time) <= ?
AND ual.user_id IN (?,?)
";
$stmt2 = $db->prepare($logsSql);
if (!$stmt2) echo 'Error 2: ' . $db->error . "\n";
else echo "OK 2\n";
