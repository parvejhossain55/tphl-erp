<?php
class Con_SoilTest extends Model {
    protected $db;

    public function __construct($db = null) {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    public function getAll() {
        $sql = "SELECT s.*, p.name as project_name, u.full_name as engineer_name 
                FROM soil_tests s
                LEFT JOIN projects p ON s.project_id = p.id
                LEFT JOIN users u ON s.site_engineer_id = u.id
                ORDER BY s.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM soil_tests WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO soil_tests 
            (project_id, site_engineer_id, field_data, lab_test_document_link, field_test_doc_link, test_date, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $params = [
            $data['project_id'],
            $data['site_engineer_id'],
            $data['field_data'] ?? null,
            $data['lab_test_document_link'] ?? null,
            $data['field_test_doc_link'] ?? null,
            $data['test_date'] ?? null,
            $data['created_by']
        ];
        
        $stmt->bind_param("iissssi", ...$params);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE soil_tests 
            SET project_id = ?, site_engineer_id = ?, field_data = ?, lab_test_document_link = ?, field_test_doc_link = ?, test_date = ? 
            WHERE id = ?
        ");
        
        $params = [
            $data['project_id'],
            $data['site_engineer_id'],
            $data['field_data'] ?? null,
            $data['lab_test_document_link'] ?? null,
            $data['field_test_doc_link'] ?? null,
            $data['test_date'] ?? null,
            $id
        ];
        
        $stmt->bind_param("iisssi", ...$params);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM soil_tests WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
