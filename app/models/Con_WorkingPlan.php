<?php
class Con_WorkingPlan extends Model {
    protected $db;

    public function __construct($db = null) {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    public function getAll() {
        $sql = "SELECT w.*, p.name as project_name, m.name as material_name, m.unit, u.full_name as created_by_name
                FROM project_working_plan w
                JOIN projects p ON w.project_id = p.id
                JOIN con_materials m ON w.material_id = m.id
                JOIN users u ON w.created_by = u.id
                ORDER BY w.year DESC, w.month DESC, p.name ASC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getBudgetExtends() {
        $sql = "SELECT e.*, p.name as project_name, u.full_name as requested_by_name
                FROM budget_extend_requests e
                JOIN projects p ON e.project_id = p.id
                JOIN users u ON e.requested_by = u.id
                ORDER BY e.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function createPlan($data) {
        $stmt = $this->db->prepare("
            INSERT INTO project_working_plan (project_id, material_id, year, month, planned_quantity, estimated_cost, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiiddi", 
            $data['project_id'], 
            $data['material_id'], 
            $data['year'], 
            $data['month'], 
            $data['planned_quantity'], 
            $data['estimated_cost'], 
            $data['created_by']
        );
        return $stmt->execute();
    }

    public function createBudgetRequest($data) {
        $stmt = $this->db->prepare("
            INSERT INTO budget_extend_requests (project_id, requested_by, title, amount, reason) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisds", 
            $data['project_id'], 
            $data['requested_by'], 
            $data['title'], 
            $data['amount'], 
            $data['reason']
        );
        return $stmt->execute();
    }
}
