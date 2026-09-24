<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

// Check sales users with employee_id
$sql = "
    SELECT 
        u.id, 
        u.full_name, 
        u.email,
        u.role_id,
        r1.name as role_name, 
        u.secondary_role_id,
        r2.name as sec_role_name, 
        COALESCE(e1.employee_id, e2.employee_id) as employee_id
    FROM users u 
    LEFT JOIN roles r1 ON u.role_id = r1.id 
    LEFT JOIN roles r2 ON u.secondary_role_id = r2.id 
    LEFT JOIN employees e1 ON e1.user_id = u.id
    LEFT JOIN employees e2 ON u.employee_id = e2.id
    WHERE (
        u.role_id IN (5, 8, 9)
        OR u.secondary_role_id IN (5, 8, 9)
        OR r1.name LIKE '%sales%'
        OR r1.name LIKE '%sale%'
        OR r2.name LIKE '%sales%'
        OR r2.name LIKE '%sale%'
        OR u.role_id IN (1, 3)
    )
    ORDER BY u.full_name ASC
";

$res = $db->query($sql);
echo "Total sales users: " . $res->num_rows . "\n";
while ($row = $res->fetch_assoc()) {
    $emp = !empty($row['employee_id']) ? " [EMP: {$row['employee_id']}]" : "";
    $role = $row['role_name'] ?: ($row['sec_role_name'] ?: 'Sales');
    echo "{$row['id']}: {$row['full_name']}{$emp} ({$role})\n";
}
