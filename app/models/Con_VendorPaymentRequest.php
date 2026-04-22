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

    /**
     * Get all vendor payment requests with relations + approved_by/payment_by names
     * @param int|null $userId  If set, only show requests created by this user
     */
    public function getAll($userId = null)
    {
        $sql = "SELECT pr.*,
                       v.name       AS vendor_name,
                       p.name       AS project_name,
                       po.id        AS po_number,
                       u.full_name  AS created_by_name,
                       ab.full_name AS approved_by_name,
                       pb.full_name AS payment_by_name
                FROM {$this->table} pr
                LEFT JOIN vendors v          ON v.id  = pr.vendor_id
                LEFT JOIN projects p         ON p.id  = pr.project_id
                LEFT JOIN purchase_orders po ON po.id = pr.purchase_order_id
                LEFT JOIN users u            ON u.id  = pr.created_by
                LEFT JOIN users ab           ON ab.id = pr.approved_by
                LEFT JOIN (
                    SELECT ref_id, processed_by
                    FROM con_financial_transactions
                    WHERE ref_type = 'vendor_payment'
                    ORDER BY id DESC
                ) latest_txn ON latest_txn.ref_id = pr.id
                LEFT JOIN users pb ON pb.id = latest_txn.processed_by";

        if ($userId !== null) {
            $sql .= " WHERE pr.created_by = " . (int)$userId;
        }

        $sql .= " GROUP BY pr.id ORDER BY pr.id DESC";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT pr.*,
                    v.name       AS vendor_name,
                    p.name       AS project_name,
                    po.id        AS po_number,
                    u.full_name  AS created_by_name,
                    ab.full_name AS approved_by_name
             FROM {$this->table} pr
             LEFT JOIN vendors v          ON v.id  = pr.vendor_id
             LEFT JOIN projects p         ON p.id  = pr.project_id
             LEFT JOIN purchase_orders po ON po.id = pr.purchase_order_id
             LEFT JOIN users u            ON u.id  = pr.created_by
             LEFT JOIN users ab           ON ab.id = pr.approved_by
             WHERE pr.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Get all payment requests for a specific Purchase Order */
    public function getByPurchaseOrder($poId)
    {
        $stmt = $this->db->prepare(
            "SELECT pr.*,
                    v.name AS vendor_name,
                    u.full_name AS created_by_name,
                    COALESCE(paid.total_paid, 0) AS total_paid
             FROM {$this->table} pr
             LEFT JOIN vendors v ON v.id = pr.vendor_id
             LEFT JOIN users u   ON u.id = pr.created_by
             LEFT JOIN (
                 SELECT ref_id, SUM(amount_paid) AS total_paid
                 FROM con_financial_transactions
                 WHERE ref_type = 'vendor_payment'
                 GROUP BY ref_id
             ) paid ON paid.ref_id = pr.id
             WHERE pr.purchase_order_id = ?
             ORDER BY pr.id ASC"
        );
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    // HELPER: TOTAL ADVANCE PAID FOR A PURCHASE ORDER
    // =========================================================================

    public function getTotalAdvancePaid($poId)
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(cft.amount_paid), 0) AS total_advance
             FROM con_financial_transactions cft
             INNER JOIN {$this->table} vpr ON vpr.id = cft.ref_id
             WHERE cft.ref_type = 'vendor_payment'
               AND vpr.purchase_order_id = ?
               AND cft.payment_type = 'Advance'"
        );
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        return (float) $stmt->get_result()->fetch_assoc()['total_advance'];
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create($data)
    {
        $this->db->begin_transaction();
        try {
            $paymentType     = $data['payment_type'] ?? 'Final';
            $purchaseOrderId = (int)($data['purchase_order_id'] ?? 0);
            $deliveryVerified = ($paymentType === 'Advance') ? 0 : (int)($data['delivery_verified'] ?? 0);
            $penaltyAmt      = ($paymentType === 'Advance') ? 0.00 : (float)($data['penalty_deducted'] ?? 0);
            $invoiceAmt      = (float)($data['invoice_amount'] ?? 0);
            $netPayable      = ($paymentType === 'Advance')
                                    ? $invoiceAmt
                                    : (float)($data['net_payable'] ?? $invoiceAmt);
            $createdBy       = (int)($data['created_by'] ?? 0);
            $projectId       = (int)($data['project_id'] ?? 0);
            $vendorId        = (int)($data['vendor_id'] ?? 0);

            // INSERT — matches actual table columns
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table}
                    (project_id, vendor_id, purchase_order_id, payment_type,
                     invoice_amount, penalty_amount, net_payable_amount, net_payable,
                     delivery_verified, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', ?)"
            );
            $stmt->bind_param(
                'iiisddddii',
                $projectId,
                $vendorId,
                $purchaseOrderId,
                $paymentType,
                $invoiceAmt,
                $penaltyAmt,
                $netPayable,   // net_payable_amount
                $netPayable,   // net_payable (same value)
                $deliveryVerified,
                $createdBy
            );
            $stmt->execute();
            $requestId = $this->db->insert_id;

            if (!$requestId) {
                throw new \Exception("Insert returned 0 id");
            }

            // Link pending penalties to this request (only for non-Advance)
            if ($paymentType !== 'Advance' && $vendorId > 0) {
                $updStmt = $this->db->prepare(
                    "UPDATE vendor_penalties
                     SET payment_request_id = ?
                     WHERE vendor_id = ? AND payment_request_id IS NULL"
                );
                $updStmt->bind_param('ii', $requestId, $vendorId);
                $updStmt->execute();
            }

            $this->db->commit();
            return $requestId;

        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("VPR create failed: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // APPROVE — now tracks approved_by and sets approved_amount + due_amount
    // =========================================================================

    public function approve($id, $userId, $approvedAmount = null)
    {
        $request = $this->find($id);
        if (!$request || $request['status'] !== 'Submitted') return false;

        if ($approvedAmount === null || $approvedAmount <= 0) {
            $approvedAmount = (float)($request['net_payable'] ?? 0);
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status = 'Approved', approved_amount = ?, approved_by = ?, due_amount = ?
             WHERE id = ? AND status = 'Submitted'"
        );
        $stmt->bind_param('didi', $approvedAmount, $userId, $approvedAmount, $id);
        return $stmt->execute();
    }

    // =========================================================================
    // REJECT — now tracks approved_by
    // =========================================================================

    public function reject($id, $userId)
    {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = 'Rejected', approved_by = ?
                 WHERE id = ? AND status = 'Submitted'"
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
