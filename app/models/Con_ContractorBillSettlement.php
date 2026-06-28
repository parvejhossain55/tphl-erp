<?php

class Con_ContractorBillSettlement
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // -------------------------------------------------------------------------
    // READ
    // -------------------------------------------------------------------------

    public function getByBillId(int $billId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, u.full_name AS submitted_by_name, v.full_name AS verified_by_name
            FROM con_contractor_bill_settlements s
            LEFT JOIN users u ON u.id = s.submitted_by
            LEFT JOIN users v ON v.id = s.balance_verified_by
            WHERE s.bill_id = ?
            ORDER BY s.created_at ASC
        ");
        $stmt->bind_param('i', $billId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, u.full_name AS submitted_by_name, v.full_name AS verified_by_name
            FROM con_contractor_bill_settlements s
            LEFT JOIN users u ON u.id = s.submitted_by
            LEFT JOIN users v ON v.id = s.balance_verified_by
            WHERE s.id = ?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    // -------------------------------------------------------------------------
    // CREATE
    // -------------------------------------------------------------------------

    public function create(array $data): int|false
    {
        $proofJson   = is_array($data['proof_images']) ? json_encode($data['proof_images']) : ($data['proof_images'] ?? null);
        $receivedAmt = (float)($data['received_amount'] ?? 0);
        $paidAmt     = (float)($data['paid_amount']    ?? 0);
        $balance     = max(0.0, $receivedAmt - $paidAmt);

        $stmt = $this->db->prepare("
            INSERT INTO con_contractor_bill_settlements
              (bill_id, project_id, contractor_id, submitted_by,
               paid_amount, paid_date, payment_method, reference_no,
               labour_count, work_note, proof_images,
               received_amount, balance_amount, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'submitted')
        ");
        if (!$stmt) {
            error_log("CBS::create prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            'iiiidsssissdd',
            $data['bill_id'],
            $data['project_id'],
            $data['contractor_id'],
            $data['submitted_by'],
            $paidAmt,
            $data['paid_date'],
            $data['payment_method'],
            $data['reference_no'],
            $data['labour_count'],
            $data['work_note'],
            $proofJson,
            $receivedAmt,
            $balance
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        error_log("CBS::create execute failed: " . $this->db->error);
        return false;
    }

    // -------------------------------------------------------------------------
    // STATUS UPDATES
    // -------------------------------------------------------------------------

    /** Site engineer marks that they returned the leftover balance to accounts */
    public function markBalanceReturned(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE con_contractor_bill_settlements
            SET balance_returned = 1,
                balance_returned_at = NOW(),
                status = 'accounts_received'
            WHERE id = ? AND balance_returned = 0
        ");
        $stmt->bind_param('i', $id);
        return $stmt->execute() && $this->db->affected_rows > 0;
    }

    /** Accounts confirms they received the settlement / balance returned */
    public function verify(int $id, int $verifiedBy, string $note = ''): bool
    {
        $stmt = $this->db->prepare("
            UPDATE con_contractor_bill_settlements
            SET balance_verified     = 1,
                balance_verified_by  = ?,
                balance_verified_at  = NOW(),
                accounts_note        = ?,
                status               = 'verified'
            WHERE id = ?
        ");
        $stmt->bind_param('isi', $verifiedBy, $note, $id);
        return $stmt->execute();
    }

    // -------------------------------------------------------------------------
    // AGGREGATES
    // -------------------------------------------------------------------------

    public function getTotalPaidByBill(int $billId): float
    {
        $id  = (int)$billId;
        $res = $this->db->query(
            "SELECT COALESCE(SUM(paid_amount), 0) AS total
             FROM con_contractor_bill_settlements
             WHERE bill_id = $id"
        );
        return $res ? (float)$res->fetch_assoc()['total'] : 0.0;
    }
}