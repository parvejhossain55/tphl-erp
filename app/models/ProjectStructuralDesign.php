<?php
class ProjectStructuralDesign extends Model {
    protected $db;

    public function __construct($db = null) {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    public function getAll() {
        $sql = "SELECT sd.*, p.name as project_name
                FROM project_structural_designs sd
                LEFT JOIN projects p ON sd.project_id = p.id
                ORDER BY sd.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM project_structural_designs WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO project_structural_designs 
            (project_id, designer_type, designer_name, designer_phone, designer_address, design_link,company_name, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?,?)
        ");
        
        $params = [
            $data['project_id'],
            $data['designer_type'] ?? 'internal',
            $data['designer_name'],
            $data['designer_phone'] ?? null,
            $data['designer_address'] ?? null,
            $data['design_link'] ?? '',
            $data['company_name'],
            $data['created_by']
        ];
        
        $stmt->bind_param("issssssi", ...$params);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE project_structural_designs 
            SET project_id = ?, designer_type = ?, designer_name = ?, designer_phone = ?, designer_address = ?, design_link = ? ,company_name=?
            WHERE id = ?
        ");
        
        $params = [
            $data['project_id'],
            $data['designer_type'] ?? 'internal',
            $data['designer_name'],
            $data['designer_phone'] ?? null,
            $data['designer_address'] ?? null,
            $data['design_link'] ?? '',
            $data['company_name'] ?? '',
            $id
        ];
        
        $stmt->bind_param("issssssi", ...$params);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM project_structural_designs WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
