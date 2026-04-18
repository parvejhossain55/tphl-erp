<?php
class Con_ProjectDrawing extends Model {
    protected $db;

    public function __construct($db = null) {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    public function getAll() {
        $sql = "SELECT d.*, p.name as project_name, dt.name as design_type_name, 
                       u.full_name as internal_designer_name
                FROM project_drawings d
                LEFT JOIN projects p ON d.project_id = p.id
                LEFT JOIN design_types dt ON d.design_type_id = dt.id
                LEFT JOIN users u ON d.user_id = u.id
                ORDER BY d.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM project_drawings WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO project_drawings 
            (project_id, design_type_id, is_in_house, user_id, designer_name, designer_designation, designer_company, design_link, version, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $params = [
            $data['project_id'],
            $data['design_type_id'],
            $data['is_in_house'] ?? 1,
            $data['user_id'] ?? null,
            $data['designer_name'] ?? null,
            $data['designer_designation'] ?? null,
            $data['designer_company'] ?? null,
            $data['design_link'] ?? null,
            $data['version'] ?? '1.0',
            $data['status'] ?? 'Draft',
            $data['created_by']
        ];
        
        $stmt->bind_param("iiiissssssi", ...$params);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE project_drawings 
            SET project_id = ?, design_type_id = ?, is_in_house = ?, user_id = ?, designer_name = ?, designer_designation = ?, designer_company = ?, design_link = ?, version = ?, status = ? 
            WHERE id = ?
        ");
        
        $params = [
            $data['project_id'],
            $data['design_type_id'],
            $data['is_in_house'] ?? 1,
            $data['user_id'] ?? null,
            $data['designer_name'] ?? null,
            $data['designer_designation'] ?? null,
            $data['designer_company'] ?? null,
            $data['design_link'] ?? null,
            $data['version'] ?? '1.0',
            $data['status'] ?? 'Draft',
            $id
        ];
        
        $stmt->bind_param("iiiissssssi", ...$params);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM project_drawings WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
