<?php
class Con_ArchitectEngineering extends Model {
    protected $db;

    public function __construct($db = null) {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    public function getAll() {
        $sql = "SELECT a.*, p.name as project_name, u.full_name as user_name 
                FROM architect_engineering a
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM architect_engineering WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO architect_engineering 
            (project_id, user_id, full_architecture_design_link, notes, created_by) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $params = [
            $data['project_id'],
            $data['user_id'],
            $data['full_architecture_design_link'] ?? null,
            $data['notes'] ?? null,
            $data['created_by']
        ];
        
        $stmt->bind_param("iissi", ...$params);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE architect_engineering 
            SET project_id = ?, user_id = ?, full_architecture_design_link = ?, notes = ? 
            WHERE id = ?
        ");
        
        $params = [
            $data['project_id'],
            $data['user_id'],
            $data['full_architecture_design_link'] ?? null,
            $data['notes'] ?? null,
            $id
        ];
        
        $stmt->bind_param("iissi", ...$params);
        return $stmt->execute();
    }
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM architect_engineering WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
