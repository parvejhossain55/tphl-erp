<?php
class Con_MaterialReturn extends Model
{
    protected $table = 'material_returns';
    protected $itemTable = 'material_return_items';

    public function getAll()
    {
        $sql = "SELECT mr.*, v.name AS vendor_name, p.name AS project_name,
                       u.full_name AS created_by_name, rec.received_status
                FROM {$this->table} mr
                LEFT JOIN vendors v ON v.id = mr.vendor_id
                LEFT JOIN projects p ON p.id = mr.project_id
                LEFT JOIN users u ON u.id = mr.created_by
                LEFT JOIN material_receiving rec ON rec.id = mr.receiving_id
                ORDER BY mr.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT mr.*, v.name AS vendor_name, p.name AS project_name,
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
                    m.name AS material_name, m.unit
             FROM {$this->itemTable} mri
             JOIN con_materials m ON m.id = mri.material_item_id
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

    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $id);
        return $stmt->execute();
    }
}
