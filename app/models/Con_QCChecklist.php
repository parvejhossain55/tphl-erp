<?php
class Con_QCChecklist {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll() {
        $sql = "SELECT c.*, wt.work_type_name, m.name as material_name, COALESCE(cat.name, 'Uncategorized') AS category_name
                FROM qc_checklists c
                LEFT JOIN work_types wt ON c.work_type_id = wt.id
                LEFT JOIN con_materials m ON c.material_item_id = m.id
                LEFT JOIN con_material_categories cat ON cat.id = m.category_id
                ORDER BY c.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getByWorkType($work_type_id) {
        $stmt = $this->db->prepare("SELECT * FROM qc_checklists WHERE work_type_id = ?");
        $stmt->bind_param('i', $work_type_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO qc_checklists (work_type_id, material_item_id, checklist_item_name, created_by) VALUES (?, ?, ?, ?)");
        $material_id = !empty($data['material_item_id']) ? $data['material_item_id'] : null;
        $stmt->bind_param('iisi', 
            $data['work_type_id'], 
            $material_id, 
            $data['checklist_item_name'], 
            $data['created_by']
        );
        return $stmt->execute();
    }
}
