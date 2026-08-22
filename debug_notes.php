<?php
// Quick debug: show exact raw response of the AJAX endpoint
// Visit: http://localhost:8080/TPHL-ERP/get-follow-up-history/28939

session_start();

// Fake session for testing (comment out in production)
if (empty($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 29, 'full_name' => 'Test', 'role_id' => 3];
}

$conn = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$leadId = intval($_GET['lead_id'] ?? 28939);

ob_clean();

$sql = "SELECT f.*, p.name as project_name, fl.flat_name, u.full_name as agent_name, l.full_name as lead_name
        FROM lead_follow_ups f
        LEFT JOIN projects p ON f.project_id = p.id
        LEFT JOIN flats fl ON f.flat_id = fl.id
        LEFT JOIN users u ON f.created_by = u.id
        LEFT JOIN leads l ON f.lead_id = l.id
        WHERE f.lead_id = ? AND f.deleted_at IS NULL
        ORDER BY f.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $leadId);
$stmt->execute();
$result = $stmt->get_result();
$followUps = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($followUps);
exit;
