<?php
class Con_MaterialReturn extends Model
{
    protected $table = 'material_returns';
    protected $itemTable = 'material_return_items';

    public function getAll()
    {
        $sql = "SELECT mr.*, COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name, p.name AS project_name,
                       u.full_name AS created_by_name, rec.received_status,
                       rep.id AS replacement_receiving_id
                FROM {$this->table} mr
                LEFT JOIN vendors v ON v.id = mr.vendor_id
                LEFT JOIN projects p ON p.id = mr.project_id
                LEFT JOIN users u ON u.id = mr.created_by
                LEFT JOIN material_receiving rec ON rec.id = mr.receiving_id
                LEFT JOIN material_receiving rep ON rep.return_id = mr.id
                ORDER BY mr.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT mr.*, COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name, p.name AS project_name,
                    u.full_name AS created_by_name, rec.received_status
             FROM {$this->table} mr
             LEFT JOIN vendors v ON v.id = mr.vendor_id
             LEFT JOIN projects p ON p.id = mr.project_id
             LEFT JOIN users u ON u.id = mr.created_by
             LEFT JOIN material_receiving rec ON rec.id = mr.receiving_id
             WHERE mr.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getItems($returnId)
    {
        $stmt = $this->db->prepare(
            "SELECT mri.*, 
                    m.name AS material_name, m.unit, COALESCE(cat.name, 'Uncategorized') AS category_name
             FROM {$this->itemTable} mri
             JOIN con_materials m ON m.id = mri.material_item_id
             LEFT JOIN con_material_categories cat ON cat.id = m.category_id
             WHERE mri.return_id = ?
             ORDER BY mri.id ASC"
        );
        $stmt->bind_param('i', $returnId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create($data, $items)
    {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table}
                    (receiving_id, vendor_id, project_id, return_date, return_reason, attachment, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $status = $data['status'] ?? 'pending';
            $stmt->bind_param('iiissssi',
                $data['receiving_id'],
                $data['vendor_id'],
                $data['project_id'],
                $data['return_date'],
                $data['return_reason'],
                $data['attachment'],
                $status,
                $data['created_by']
            );
            $stmt->execute();
            $returnId = $this->db->insert_id;

            $itemStmt = $this->db->prepare(
                "INSERT INTO {$this->itemTable}
                    (return_id, material_item_id, return_qty, unit_price)
                 VALUES (?, ?, ?, ?)"
            );
            
            foreach ($items as $item) {
                $itemStmt->bind_param('iidd',
                    $returnId,
                    $item['material_item_id'],
                    $item['return_qty'],
                    $item['unit_price']
                );
                $itemStmt->execute();
            }

            $this->db->commit();
            return $returnId;
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Material Return create failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($id, $status, $userId = null)
    {
        if ($status === 'sent' && $userId) {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ?, sent_by = ?, sent_at = NOW() WHERE id = ?");
            $stmt->bind_param('sii', $status, $userId, $id);
        } else {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $status, $id);
        }
        return $stmt->execute();
    }

    public function getByPurchaseOrder($poId)
    {
        $stmt = $this->db->prepare(
            "SELECT mr.*, COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name, u.full_name AS created_by_name,
                    su.full_name AS sent_by_name,
                    rep.id AS replacement_receiving_id,
                    rep.overall_status AS replacement_qc_status
             FROM {$this->table} mr
             JOIN material_receiving rec ON rec.id = mr.receiving_id
             LEFT JOIN vendors v ON v.id = mr.vendor_id
             LEFT JOIN users u ON u.id = mr.created_by
             LEFT JOIN users su ON su.id = mr.sent_by
             LEFT JOIN material_receiving rep ON rep.return_id = mr.id
             WHERE rec.purchase_order_id = ?
             ORDER BY mr.id DESC"
        );
        if (!$stmt) return [];
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        $returns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($returns as &$ret) {
            $ret['items'] = $this->getItems($ret['id']);
        }
        return $returns;
    }

    public function getReplacementPendingItems($returnId)
    {
        $stmt = $this->db->prepare(
            "SELECT poi.id, poi.quantity, mri.return_qty AS pending_qty,
                    0 AS received_total,
                    m.name AS material_name, m.unit,
                    COALESCE(cat.name, 'Uncategorized') AS category_name,
                    COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name
             FROM {$this->itemTable} mri
             JOIN {$this->table} mr ON mr.id = mri.return_id
             JOIN material_receiving rec ON rec.id = mr.receiving_id
             JOIN purchase_order_items poi ON poi.purchase_order_id = rec.purchase_order_id AND poi.material_id = mri.material_item_id
             JOIN con_materials m ON m.id = mri.material_item_id
             LEFT JOIN con_material_categories cat ON cat.id = m.category_id
             LEFT JOIN vendors v ON v.id = COALESCE(mr.vendor_id, poi.selected_vendor_id)
             WHERE mri.return_id = ?
             GROUP BY poi.id, poi.quantity, mri.return_qty, m.name, m.unit, cat.name, v.company_name, v.name
             ORDER BY mri.id ASC"
        );
        if (!$stmt) return [];
        $stmt->bind_param('i', $returnId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
