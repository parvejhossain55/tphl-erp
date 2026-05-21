<?php

class ExpenseAdjustment
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll($userId = null)
    {
        $query = "
            SELECT ea.*, u.full_name as requester_name,
                   COALESCE(cr.purpose_paid_to, CONCAT('Vendor Payment ', vpr.invoice_number), cbr.work_description, pr.notes) as original_purpose,
                   COALESCE(cr.amount, vpr.net_payable_amount, cbr.bill_amount, 0) as original_amount,
                   p.name as project_name, cr.office
            FROM expense_adjustments ea
            JOIN users u ON ea.user_id = u.id
            LEFT JOIN cashout_requests cr ON ea.cashout_request_id = cr.id AND ea.parent_type = 'cashout'
            LEFT JOIN vendor_payment_requests vpr ON ea.cashout_request_id = vpr.id AND ea.parent_type = 'vendor'
            LEFT JOIN contractor_bill_requests cbr ON ea.cashout_request_id = cbr.id AND ea.parent_type = 'contractor'
            LEFT JOIN purchase_requests pr ON ea.cashout_request_id = pr.id AND ea.parent_type = 'purchase'
            LEFT JOIN projects p ON ea.project_id = p.id
        ";

        if ($userId) {
            $query .= " WHERE ea.user_id = " . (int)$userId;
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

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT ea.*, u.full_name as requester_name, r.name as requester_role,
                   COALESCE(cr.purpose_paid_to, CONCAT('Vendor Payment ', vpr.invoice_number), cbr.work_description, pr.notes) as original_purpose, 
                   COALESCE(cr.amount, vpr.net_payable_amount, cbr.bill_amount, 0) as original_amount,
                   COALESCE(cr.description, 'Vendor request', cbr.work_description, pr.notes) as original_desc,
                   p.name as project_name,
                   ap.full_name as approver_name
            FROM expense_adjustments ea
            JOIN users u ON ea.user_id = u.id
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN cashout_requests cr ON ea.cashout_request_id = cr.id AND ea.parent_type = 'cashout'
            LEFT JOIN vendor_payment_requests vpr ON ea.cashout_request_id = vpr.id AND ea.parent_type = 'vendor'
            LEFT JOIN contractor_bill_requests cbr ON ea.cashout_request_id = cbr.id AND ea.parent_type = 'contractor'
            LEFT JOIN purchase_requests pr ON ea.cashout_request_id = pr.id AND ea.parent_type = 'purchase'
            LEFT JOIN projects p ON ea.project_id = p.id
            LEFT JOIN users ap ON ea.approved_by = ap.id
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

    public function approve($id, $approvedBy, $approvedAmount)
    {
        $stmt = $this->db->prepare("
            UPDATE expense_adjustments 
            SET status = 'approved', 
                approved_by = ?, 
                approved_at = NOW(), 
                approved_amount = ?,
                due_amount = ? 
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->bind_param("iddi", $approvedBy, $approvedAmount, $approvedAmount, $id);
        return $stmt->execute();
    }

    public function reject($id, $approvedBy)
    {
        $stmt = $this->db->prepare("
            UPDATE expense_adjustments 
            SET status = 'rejected', 
                approved_by = ?, 
                approved_at = NOW() 
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->bind_param("ii", $approvedBy, $id);
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
            $this->updateDueAmount($data['adjustment_id']);
            return true;
        }
        return false;
    }

    private function updateDueAmount($adjustmentId)
    {
        $adj = $this->getById($adjustmentId);
        if (!$adj) return;

        $stmt = $this->db->prepare("SELECT SUM(amount) as total_paid FROM adjustment_transactions WHERE adjustment_id = ?");
        $stmt->bind_param("i", $adjustmentId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        $totalPaid = $res['total_paid'] ?? 0;
        
        // For 'return', target is original amount. For 'extra', target is approved_amount
        $target = ($adj['adjustment_type'] == 'return') ? $adj['amount'] : $adj['approved_amount'];
        $remaining = $target - $totalPaid;
        
        $status = $adj['status'];
        if ($remaining <= 0) {
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
