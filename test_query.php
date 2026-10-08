<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) die("Connection failed: " . $db->connect_error);

$query = "
            SELECT 
                pr.id AS pr_id,
                pr.created_at,
                p.name AS project_name,
                u.full_name AS requested_by,
                pr.status,
                pr.assigned_executive_id,
                ex.full_name AS assigned_executive_name,
                pr.approved_by_id,
                pr.qc_approved_by_id,
                cat.name AS category_name,
                COALESCE(cat.total_lead_days, 0) AS total_lead_days,
                COUNT(pri.id) AS total_items,
                GROUP_CONCAT(m.name SEPARATOR ', ') AS item_names,
                MAX(pri.required_date) AS required_date,
                DATEDIFF(NOW(), pr.created_at) AS aging_days,
                (SELECT COUNT(id) FROM quotations WHERE request_id = pr.id) AS qtn_count,
                (SELECT COUNT(id) FROM purchase_orders WHERE request_id = pr.id AND status != 'Cancelled') AS po_count
            FROM purchase_requests pr
            JOIN projects p ON p.id = pr.project_id
            JOIN users u ON u.id = pr.created_by
            LEFT JOIN users ex ON ex.id = pr.assigned_executive_id
            JOIN purchase_request_items pri ON pri.request_id = pr.id
            JOIN con_materials m ON m.id = pri.material_id
            LEFT JOIN con_material_categories cat ON cat.id = m.category_id
            WHERE pr.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
              AND pr.status != 'Rejected'
            GROUP BY 
                pr.id, cat.id, pr.created_at, p.name, u.full_name, pr.status, 
                pr.assigned_executive_id, ex.full_name, pr.approved_by_id, 
                pr.qc_approved_by_id, cat.name, cat.total_lead_days
            ORDER BY p.name ASC, pr.created_at DESC
        ";
        
$stmt = $db->prepare($query);
if (!$stmt) {
    echo "Prepare failed: " . $db->error . "\n";
} else {
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res) {
        echo "Execute failed: " . $stmt->error . "\n";
    } else {
        echo "Success, rows: " . $res->num_rows . "\n";
    }
}
