<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$stmt = $db->query("
    SELECT lb.id, lb.agreement_value, lb.discount_amount, lb.past_approval_attachment,
           l.full_name AS client_name, l.phone AS client_phone,
           p.name AS project_name,
           f.flat_name, f.floor_no,
           u.full_name AS sales_agent_name, e.employee_id AS sales_agent_code
    FROM lead_bookings lb
    LEFT JOIN leads l ON lb.lead_id = l.id
    LEFT JOIN projects p ON lb.project_id = p.id
    LEFT JOIN flats f ON lb.flat_id = f.id
    LEFT JOIN users u ON lb.sales_by_id = u.id
    LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
    WHERE lb.id = 3410
    LIMIT 1
");
print_r($stmt->fetch_assoc());
