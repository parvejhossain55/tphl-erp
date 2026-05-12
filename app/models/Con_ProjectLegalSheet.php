<?php
class Con_ProjectLegalSheet extends Model {
    protected $table = 'project_legal_sheets';

    public function __construct($db = null) {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    public function getAll() {
        $query = "SELECT pls.*, p.name as project_name, u.full_name as submitted_by_name 
                  FROM project_legal_sheets pls
                  LEFT JOIN projects p ON p.id = pls.project_id
                  LEFT JOIN users u ON u.id = pls.submitted_by
                  ORDER BY pls.id DESC";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getById($id) {
        $id = (int)$id;
        $query = "SELECT * FROM project_legal_sheets WHERE id = $id";
        $result = $this->db->query($query);
        return $result ? $result->fetch_assoc() : null;
    }

    public function create($data) {
        $project_id   = (int)$data['project_id'];
        $sheet_type   = $data['sheet_type'];
        $title        = $data['title'];
        $version      = $data['version'] ?? 'V1';
        $link         = $data['link'];
        $submitted_by = (int)$data['submitted_by'];
        $remark       = $data['remark'] ?? '';

        $stmt = $this->db->prepare("INSERT INTO project_legal_sheets 
                  (project_id, sheet_type, title, version, link, submitted_by, remark) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssis", $project_id, $sheet_type, $title, $version, $link, $submitted_by, $remark);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $id = (int)$id;
        $project_id   = (int)$data['project_id'];
        $sheet_type   = $data['sheet_type'];
        $title        = $data['title'];
        $version      = $data['version'] ?? 'V1';
        $link         = $data['link'];
        $remark       = $data['remark'] ?? '';

        $stmt = $this->db->prepare("UPDATE project_legal_sheets SET 
                  project_id = ?, 
                  sheet_type = ?, 
                  title = ?, 
                  version = ?, 
                  link = ?, 
                  remark = ? 
                  WHERE id = ?");
        $stmt->bind_param("isssssi", $project_id, $sheet_type, $title, $version, $link, $remark, $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $id = (int)$id;
        $query = "DELETE FROM project_legal_sheets WHERE id = $id";
        return $this->db->query($query);
    }
}
