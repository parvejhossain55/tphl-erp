<?php

class Con_MaterialCategory extends Model
{
    protected $table = 'con_material_categories';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    /** All categories with how many materials belong to each */
    public function getAll()
    {
        $sql = "SELECT c.*,
                       COUNT(m.id) AS material_count
                FROM {$this->table} c
                LEFT JOIN con_materials m ON m.category_id = c.id
                GROUP BY c.id
                ORDER BY c.name ASC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (name, description, created_by) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('ssi', $data['name'], $data['description'], $data['created_by']);
        $stmt->execute();
        return $stmt->insert_id;
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET name = ?, description = ? WHERE id = ?"
        );
        $stmt->bind_param('ssi', $data['name'], $data['description'], $id);
        return $stmt->execute();
    }

    public function updateLeadTimes($id, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} 
             SET item_type = ?, pr_to_qtn_days = ?, cs_analysis_days = ?, approval_days = ?, 
                 wo_po_days = ?, adv_payment_days = ?, transport_days = ?, total_lead_days = ?
             WHERE id = ?"
        );
        $stmt->bind_param('siiiiiiii',
            $data['item_type'],
            $data['pr_to_qtn_days'],
            $data['cs_analysis_days'],
            $data['approval_days'],
            $data['wo_po_days'],
            $data['adv_payment_days'],
            $data['transport_days'],
            $data['total_lead_days'],
            $id
        );
        return $stmt->execute();
    }

    /** Delete only if no materials are linked */
    public function delete($id)
    {
        $check = $this->db->prepare("SELECT COUNT(*) AS cnt FROM con_materials WHERE category_id = ?");
        $check->bind_param('i', $id);
        $check->execute();
        $cnt = (int)($check->get_result()->fetch_assoc()['cnt'] ?? 0);
        if ($cnt > 0) {
            return false; // cannot delete — materials exist
        }
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
