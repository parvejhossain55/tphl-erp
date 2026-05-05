<?php
class Con_QCMaterialTest {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll() {
        $sql = "SELECT mt.*, u.full_name as created_by_name
                FROM qc_material_tests mt
                LEFT JOIN material_receiving mr ON mt.material_receiving_id = mr.id
                LEFT JOIN users u ON mt.created_by = u.id
                ORDER BY mt.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function create($data) {
        $attachment = $data['attachment'] ?? null;
        $stmt = $this->db->prepare("INSERT INTO qc_material_tests (material_receiving_id, test_name, result_value, test_status, lab_report_link, created_by, attachment) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issssis', 
            $data['material_receiving_id'], 
            $data['test_name'], 
            $data['result_value'], 
            $data['test_status'], 
            $data['lab_report_link'], 
            $data['created_by'],
            $attachment
        );
        return $stmt->execute();
    }
}
