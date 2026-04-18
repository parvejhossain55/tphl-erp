<?php
class Con_DesignType extends Model {
    protected $db;

    public function __construct($db = null) {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    public function getAll() {
        $sql = "SELECT * FROM design_types ORDER BY name ASC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM design_types WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO design_types (name, status, created_by) 
            VALUES (?, ?, ?)
        ");
        
        $params = [
            $data['name'],
            $data['status'] ?? 'active',
            $data['created_by']
        ];
        
        $stmt->bind_param("ssi", ...$params);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE design_types 
            SET name = ?, status = ? 
            WHERE id = ?
        ");
        
        $params = [
            $data['name'],
            $data['status'] ?? 'active',
            $id
        ];
        
        $stmt->bind_param("ssi", ...$params);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM design_types WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
