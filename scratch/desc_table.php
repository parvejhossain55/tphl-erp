<?php
$config = require __DIR__ . '/../app/config/config.php';
$mysqli = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$sql = "SELECT po.*, pr.project_id, pr.notes AS pr_notes, p.name AS project_name,
               u.full_name AS created_by_name,
               apv.full_name AS pm_approver_name,
               apv_r.name    AS pm_approver_role,
               qc_u.full_name AS qc_approver_name
        FROM purchase_orders po
        JOIN purchase_requests pr ON pr.id = po.request_id
        LEFT JOIN projects p ON p.id = pr.project_id
        LEFT JOIN users u ON u.id = po.created_by
        LEFT JOIN users apv ON apv.id = po.approved_by_pm
        LEFT JOIN roles apv_r ON apv_r.id = apv.role_id
        LEFT JOIN users qc_u ON qc_u.id = po.qc_approved_by_id
        WHERE po.id = ?";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo "Prepare failed: " . $mysqli->error . "\n";
} else {
    echo "Prepare succeeded!\n";
}
