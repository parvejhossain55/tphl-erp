<?php
/**
 * Con_VendorPenalty Model
 * Table: vendor_penalties
 *
 * ACTUAL DB SCHEMA:
 * id, vendor_id, is_deducted_from_bill, purchase_order_id, receiving_id,
 * penalty_category(Late Delivery|Quality Issue|Shortage|Others),
 * penalty_amount, is_fixed_amount, reason,
 * action_taken(Deduct from Bill|Replacement Taken|Fine Paid),
 * penalty_status(Pending|Approved|Rejected), attachment, created_by, created_at
 */
class Con_VendorPenalty extends Model
{
    protected $table = 'vendor_penalties';

    public function getAll()
    {
        $sql = "SELECT vp.*, COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name, v.phone AS vendor_phone,
                       u.full_name AS created_by_name
                FROM {$this->table} vp
                JOIN vendors v ON v.id = vp.vendor_id
                LEFT JOIN users u ON u.id = vp.created_by
                ORDER BY vp.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT vp.*, COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name
             FROM {$this->table} vp
             JOIN vendors v ON v.id = vp.vendor_id
             WHERE vp.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table}
                (vendor_id, purchase_order_id, penalty_category, penalty_amount, reason, action_taken, penalty_status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $poId          = (int)($data['purchase_order_id'] ?? 0);
        $penaltyStatus = $data['penalty_status'] ?? 'Pending';
        $actionTaken   = $data['action_taken'] ?? 'Deduct from Bill';
        $stmt->bind_param('iisdsssi',
            $data['vendor_id'],
            $poId,
            $data['penalty_category'],
            $data['penalty_amount'],
            $data['reason'],
            $actionTaken,
            $penaltyStatus,
            $data['created_by']
        );
        $stmt->execute();

        // Update vendor rating
        $this->recalcRating($data['vendor_id']);

        return $stmt->insert_id;
    }

    public function delete($id)
    {
        $penalty = $this->find($id);
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();

        if ($result && $penalty) {
            $this->recalcRating($penalty['vendor_id']);
        }
        return $result;
    }

    /**
     * Recalculate vendor rating: 5.0 - (penalty_count * 0.5), minimum 1.0
     */
    private function recalcRating($vendorId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM {$this->table} WHERE vendor_id = ?");
        $stmt->bind_param('i', $vendorId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        $newRating = max(1.0, 5.0 - ((int)$row['cnt'] * 0.5));

        $upd = $this->db->prepare("UPDATE vendors SET rating = ? WHERE id = ?");
        $upd->bind_param('di', $newRating, $vendorId);
        $upd->execute();
    }

    public function getStats()
    {
        $sql = "SELECT
                    COUNT(*) AS total_penalties,
                    COALESCE(SUM(penalty_amount), 0) AS total_amount,
                    COUNT(DISTINCT vendor_id) AS vendors_affected
                FROM {$this->table}";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_assoc() : [];
    }
}
