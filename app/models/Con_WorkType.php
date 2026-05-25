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

    public function countAll() {
        $sql = "SELECT COUNT(*) as total FROM work_types";
        $result = $this->db->query($sql);
        $row = $result ? $result->fetch_assoc() : null;
        return $row ? (int)$row['total'] : 0;
    }

    public function getPaginated($limit, $offset) {
        $stmt = $this->db->prepare("SELECT wt.*, dt.name AS design_type_name, u.full_name AS created_by_name 
                                    FROM work_types wt
                                    LEFT JOIN design_types dt ON wt.design_type_id = dt.id
                                    LEFT JOIN users u ON wt.created_by = u.id
                                    ORDER BY wt.id DESC
                                    LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM work_types WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $designTypeId = $data['design_type_id'];
        $workTypeName = $data['work_type_name'];
        $defaultPhaseName = $data['default_phase_name'] ?? null;
        $description = $data['description'];
        $createdBy = $data['created_by'];

        $stmt = $this->db->prepare("INSERT INTO work_types (design_type_id, work_type_name, default_phase_name, description, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('isssi', 
            $designTypeId, 
            $workTypeName, 
            $defaultPhaseName, 
            $description, 
            $createdBy
        );
        return $stmt->execute();
    }

    public function update($id, $data) {
        $designTypeId = $data['design_type_id'];
        $workTypeName = $data['work_type_name'];
        $defaultPhaseName = $data['default_phase_name'] ?? null;
        $description = $data['description'];

        $stmt = $this->db->prepare("UPDATE work_types SET design_type_id = ?, work_type_name = ?, default_phase_name = ?, description = ? WHERE id = ?");
        $stmt->bind_param('isssi', 
            $designTypeId, 
            $workTypeName, 
            $defaultPhaseName, 
            $description, 
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
