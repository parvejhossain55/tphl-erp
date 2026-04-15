<?php
class Con_LandVisibilityStudy extends Model {
    protected $db;

    public function __construct($db = null) {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    public function getAll() {
        $sql = "SELECT l.*, p.name as project_name, u.full_name as created_by_name
                FROM land_visibility_study l
                LEFT JOIN projects p ON l.project_id = p.id
                LEFT JOIN users u ON l.created_by = u.id
                ORDER BY l.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM land_visibility_study WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO land_visibility_study 
            (project_id, land_legal_doc_status, digital_survey_report_link, govt_approval_doc_link, others_doc_link, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $params = [
            $data['project_id'],
            $data['land_legal_doc_status'] ?? 'Pending',
            $data['digital_survey_report_link'] ?? null,
            $data['govt_approval_doc_link'] ?? null,
            $data['others_doc_link'] ?? null,
            $data['created_by']
        ];
        
        $stmt->bind_param("issssi", ...$params);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE land_visibility_study 
            SET project_id = ?, land_legal_doc_status = ?, digital_survey_report_link = ?, govt_approval_doc_link = ?, others_doc_link = ? 
            WHERE id = ?
        ");
        
        $params = [
            $data['project_id'],
            $data['land_legal_doc_status'] ?? 'Pending',
            $data['digital_survey_report_link'] ?? null,
            $data['govt_approval_doc_link'] ?? null,
            $data['others_doc_link'] ?? null,
            $id
        ];
        
        $stmt->bind_param("issssi", ...$params);
        return $stmt->execute();
    }
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM land_visibility_study WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
