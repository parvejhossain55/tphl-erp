<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$sql = "SELECT f.*, p.name as project_name, fl.flat_name, u.full_name as agent_name, l.full_name as lead_name
        FROM lead_follow_ups f
        LEFT JOIN projects p ON f.project_id = p.id
        LEFT JOIN flats fl ON f.flat_id = fl.id
        LEFT JOIN users u ON f.created_by = u.id
        LEFT JOIN leads l ON f.lead_id = l.id
        WHERE f.lead_id = 28939 AND f.deleted_at IS NULL
        ORDER BY f.id DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) { die($conn->error); }
$stmt->execute();
$result = $stmt->get_result();
$followUps = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($followUps);
