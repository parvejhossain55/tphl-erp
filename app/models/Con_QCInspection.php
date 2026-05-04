<?php
class Con_QCInspection {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll() {
        $sql = "SELECT i.*, p.name as project_name, wt.work_type_name, u.full_name as inspector_name
                FROM qc_inspections i
                LEFT JOIN projects p ON i.project_id = p.id
                LEFT JOIN work_types wt ON i.work_type_id = wt.id
                LEFT JOIN users u ON i.inspector_id = u.id
                ORDER BY i.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM qc_inspections WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function checkBlocker($project_id, $work_type_id) {
        // If overall_status is not 'passed', contractor_bill_requests should be blocked
        $stmt = $this->db->prepare("SELECT overall_status FROM qc_inspections WHERE project_id = ? AND work_type_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('ii', $project_id, $work_type_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        // If there's no inspection yet, or if it's failed/rework_needed, return true (Blocked)
        if (!$result || $result['overall_status'] !== 'passed') {
            return true; 
        }
        return false;
    }

    public function create($data) {
        $attachment = $data['attachment'] ?? null;
        $stmt = $this->db->prepare("INSERT INTO qc_inspections (project_id, work_type_id, inspector_id, inspection_date, overall_status, comments, attachment) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iiissss', 
            $data['project_id'], 
            $data['work_type_id'], 
            $data['inspector_id'], 
            $data['inspection_date'], 
            $data['overall_status'], 
            $data['comments'],
            $attachment
        );
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }
}
