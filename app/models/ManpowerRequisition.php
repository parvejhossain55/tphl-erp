<?php
class ManpowerRequisition extends Model
{
    protected $db;
    protected $table = 'manpower_requisitions';

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all requisitions
    public function getAll()
    {
        $sql = "SELECT mr.*, d.name as department_name, des.name as designation_name 
                FROM manpower_requisitions mr
                LEFT JOIN departments d ON mr.department_id = d.id
                LEFT JOIN designations des ON mr.designation_id = des.id
                ORDER BY mr.id DESC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM manpower_requisitions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data)
    {
        $sql = "INSERT INTO manpower_requisitions (
            created_by,
            department_id,
            designation_id,
            job_title,
            no_of_vacancies,
            job_experience,
            to_be_filled_before,
            age_limit,
            report_to,
            requirement_type,
            project_id,
            reason,
            job_description,
            education_qualification,
            special_skills,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("ManpowerRequisition prepare failed: " . $this->db->error);
            return false;
        }

        // Prepare values
        $created_by              = (int)($data['created_by'] ?? 0);
        $department_id           = (int)($data['department_id'] ?? 0);
        $designation_id          = (int)($data['designation_id'] ?? 0);
        $job_title               = $data['job_title'] ?? '';
        $no_of_vacancies         = (int)($data['no_of_vacancies'] ?? 0);
        $job_experience          = $data['job_experience'] ?? '';
        $to_be_filled_before     = !empty($data['to_be_filled_before']) ? $data['to_be_filled_before'] : null;
        $age_limit               = $data['age_limit'] ?? '';
        $report_to               = (int)($data['report_to'] ?? 0);
        $requirement_type        = $data['requirement_type'] ?? '';
        $project_id              = ($data['project_id'] && (int)$data['project_id'] > 0) ? (int)$data['project_id'] : null;
        $reason                  = $data['reason'] ?? '';
        $job_description         = $data['job_description'] ?? '';
        $education_qualification = $data['education_qualification'] ?? '';
        $special_skills          = $data['special_skills'] ?? '';
        $status                  = $data['status'] ?? 'pending';

        $stmt->bind_param(
            "iiisisssisisssss",
            $created_by,
            $department_id,
            $designation_id,
            $job_title,
            $no_of_vacancies,
            $job_experience,
            $to_be_filled_before,
            $age_limit,
            $report_to,
            $requirement_type,
            $project_id,
            $reason,
            $job_description,
            $education_qualification,
            $special_skills,
            $status
        );

        if ($stmt->execute()) {
            $insertId = $this->db->insert_id;
            $stmt->close();
            return $insertId;
        }

        error_log("ManpowerRequisition execute failed: " . $stmt->error);
        return false;
    }
}
