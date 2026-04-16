<?php
class Con_BOQ extends Model {
    protected $db;

    public function __construct($db = null) {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    public function getAll() {
        $sql = "SELECT b.*, p.name as project_name, u.full_name as created_by_name
                FROM boq_master b
                LEFT JOIN projects p ON b.project_id = p.id
                LEFT JOIN users u ON b.created_by = u.id
                ORDER BY b.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT b.*, p.name as project_name 
            FROM boq_master b 
            JOIN projects p ON b.project_id = p.id 
            WHERE b.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getItems($boqId) {
        $stmt = $this->db->prepare("
            SELECT i.*, m.name as material_name, m.unit 
            FROM boq_items i 
            JOIN con_materials m ON i.material_id = m.id 
            WHERE i.boq_id = ?
        ");
        $stmt->bind_param("i", $boqId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createMaster($data) {
        $stmt = $this->db->prepare("
            INSERT INTO boq_master (project_id, version, status, created_by) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("issi", $data['project_id'], $data['version'], $data['status'], $data['created_by']);
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function addItems($boqId, $items) {
        // Clear existing if any (for simple update/replace logic)
        $this->db->query("DELETE FROM boq_items WHERE boq_id = $boqId");

        $stmt = $this->db->prepare("
            INSERT INTO boq_items (boq_id, material_id, quantity, unit_cost) 
            VALUES (?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $stmt->bind_param("iidd", $boqId, $item['material_id'], $item['quantity'], $item['unit_cost']);
            $stmt->execute();
        }
        return true;
    }

    public function delete($id) {
        $this->db->query("DELETE FROM boq_items WHERE boq_id = $id");
        $stmt = $this->db->prepare("DELETE FROM boq_master WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
