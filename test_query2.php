<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli('localhost', 'root', '', 'tphl_erp');
    
    // The exact query from ExpenseAdjustment.php
    $query = "
            SELECT ea.*, u.full_name as requester_name, r.name as requester_role,
                   COALESCE(cr.purpose_paid_to, CONCAT('Vendor Payment #', vpr.id, IF(vpr.invoice_number IS NOT NULL AND vpr.invoice_number != '', CONCAT(' (', vpr.invoice_number, ')'), '')), cbr.work_description, pr.notes, CONCAT('Other Cost: ', IFNULL(poc.purpose, poc.rcost_type)), CONCAT('Extra Asset: ', pea.item_name)) as original_purpose,
                   COALESCE(cr.amount, vpr.net_payable_amount, cbr.bill_amount, poc.payment_amount, poc.approved_amount, poc.request_amount, pea.paid_amount, pea.total_price, 0) as original_amount,
                   COALESCE(cr.description, 'Vendor request', cbr.work_description, pr.notes, poc.purpose, pea.justification) as original_desc,
                   p.name as project_name,
                   ap.full_name as approver_name,
                   ap_r.name as approver_role,
                   qc_u.full_name as qc_name,
                   qc_r.name as qc_role
            FROM expense_adjustments ea
            JOIN users u ON ea.user_id = u.id
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN cashout_requests cr ON ea.cashout_request_id = cr.id AND ea.parent_type = 'cashout'
            LEFT JOIN vendor_payment_requests vpr ON ea.cashout_request_id = vpr.id AND ea.parent_type = 'vendor'
            LEFT JOIN contractor_bill_requests cbr ON ea.cashout_request_id = cbr.id AND ea.parent_type = 'contractor'
            LEFT JOIN purchase_requests pr ON ea.cashout_request_id = pr.id AND ea.parent_type = 'purchase'
            LEFT JOIN project_other_costs poc ON ea.cashout_request_id = poc.id AND ea.parent_type = 'other_cost'
            LEFT JOIN project_extra_assets pea ON ea.cashout_request_id = pea.id AND ea.parent_type = 'extra_purchase'
            LEFT JOIN projects p ON ea.project_id = p.id
            LEFT JOIN users ap ON ea.approved_by = ap.id
            LEFT JOIN roles ap_r ON ap.role_id = ap_r.id
            LEFT JOIN users qc_u ON ea.qc_approved_by = qc_u.id
            LEFT JOIN roles qc_r ON qc_u.role_id = qc_r.id
            WHERE ea.id = 2
    ";
    
    $stmt = $db->prepare($query);
    if (!$stmt) {
        echo "Prepare failed: " . $db->error . "\n";
    } else {
        echo "Prepare success\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
