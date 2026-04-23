<?php
/**
 * Con_FinancialTransaction Model
 * Table: con_financial_transactions
 *
 * Schema:
 * id, ref_type (contractor_bill|operational_fund|vendor_payment),
 * ref_id, project_id, amount_paid, payment_type (Advance|Partial|Full|Refund),
 * payment_method (Cash|Cheque|BankTransfer|MobileWallet|Other),
 * transaction_date, reference_number, notes, disbursement_proof,
 * processed_by, update_status, created_by, created_at, updated_at
 */
class Con_FinancialTransaction extends Model
{
    protected $table = 'con_financial_transactions';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    // =========================================================================
    // READ
    // =========================================================================

    /** All transactions with project and processor info */
    public function getAll()
    {
        $sql = "SELECT cft.*,
                       p.name          AS project_name,
                       u.full_name     AS processed_by_name
                FROM {$this->table} cft
                LEFT JOIN projects p ON p.id  = cft.project_id
                LEFT JOIN users    u ON u.id  = cft.processed_by
                ORDER BY cft.created_at DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Find a single transaction */
    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT cft.*,
                    p.name      AS project_name,
                    u.full_name AS processed_by_name
             FROM {$this->table} cft
             LEFT JOIN projects p ON p.id = cft.project_id
             LEFT JOIN users    u ON u.id = cft.processed_by
             WHERE cft.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Transactions for a specific referenced record */
    public function getByRef($refType, $refId)
    {
        $stmt = $this->db->prepare(
            "SELECT cft.*, u.full_name AS processed_by_name
             FROM {$this->table} cft
             LEFT JOIN users u ON u.id = cft.processed_by
             WHERE cft.ref_type = ? AND cft.ref_id = ?
             ORDER BY cft.created_at DESC"
        );
        $stmt->bind_param('si', $refType, $refId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Total amount already paid for a specific source record */
    public function getTotalPaidForSource($refType, $refId)
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount_paid), 0) AS total_paid
             FROM {$this->table}
             WHERE ref_type = ? AND ref_id = ?"
        );
        $stmt->bind_param('si', $refType, $refId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (float)($row['total_paid'] ?? 0);
    }

