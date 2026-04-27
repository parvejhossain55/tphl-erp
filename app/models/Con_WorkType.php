<?php
class Con_WorkType {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll() {
        $sql = "SELECT wt.*, dt.name AS design_type_name, u.full_name AS created_by_name 
                FROM work_types wt
                LEFT JOIN design_types dt ON wt.design_type_id = dt.id
                LEFT JOIN users u ON wt.created_by = u.id
                ORDER BY wt.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM work_types WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO work_types (design_type_id, work_type_name, description, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('issi', 
            $data['design_type_id'], 
            $data['work_type_name'], 
            $data['description'], 
            $data['created_by']
        );
        return $stmt->execute();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE work_types SET design_type_id = ?, work_type_name = ?, description = ? WHERE id = ?");
        $stmt->bind_param('issi', 
            $data['design_type_id'], 
            $data['work_type_name'], 
            $data['description'], 
            $id
        );
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM work_types WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
