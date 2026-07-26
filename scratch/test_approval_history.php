<?php

$mysqli = new mysqli("127.0.0.1", "root", "", "tphl_db");

$sql = "SELECT 
            ar.id,
            ar.department,
            ar.request_type,
            ar.title,
            ar.source_table,
            ar.source_id,
            ar.request_amount,
            ar.approved_amount,
            ar.status,
            ar.created_at AS requested_at,
            ar.approved_at,
            ar.approval_note,
            u_req.full_name AS requester_name,
            IFNULL(e_req.employee_id, CONCAT('EMP-', u_req.id)) AS requester_code,
            d_req.name AS requester_dept,
            u_app.full_name AS approver_name
        FROM approval_requests ar
        LEFT JOIN users u_req ON ar.requested_by = u_req.id
        LEFT JOIN employees e_req ON u_req.id = e_req.user_id
        LEFT JOIN departments d_req ON e_req.department_id = d_req.id
        LEFT JOIN users u_app ON ar.approved_by = u_app.id
        ORDER BY ar.id DESC LIMIT 10";

$res = $mysqli->query($sql);
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
