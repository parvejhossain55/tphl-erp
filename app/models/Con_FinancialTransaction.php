<?php
/**
 * Con_FinancialTransaction Model
 * Table: con_financial_transactions
 *
 * Schema:
 * id, ref_type (contractor_bill|operational_fund|vendor_payment),
 * ref_id, project_id, amount, payment_method (Cash|Cheque|BankTransfer|MobileWallet),
 * payment_date, reference_no, notes, processed_by, created_at
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

    /**
     * Get approved (but not yet paid) contractor bills and fund requests
     * These are pending payment processing
     */
    public function getPendingApprovals()
    {
        $sql = "SELECT
                    'contractor_bill' AS ref_type,
                    cbr.id            AS ref_id,
                    cbr.project_id,
                    p.name            AS project_name,
                    CONCAT('Contractor Bill - ', oo.name) AS description,
                    cbr.bill_amount   AS amount,
                    cbr.created_at
                FROM contractor_bill_requests cbr
                LEFT JOIN projects p ON p.id = cbr.project_id
                LEFT JOIN operations_operators oo ON oo.id = cbr.operator_id
                WHERE cbr.status = 'approved'

                UNION ALL

                SELECT
                    'operational_fund' AS ref_type,
                    ofr.id             AS ref_id,
                    ofr.project_id,
                    p.name             AS project_name,
                    CONCAT('Fund Request - ', ofr.purpose) AS description,
                    COALESCE(ofr.approved_amount, ofr.amount) AS amount,
                    ofr.created_at
                FROM operational_fund_requests ofr
                LEFT JOIN projects p ON p.id = ofr.project_id
                WHERE ofr.status = 'approved'

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
                    SUM(amount) AS total_paid
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
     * Create a transaction and mark the source record as 'paid'
     * @param array $data [ref_type, ref_id, project_id, amount, payment_method, payment_date, reference_no, notes, processed_by]
     */
    public function create($data)
    {
        $this->db->begin_transaction();
        try {
            // 1. Insert transaction record
            $sql = "INSERT INTO {$this->table}
                    (ref_type, ref_id, project_id, amount, payment_method,
                     payment_date, reference_no, notes, processed_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                'siidssss i',
                $data['ref_type'],
                $data['ref_id'],
                $data['project_id'],
                $data['amount'],
                $data['payment_method'],
                $data['payment_date'],
                $data['reference_no'],
                $data['notes'],
                $data['processed_by']
            );
            $stmt->execute();
            $txnId = $stmt->insert_id;

            // 2. Mark source record as 'paid'
            if ($data['ref_type'] === 'contractor_bill') {
                $this->db->query(
                    "UPDATE contractor_bill_requests SET status='paid' WHERE id=" . (int)$data['ref_id']
                );
            } elseif ($data['ref_type'] === 'operational_fund') {
                $this->db->query(
                    "UPDATE operational_fund_requests SET status='paid' WHERE id=" . (int)$data['ref_id']
                );
            }

            $this->db->commit();
            return $txnId;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
}
