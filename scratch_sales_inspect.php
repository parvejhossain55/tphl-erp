<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$sql = "
    SELECT 
        lb.id as booking_id,
        lb.lead_id,
        lb.booking_date,
        lb.sales_by_id,
        l.assigned_id,
        u.full_name as agent_name,
        COALESCE(e1.employee_id, e2.employee_id) as agent_employee_id
    FROM lead_bookings lb
    INNER JOIN leads l ON lb.lead_id = l.id
    LEFT JOIN users u ON COALESCE(lb.sales_by_id, l.assigned_id) = u.id
    LEFT JOIN employees e1 ON e1.user_id = u.id
    LEFT JOIN employees e2 ON u.employee_id = e2.id
    ORDER BY lb.id DESC
    LIMIT 5
";

$res = $db->query($sql);
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
