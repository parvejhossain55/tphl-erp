<?php

$mysqli = new mysqli("127.0.0.1", "root", "", "tphl_db");

$sql = "SELECT 
            lb.id AS booking_id,
            lb.created_at AS booking_date,
            lb.sales_by_id,
            u.id AS user_id,
            u.full_name AS user_name,
            e.employee_id,
            d.department AS department,
            p.name AS project_name,
            f.flat_name,
            IFNULL(f.price_value, 0.00) AS sale_amount,
            IFNULL(f.land_share_value, 0.00) AS land_share_value,
            c.id AS claim_id,
            IFNULL(c.claim_status, 'not_claimed') AS claim_status
        FROM lead_bookings lb
        LEFT JOIN leads l ON lb.lead_id = l.id
        LEFT JOIN users u ON (lb.sales_by_id = u.id OR l.assigned_id = u.id)
        LEFT JOIN employees e ON u.id = e.user_id
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN projects p ON lb.project_id = p.id
        LEFT JOIN flats f ON lb.flat_id = f.id
        LEFT JOIN project_incentive_claims c ON (c.booking_id = lb.id AND c.is_external = 0)
        WHERE lb.deleted_at IS NULL
        ORDER BY lb.id DESC LIMIT 5";

$res = $mysqli->query($sql);
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