    /**
     * Get approved (but not yet fully paid) records pending payment:
     * - contractor_bill_requests (status IN approved, partially_paid)
     * - operational_fund_requests (status IN approved, partially_paid)
     * - vendor_payment_requests (status IN Submitted, Approved, Partially_Paid)
     */
    public function getPendingApprovals()
    {
        $sql = "SELECT
                    'contractor_bill' AS ref_type,
                    cbr.id            AS ref_id,
                    cbr.project_id,
                    p.name            AS project_name,
                    CONCAT('Contractor Bill #', cbr.id) AS description,
                    COALESCE(cbr.approved_amount, cbr.bill_amount) AS amount,
                    cbr.due_amount,
                    'Full'            AS payment_type,
                    cbr.status        AS source_status,
                    NULL              AS vpr_status,
                    cbr.created_at
                FROM contractor_bill_requests cbr
                LEFT JOIN projects p ON p.id = cbr.project_id
                WHERE cbr.status IN ('approved', 'partially_paid')

                UNION ALL

                SELECT
                    'operational_fund' AS ref_type,
                    ofr.id             AS ref_id,
                    ofr.project_id,
                    p.name             AS project_name,
                    CONCAT('Fund Request - ', ofr.purpose) AS description,
                    COALESCE(ofr.approved_amount, ofr.amount) AS amount,
                    ofr.due_amount,
                    'Full'             AS payment_type,
                    ofr.status         AS source_status,
                    NULL               AS vpr_status,
                    ofr.created_at
                FROM operational_fund_requests ofr
                LEFT JOIN projects p ON p.id = ofr.project_id
                WHERE ofr.status IN ('approved', 'partially_paid')

                UNION ALL

                SELECT
                    'vendor_payment'  AS ref_type,
                    vpr.id            AS ref_id,
                    vpr.project_id,
                    p.name            AS project_name,
                    CONCAT(vpr.payment_type, ' Payment — ', v.name) AS description,
                    vpr.net_payable   AS amount,
                    vpr.due_amount,
                    vpr.payment_type  AS payment_type,
                    vpr.status        AS source_status,
                    vpr.status        AS vpr_status,
                    vpr.created_at
                FROM vendor_payment_requests vpr
                LEFT JOIN projects p ON p.id  = vpr.project_id
                LEFT JOIN vendors  v ON v.id  = vpr.vendor_id
                WHERE vpr.status IN ('Submitted', 'Approved', 'Partially_Paid')

                ORDER BY created_at DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Project-wise payment summary */
    public function getSummaryByProject($projectId)
    {
        $stmt = $this->db->prepare(
            "SELECT ref_type,
                    COUNT(*) AS transaction_count,
                    SUM(amount_paid) AS total_paid
             FROM {$this->table}
             WHERE project_id = ?
             GROUP BY ref_type"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // WRITE
    // =========================================================================

    /**
     * Create a transaction and update source record with partial payment tracking.
     *
     * @param array $data [ref_type, ref_id, project_id, amount, payment_type,
     *                     payment_method, payment_date, reference_no, notes,
     *                     disbursement_proof, processed_by]
     */
    public function create($data)
    {
        $this->db->begin_transaction();
        try {
            $paymentType        = $data['payment_type']       ?? 'Full';
            $refType            = $data['ref_type']            ?? '';
            $refId              = (int)($data['ref_id']        ?? 0);
            $projectId          = (int)($data['project_id']   ?? 0);
            $amount             = (float)($data['amount']     ?? 0);
            $paymentMethod      = $data['payment_method']      ?? 'Cash';
            $paymentDate        = $data['payment_date']        ?? date('Y-m-d');
            $referenceNo        = $data['reference_no']        ?? '';
            $notes              = $data['notes']               ?? '';
            $disbursementProof  = $data['disbursement_proof']  ?? null;
            $processedBy        = (int)($data['processed_by'] ?? 0);

            // 1. Insert transaction record
            $sql = "INSERT INTO {$this->table}
                    (ref_type, ref_id, project_id, amount_paid, payment_type, payment_method,
                     transaction_date_str, reference_number, notes, disbursement_proof,
                     processed_by, update_status, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Completed', ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                'siidssssssii',
                $refType,
                $refId,
                $projectId,
                $amount,
                $paymentType,
                $paymentMethod,
                $paymentDate,
                $referenceNo,
                $notes,
                $disbursementProof,
                $processedBy,
                $processedBy
            );
            if (!$stmt->execute()) {
                throw new \Exception("Insert failed: " . $stmt->error);
            }
            $txnId = $stmt->insert_id;

            // 2. Calculate total paid so far for this source
            $totalPaid = $this->getTotalPaidForSource($refType, $refId);

            // 3. Determine the approved/payable amount from the source
            $sourceAmount = $this->getSourceAmount($refType, $refId);

            // 4. Calculate due amount and determine new status
            $dueAmount = max(0, $sourceAmount - $totalPaid);

            if ($dueAmount <= 0) {
                $newStatus = $this->getFullyPaidStatus($refType);
            } else {
                $newStatus = $this->getPartiallyPaidStatus($refType);
            }

            // 5. Update source record with new status and due_amount
            $this->updateSourceRecord($refType, $refId, $newStatus, $dueAmount);

            $this->db->commit();
            return $txnId;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Financial transaction create failed: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // HELPERS (private)
    // =========================================================================

    /** Get the approved/payable amount from the source record */
    private function getSourceAmount($refType, $refId)
    {
        if ($refType === 'contractor_bill') {
            $stmt = $this->db->prepare(
                "SELECT COALESCE(approved_amount, bill_amount) AS payable FROM contractor_bill_requests WHERE id = ?"
            );
        } elseif ($refType === 'operational_fund') {
            $stmt = $this->db->prepare(
                "SELECT COALESCE(approved_amount, amount) AS payable FROM operational_fund_requests WHERE id = ?"
            );
        } elseif ($refType === 'vendor_payment') {
            $stmt = $this->db->prepare(
                "SELECT COALESCE(approved_amount, net_payable) AS payable FROM vendor_payment_requests WHERE id = ?"
            );
        } else {
            return 0;
        }

        $stmt->bind_param('i', $refId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (float)($row['payable'] ?? 0);
    }

    /** Get the 'fully_paid' status string for each source type */
    private function getFullyPaidStatus($refType)
    {
        return match ($refType) {
            'vendor_payment' => 'Fully_Paid',
            default          => 'fully_paid',
        };
    }

    /** Get the 'partially_paid' status string for each source type */
    private function getPartiallyPaidStatus($refType)
    {
        return match ($refType) {
            'vendor_payment' => 'Partially_Paid',
            default          => 'partially_paid',
        };
    }

    /** Update source record status and due_amount */
    private function updateSourceRecord($refType, $refId, $status, $dueAmount)
    {
        if ($refType === 'contractor_bill') {
            $stmt = $this->db->prepare(
                "UPDATE contractor_bill_requests SET status = ?, due_amount = ? WHERE id = ?"
            );
        } elseif ($refType === 'operational_fund') {
            $stmt = $this->db->prepare(
                "UPDATE operational_fund_requests SET status = ?, due_amount = ? WHERE id = ?"
            );
        } elseif ($refType === 'vendor_payment') {
            $stmt = $this->db->prepare(
                "UPDATE vendor_payment_requests SET status = ?, due_amount = ? WHERE id = ?"
            );
        } else {
            return;
        }

        $stmt->bind_param('sdi', $status, $dueAmount, $refId);
        $stmt->execute();
    }
}
