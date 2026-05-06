<?php
<<<<<<< HEAD
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$rPendingReturns = $db->query("
            SELECT mr.*, p.name AS project_name, v.name AS vendor_name, u.full_name AS created_by_name
            FROM material_returns mr
            LEFT JOIN projects p ON p.id = mr.project_id
            LEFT JOIN vendors v ON v.id = mr.vendor_id
            LEFT JOIN users u ON u.id = mr.created_by
            WHERE mr.status = 'pending'
            ORDER BY mr.id DESC
            LIMIT 10
        ");
if ($rPendingReturns) {
    echo "Success. Num rows: " . $rPendingReturns->num_rows;
} else {
    echo "Error: " . $db->error;
=======
require 'config/database.php';

$sql = "
SELECT * FROM (
    SELECT 
        cr.id,
        'general' as cashout_type,
        cr.amount,
        cr.due_amount,
        cr.status,
        cr.created_at,
        cr.requested_by,
        cr.project_id,
        cr.office,
        cr.purpose_paid_to,
        cr.payment_method,
        cr.transaction_ref,
        cr.description,
        cr.attachment_path,
        cr.bank_id,
        cr.approved_amount,
        cr.approval_note,
        cr.approved_by,
        cr.approved_at,
        cr.rejected_by,
        cr.rejected_at,
        cr.rejection_reason,
        u.full_name AS requester_name,
        (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM projects WHERE FIND_IN_SET(projects.id, cr.project_id)) AS project_name,
        ar.status AS approval_status,
        ar.approved_amount AS approval_approved_amount,
        ar.approved_at AS approval_approved_at,
        ar.approved_by AS approval_user_id,
        ar.id AS approval_id,
        approver.full_name AS approved_by_name,
        rejector.full_name AS rejected_by_name
    FROM cashout_requests cr
    LEFT JOIN users u ON cr.requested_by = u.id
    LEFT JOIN approval_requests ar ON ar.source_table = 'cashout_requests' AND ar.source_id = cr.id
    LEFT JOIN users approver ON ar.approved_by = approver.id
    LEFT JOIN users rejector ON cr.rejected_by = rejector.id

    UNION ALL

    SELECT 
        vpr.id,
        'vendor' as cashout_type,
        vpr.invoice_amount as amount,
        vpr.due_amount,
        vpr.status,
        vpr.created_at,
        vpr.created_by as requested_by,
        CAST(vpr.project_id AS CHAR) as project_id,
        NULL as office,
        v.name as purpose_paid_to,
        'Bank/Cash' as payment_method,
        vpr.invoice_number as transaction_ref,
        vpr.payment_type as description,
        vpr.payment_attachment as attachment_path,
        NULL as bank_id,
        vpr.approved_amount,
        NULL as approval_note,
        vpr.approved_by,
        NULL as approved_at,
        NULL as rejected_by,
        NULL as rejected_at,
        NULL as rejection_reason,
        u.full_name AS requester_name,
        (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM projects WHERE FIND_IN_SET(projects.id, vpr.project_id)) AS project_name,
        NULL AS approval_status,
        NULL AS approval_approved_amount,
        NULL AS approval_approved_at,
        NULL AS approval_user_id,
        NULL AS approval_id,
        approver.full_name AS approved_by_name,
        NULL AS rejected_by_name
    FROM vendor_payment_requests vpr
    LEFT JOIN vendors v ON vpr.vendor_id = v.id
    LEFT JOIN users u ON vpr.created_by = u.id
    LEFT JOIN users approver ON vpr.approved_by = approver.id

    UNION ALL

    SELECT 
        cbr.id,
        'contractor bill' as cashout_type,
        cbr.bill_amount as amount,
        cbr.due_amount,
        cbr.status,
        cbr.created_at,
        cbr.created_by as requested_by,
        CAST(cbr.project_id AS CHAR) as project_id,
        NULL as office,
        cbr.work_description as purpose_paid_to,
        'Bank/Cash' as payment_method,
        NULL as transaction_ref,
        cbr.work_description as description,
        cbr.attachment_path,
        NULL as bank_id,
        cbr.approved_amount,
        cbr.verification_note as approval_note,
        cbr.approved_by,
        NULL as approved_at,
        NULL as rejected_by,
        NULL as rejected_at,
        NULL as rejection_reason,
        u.full_name AS requester_name,
        (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM projects WHERE FIND_IN_SET(projects.id, cbr.project_id)) AS project_name,
        NULL AS approval_status,
        NULL AS approval_approved_amount,
        NULL AS approval_approved_at,
        NULL AS approval_user_id,
        NULL AS approval_id,
        approver.full_name AS approved_by_name,
        NULL AS rejected_by_name
    FROM contractor_bill_requests cbr
    LEFT JOIN users u ON cbr.created_by = u.id
    LEFT JOIN users approver ON cbr.approved_by = approver.id
) AS cr
WHERE 1=1 LIMIT 5
";

$res = $conn->query($sql);
if (!$res) {
    echo "Error: " . $conn->error;
} else {
    while($row = $res->fetch_assoc()){
        print_r($row);
    }
>>>>>>> 868cdaf69563db9f6be9c6bc354a8310e352fe58
}
