<?php
// Direct test of employee summary
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

// Test with first user ID
$userId = 16; // Ashfaque Bhuiyan Omi (from client experience test)
$dateFrom = '2026-09-01';
$dateTo = '2026-09-17';

$startStr = $dateFrom . ' 00:00:00';
$endStr   = $dateTo . ' 23:59:59';

$sqlSummary = "
    SELECT 
        u.full_name, u.email,
        dept.name AS department_name,
        des.name  AS designation_name,
        COUNT(ual.id) AS total_sessions,
        SUM(TIMESTAMPDIFF(MINUTE, ual.login_time, ual.last_active_time)) AS total_minutes
    FROM user_activity_logs ual
    JOIN users u ON u.id = ual.user_id
    LEFT JOIN designations des  ON des.id  = u.designation_id
    LEFT JOIN departments  dept ON dept.id = des.department_id
    WHERE ual.user_id = ?
    AND ual.login_time BETWEEN ? AND ?
    GROUP BY u.full_name, u.email, dept.name, des.name
";

$stmt = $db->prepare($sqlSummary);
$stmt->bind_param('iss', $userId, $startStr, $endStr);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo "Summary for user $userId:\n";
print_r($summary);

$qLeads = "SELECT COUNT(*) as cnt FROM leads WHERE created_by = ? AND created_at BETWEEN ? AND ?";
$stmtL = $db->prepare($qLeads);
if($stmtL) {
    $stmtL->bind_param('iss', $userId, $startStr, $endStr);
    $stmtL->execute();
    $leads = $stmtL->get_result()->fetch_assoc()['cnt'];
    echo "Leads: $leads\n";
} else {
    echo "Leads query error: " . $db->error . "\n";
}
