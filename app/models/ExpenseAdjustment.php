<?php

class ExpenseAdjustment
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll($userId = null, $actionableRole = null, $customWhere = null)
    {
        $query = "
            SELECT ea.*, u.full_name as requester_name, r.name as requester_role,
                   COALESCE(cr.purpose_paid_to, CONCAT('Vendor Payment #', vpr.id, IF(vpr.invoice_number IS NOT NULL AND vpr.invoice_number != '', CONCAT(' (', vpr.invoice_number, ')'), '')), cbr.work_description, pr.notes, CONCAT('Other Cost: ', IFNULL(poc.purpose, poc.rcost_type)), CONCAT('Extra Asset: ', pea.item_name)) as original_purpose,
                   COALESCE(cr.amount, vpr.net_payable_amount, cbr.bill_amount, poc.payment_amount, poc.approved_amount, poc.request_amount, pea.paid_amount, pea.total_price, 0) as original_amount,
                   p.name as project_name, COALESCE(ea.office, cr.office, pea.asset_location) as office
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
        ";

        $where = [];
        if ($userId) {
            $where[] = "ea.user_id = " . (int)$userId;
        } elseif ($actionableRole === 'accounts') {
            $where[] = "((ea.adjustment_type = 'return' AND ea.status IN ('pending', 'partially_paid')) OR (ea.adjustment_type = 'extra' AND ea.status IN ('approved', 'partially_paid')))";
        } elseif ($actionableRole === 'qc') {
            $where[] = "(ea.adjustment_type = 'extra' AND ea.status = 'pending')";
        } elseif ($actionableRole === 'management') {
            $where[] = "(ea.adjustment_type = 'extra' AND ea.status = 'qc_approved')";
        }

        if ($customWhere) {
            $where[] = $customWhere;
        }

        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        $query .= " ORDER BY ea.created_at DESC";

        $result = $this->db->query($query);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function getTabCounts($userId, $actionableRole = null)
    {
        $userId = (int)$userId;
        $myRes = $this->db->query("SELECT COUNT(*) as c FROM expense_adjustments WHERE user_id = {$userId}");
        $myCount = $myRes ? (int)$myRes->fetch_assoc()['c'] : 0;

        $allRes = $this->db->query("SELECT COUNT(*) as c FROM expense_adjustments");
        $allCount = $allRes ? (int)$allRes->fetch_assoc()['c'] : 0;

        $actionableCount = 0;
        if ($actionableRole === 'accounts') {
            $actRes = $this->db->query("SELECT COUNT(*) as c FROM expense_adjustments WHERE (adjustment_type = 'return' AND status IN ('pending', 'partially_paid')) OR (adjustment_type = 'extra' AND status IN ('approved', 'partially_paid'))");
            $actionableCount = $actRes ? (int)$actRes->fetch_assoc()['c'] : 0;
        } elseif ($actionableRole === 'qc') {
            $actRes = $this->db->query("SELECT COUNT(*) as c FROM expense_adjustments WHERE adjustment_type = 'extra' AND status = 'pending'");
            $actionableCount = $actRes ? (int)$actRes->fetch_assoc()['c'] : 0;
        } elseif ($actionableRole === 'management') {
            $actRes = $this->db->query("SELECT COUNT(*) as c FROM expense_adjustments WHERE adjustment_type = 'extra' AND status = 'qc_approved'");
            $actionableCount = $actRes ? (int)$actRes->fetch_assoc()['c'] : 0;
        }

        return [
            'my' => $myCount,
            'actionable' => $actionableCount,
            'all' => $allCount
        ];
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
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
            WHERE ea.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getTransactions($adjustmentId)
    {
        $stmt = $this->db->prepare("
            SELECT at.*, u.full_name as added_by_name, r.name as added_by_role
            FROM adjustment_transactions at
            LEFT JOIN users u ON at.created_by = u.id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE at.adjustment_id = ?
            ORDER BY at.payment_date DESC, at.id DESC
        ");
        $stmt->bind_param("i", $adjustmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO expense_adjustments 
            (user_id, cashout_request_id, parent_type, adjustment_type, title, description, amount, document, note, project_id, office)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            error_log("ExpenseAdjustment::create - prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            "iisssssssis",
            $data['user_id'],
            $data['cashout_request_id'],
            $data['parent_type'],
            $data['adjustment_type'],
            $data['title'],
            $data['description'],
            $data['amount'],
            $data['document'],
            $data['note'],
            $data['project_id'],
            $data['office']
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function qcApprove($id, $qcUserId, $qcApprovedAmount, $qcNote = '')
    {
        $stmt = $this->db->prepare("
            UPDATE expense_adjustments 
            SET status = 'qc_approved', 
                qc_approved_by = ?, 
                qc_approved_at = NOW(), 
                qc_note = ?,
                approved_amount = ?,
                due_amount = ?
            WHERE id = ? AND status = 'pending' AND adjustment_type = 'extra'
        ");
        $stmt->bind_param("isddi", $qcUserId, $qcNote, $qcApprovedAmount, $qcApprovedAmount, $id);
        return $stmt->execute();
    }

    public function approve($id, $approvedBy, $approvedAmount, $mgmtNote = '')
    {
        return $this->mgmtApprove($id, $approvedBy, $approvedAmount, $mgmtNote);
    }

    public function mgmtApprove($id, $approvedBy, $approvedAmount, $mgmtNote = '')
    {
        $stmt = $this->db->prepare("
            UPDATE expense_adjustments 
            SET status = 'approved', 
                approved_by = ?, 
                approved_at = NOW(), 
                mgmt_note = ?,
                approved_amount = ?,
                due_amount = ? 
            WHERE id = ? AND (status = 'qc_approved' OR (status = 'pending' AND adjustment_type = 'extra'))
        ");
        $stmt->bind_param("isddi", $approvedBy, $mgmtNote, $approvedAmount, $approvedAmount, $id);
        return $stmt->execute();
    }

    public function reject($id, $rejectedBy, $rejectionNote = '')
    {
        $stmt = $this->db->prepare("
            UPDATE expense_adjustments 
            SET status = 'rejected', 
                approved_by = ?, 
                approved_at = NOW(),
                rejection_note = ?
            WHERE id = ? AND status IN ('pending', 'qc_approved')
        ");
        $stmt->bind_param("isi", $rejectedBy, $rejectionNote, $id);
        return $stmt->execute();
    }

    public function addTransaction($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO adjustment_transactions 
            (adjustment_id, transaction_type, amount, payment_date, payment_method, transaction_ref, bank_id, bank_account_no, cheque_no, online_type, disbursement_proof, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isdsssissdsi",
            $data['adjustment_id'],
            $data['transaction_type'],
            $data['amount'],
            $data['payment_date'],
            $data['payment_method'],
            $data['transaction_ref'],
            $data['bank_id'],
            $data['bank_account_no'],
            $data['cheque_no'],
            $data['online_type'],
            $data['disbursement_proof'],
            $data['created_by']
        );

        if ($stmt->execute()) {
            $txnId = $this->db->insert_id;
            $this->updateDueAmount($data['adjustment_id']);
            return $txnId;
        }
        return false;
    }

    public function updateDueAmount($adjustmentId)
    {
        $adj = $this->getById($adjustmentId);
        if (!$adj) return;

        $stmt = $this->db->prepare("SELECT SUM(amount) as total_paid FROM adjustment_transactions WHERE adjustment_id = ?");
        $stmt->bind_param("i", $adjustmentId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        $totalPaid = (float)($res['total_paid'] ?? 0);
        
        // If extra, target is approved_amount (or amount if approved_amount is not set yet)
        // If return, target is amount (or approved_amount)
        if ($adj['adjustment_type'] === 'return') {
            $target = (!empty($adj['amount']) && (float)$adj['amount'] > 0) ? (float)$adj['amount'] : (float)($adj['approved_amount'] ?? 0);
        } else {
            $target = (!empty($adj['approved_amount']) && (float)$adj['approved_amount'] > 0) ? (float)$adj['approved_amount'] : (float)$adj['amount'];
        }

        $remaining = $target - $totalPaid;
        
        $status = $adj['status'];
        if ($remaining <= 0 && $totalPaid > 0) {
            $status = 'fully_paid';
            $remaining = 0;
        } elseif ($totalPaid > 0) {
            $status = 'partially_paid';
        }

        $upStmt = $this->db->prepare("UPDATE expense_adjustments SET due_amount = ?, status = ? WHERE id = ?");
        $upStmt->bind_param("dsi", $remaining, $status, $adjustmentId);
        $upStmt->execute();
    }
}
