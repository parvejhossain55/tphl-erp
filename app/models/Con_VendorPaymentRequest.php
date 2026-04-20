<?php

class Con_VendorPaymentRequest extends Model
{
    protected $table = 'vendor_payment_requests';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    // =========================================================================
    // READ
    // =========================================================================

    public function getAll()
    {
        $sql = "SELECT pr.*, 
                       v.company_name AS vendor_name, 
                       p.name AS project_name,
                       u.full_name AS created_by_name,
                       a.full_name AS approved_by_name
                FROM {$this->table} pr
                LEFT JOIN con_vendors v ON v.id = pr.vendor_id
                LEFT JOIN projects p ON p.id = pr.project_id
                LEFT JOIN users u ON u.id = pr.created_by
                LEFT JOIN users a ON a.id = pr.approved_by
                ORDER BY pr.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT pr.*, 
                    v.company_name AS vendor_name, 
                    p.name AS project_name,
                    u.full_name AS created_by_name,
                    a.full_name AS approved_by_name
             FROM {$this->table} pr
             LEFT JOIN con_vendors v ON v.id = pr.vendor_id
             LEFT JOIN projects p ON p.id = pr.project_id
             LEFT JOIN users u ON u.id = pr.created_by
             LEFT JOIN users a ON a.id = pr.approved_by
             WHERE pr.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================================
    // HELPER: FETCH UNLINKED PENALTIES
    // =========================================================================

    public function getPendingPenaltySum($vendorId)
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(penalty_amount), 0) AS total_penalty 
             FROM vendor_penalties 
             WHERE vendor_id = ? AND payment_request_id IS NULL"
        );
        $stmt->bind_param('i', $vendorId);
        $stmt->execute();
        return (float) $stmt->get_result()->fetch_assoc()['total_penalty'];
    }

    // =========================================================================
    // CREATE (Transactable w/ Penalty Link)
    // =========================================================================

    public function create($data)
    {
        $this->db->begin_transaction();
        try {
            // 1. Insert Payment Request
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table}
                    (project_id, vendor_id, invoice_amount, penalty_deducted, net_payable, payment_date, remarks, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?)"
            );
            $stmt->bind_param(
                'iidddssi',
                $data['project_id'],
                $data['vendor_id'],
                $data['invoice_amount'],
                $data['penalty_deducted'],
                $data['net_payable'],
                $data['payment_date'],
                $data['remarks'],
                $data['created_by']
            );
            $stmt->execute();
            $requestId = $this->db->insert_id;

            // 2. Link pending penalties in vendor_penalties
            $updStmt = $this->db->prepare(
                "UPDATE vendor_penalties 
                 SET payment_request_id = ? 
                 WHERE vendor_id = ? AND payment_request_id IS NULL"
            );
            $updStmt->bind_param('ii', $requestId, $data['vendor_id']);
            $updStmt->execute();

            $this->db->commit();
            return $requestId;
            
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Vendor Payment Request creation failed: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // APPROVE
    // =========================================================================

    public function approve($id, $userId)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status = 'Approved', approved_by = ? 
             WHERE id = ? AND status = 'Pending'"
        );
        $stmt->bind_param('ii', $userId, $id);
        return $stmt->execute();
    }

    // =========================================================================
    // REJECT
    // =========================================================================

    public function reject($id, $userId)
    {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = 'Rejected', approved_by = ? 
                 WHERE id = ? AND status = 'Pending'"
            );
            $stmt->bind_param('ii', $userId, $id);
            $stmt->execute();

            // Unlink penalties so they can be applied to future requests
            $updStmt = $this->db->prepare(
                "UPDATE vendor_penalties 
                 SET payment_request_id = NULL 
                 WHERE payment_request_id = ?"
            );
            $updStmt->bind_param('i', $id);
            $updStmt->execute();

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }
}
