<?php

class Con_Material extends Model
{
    protected $table = 'con_materials';

    public function getAll()
    {
        $sql = "SELECT m.*,
                       u.full_name    AS created_by_name,
                       COALESCE(c.name, 'Uncategorized') AS category_name,
                       COALESCE(c.total_lead_days, 0) AS total_lead_days,
                       c.pr_to_qtn_days,
                       c.cs_analysis_days,
                       c.approval_days,
                       c.wo_po_days,
                       c.adv_payment_days,
                       c.transport_days
                FROM {$this->table} m
                LEFT JOIN users u ON u.id = m.created_by
                LEFT JOIN con_material_categories c ON c.id = m.category_id
                ORDER BY c.name ASC, m.name ASC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
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
        $sql = "INSERT INTO {$this->table} (category_id, name, unit, min_stock_level, created_by)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issdi',
            $data['category_id'],
            $data['name'],
            $data['unit'],
            $data['min_stock_level'],
            $data['created_by']
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} SET category_id=?, name=?, unit=?, min_stock_level=? WHERE id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issdi',
            $data['category_id'],
            $data['name'],
            $data['unit'],
            $data['min_stock_level'],
            $id
        );
        return $stmt->execute();
    }

    public function delete($id)
    {
        // Check if referenced in boq_items
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM boq_items WHERE material_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ((int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0) > 0) return false;

        // Check if referenced in purchase_request_items
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM purchase_request_items WHERE material_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ((int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0) > 0) return false;

        // Check if referenced in purchase_order_items
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM purchase_order_items WHERE material_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ((int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0) > 0) return false;

        // Check if referenced in con_stocks
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM con_stocks WHERE material_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ((int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0) > 0) return false;

        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $stmt->bind_param('i', $id);
            return $stmt->execute();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
