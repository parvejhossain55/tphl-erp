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
        $projectId = $data['project_id'] ?? null;
        $workTypeId = $data['work_type_id'] ?? null;
        $testTypeId = $data['test_type_id'] ?? null;
        $materialReceivingId = $data['material_receiving_id'] ?? null;
        $testName = $data['test_name'] ?? null;
        $testDate = $data['test_date'] ?? null;
        $ageOfSample = $data['age_of_sample'] ?? null;
        $requiredValue = $data['required_value'] ?? null;
        $resultValue = $data['result_value'] ?? null;
        $testStatus = $data['test_status'] ?? null;
        $labReportLink = $data['lab_report_link'] ?? null;
        $createdBy = $data['created_by'] ?? null;
        $attachment = $data['attachment'] ?? null;

        $stmt = $this->db->prepare("
            INSERT INTO qc_material_tests (
                project_id, work_type_id, test_type_id, material_receiving_id, 
                test_name, test_date, age_of_sample, required_value, 
                result_value, test_status, lab_report_link, created_by, attachment
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param('iiiississssss', 
            $projectId,
            $workTypeId,
            $testTypeId,
            $materialReceivingId,
            $testName,
            $testDate,
            $ageOfSample,
            $requiredValue,
            $resultValue,
            $testStatus,
            $labReportLink,
            $createdBy,
            $attachment
        );
        return $stmt->execute();
    }
}
