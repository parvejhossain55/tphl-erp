<?php

class Con_FundRequest extends Model
{
    protected $table = 'operational_fund_requests';

    /**
     * Get all fund requests with relations + payment processor name
     * @param int|null $userId  If set, only show requests by this user
     */
    public function getAllWithRelations($userId = null)
    {
        $sql = "SELECT ofr.*,
                       p.name       AS project_name,
                       u.full_name  AS requester_name,
                       oo.name      AS operator_name,
                       ab.full_name AS approved_by_name,
                       pb.full_name AS payment_by_name
                FROM {$this->table} ofr
                LEFT JOIN projects p              ON p.id   = ofr.project_id
                LEFT JOIN users u                 ON u.id   = ofr.requester_id
                LEFT JOIN operations_operators oo ON oo.id  = ofr.operator_id
                LEFT JOIN users ab                ON ab.id  = ofr.approved_by
                LEFT JOIN (
                    SELECT ref_id, processed_by
                    FROM con_financial_transactions
                    WHERE ref_type = 'operational_fund'
                    ORDER BY id DESC
                ) latest_txn ON latest_txn.ref_id = ofr.id
                LEFT JOIN users pb ON pb.id = latest_txn.processed_by";

        if ($userId !== null) {
            $sql .= " WHERE ofr.requester_id = " . (int)$userId;
        }

        $sql .= " GROUP BY ofr.id ORDER BY ofr.created_at DESC";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function findWithRelations($id)
    {
        $sql = "SELECT ofr.*,
                       p.name      AS project_name,
                       u.full_name AS requester_name,
                       oo.name     AS operator_name,
                       ab.full_name AS approved_by_name
                FROM {$this->table} ofr
                LEFT JOIN projects p              ON p.id   = ofr.project_id
                LEFT JOIN users u                 ON u.id   = ofr.requester_id
                LEFT JOIN operations_operators oo ON oo.id  = ofr.operator_id
                LEFT JOIN users ab                ON ab.id  = ofr.approved_by
                WHERE ofr.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data)
    {
        $sql = "INSERT INTO {$this->table}
                (project_id, requester_id, operator_id, amount, purpose, fund_date, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iiidsssi',
            $data['project_id'],
            $data['requester_id'],
            $data['operator_id'],
            $data['amount'],
            $data['purpose'],
            $data['fund_date'],
            $data['status'],
            $data['created_by']
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    public function updateStatus($id, $status, $approvedAmount = null, $approvedBy = null)
    {
        $sql = "UPDATE {$this->table}
                SET status=?, approved_amount=?, approved_by=?, updated_at=NOW()
                WHERE id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sdii', $status, $approvedAmount, $approvedBy, $id);
        return $stmt->execute();
    }
}
