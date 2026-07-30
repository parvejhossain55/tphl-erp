<?php

class ManpowerRequisitionController extends Controller
{
    protected $db;
    protected $requisitionModel;
    protected $projectModel;
    protected $userModel;
    protected $designationModel;
    protected $departmentModel;
    protected $approvalModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->requisitionModel = new ManpowerRequisition($this->db);
        $this->projectModel = new Project($this->db);
        $this->userModel = new User($this->db);
        $this->designationModel = new Designation($this->db);
        $this->departmentModel = new Department($this->db);
        $this->approvalModel = new ApprovalRequest($this->db);

        $this->ensureHrColumnsExist();
    }

    private function ensureHrColumnsExist()
    {
        if (!isset($this->db)) return;
        $checkCol = $this->db->query("SHOW COLUMNS FROM manpower_requisitions LIKE 'hr_status'");
        if ($checkCol && $checkCol->num_rows == 0) {
            $this->db->query("ALTER TABLE manpower_requisitions 
                ADD COLUMN hr_status VARCHAR(50) DEFAULT 'pending', 
                ADD COLUMN hr_remark TEXT NULL, 
                ADD COLUMN hr_updated_by INT NULL, 
                ADD COLUMN hr_updated_at DATETIME NULL;");
        }
    }

    public function index()
    {
        $statusFilter = $_GET['status'] ?? 'all';
        $hrFilter     = $_GET['hr_status'] ?? 'all';
        $whereConditions = [];

        if ($statusFilter === 'pending') {
            $whereConditions[] = "mr.status = 'pending'";
        } elseif ($statusFilter === 'approved') {
            $whereConditions[] = "mr.status = 'approved'";
        } elseif ($statusFilter === 'rejected') {
            $whereConditions[] = "mr.status = 'rejected'";
        }

        if ($hrFilter === 'on_processing') {
            $whereConditions[] = "(mr.hr_status = 'on_processing' OR mr.hr_status = 'pending' OR mr.hr_status IS NULL)";
        } elseif ($hrFilter === 'hiring_completed') {
            $whereConditions[] = "mr.hr_status = 'hiring_completed'";
        } elseif ($hrFilter === 'not_needed_now') {
            $whereConditions[] = "mr.hr_status = 'not_needed_now'";
        }

        $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

        $requisitions = $this->db->query("
            SELECT mr.*, 
                   d.name AS department_name, 
                   u.full_name AS requested_by_name,
                   u_mgr.full_name AS report_to_name,
                   u_hr.full_name AS hr_updated_by_name
            FROM manpower_requisitions mr
            LEFT JOIN departments d ON mr.department_id = d.id
            LEFT JOIN users u ON mr.created_by = u.id
            LEFT JOIN users u_mgr ON mr.report_to = u_mgr.id
            LEFT JOIN users u_hr ON mr.hr_updated_by = u_hr.id
            {$whereClause}
            ORDER BY mr.created_at DESC
        ")->fetch_all(MYSQLI_ASSOC);

        // Fetch counts for metric cards
        $countsRes = $this->db->query("
            SELECT 
                COUNT(*) AS total_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
                SUM(CASE WHEN status = 'approved' AND (hr_status = 'on_processing' OR hr_status = 'pending' OR hr_status IS NULL) THEN 1 ELSE 0 END) AS hr_processing_count,
                SUM(CASE WHEN status = 'approved' AND hr_status = 'hiring_completed' THEN 1 ELSE 0 END) AS hr_completed_count,
                SUM(CASE WHEN status = 'approved' AND hr_status = 'not_needed_now' THEN 1 ELSE 0 END) AS hr_not_needed_count
            FROM manpower_requisitions
        ")->fetch_assoc();

        // If direct jump parameter is passed and there's 1 pending item, redirect directly to that view
        if (isset($_GET['open_pending']) && !empty($requisitions) && count($requisitions) === 1 && $statusFilter === 'pending') {
            header("Location: {$GLOBALS['base_url']}/view-requisition/" . $requisitions[0]['id']);
            exit;
        }

        return $this->view('manpower-requisitions/requisition-lists', [
            'current_route' => 'requisition-lists',
            'page_title'    => 'Manpower Requisition Lists',
            'requisitions'  => $requisitions,
            'statusFilter'  => $statusFilter,
            'hrFilter'      => $hrFilter,
            'counts'        => $countsRes
        ]);
    }

    public function create()
    {
        $projects = $this->projectModel->getAll();
        $users = $this->userModel->getAll();
        $designations = $this->designationModel->getAll();
        
        $employees = $this->db->query("
            SELECT e.id, e.full_name, e.employee_id, d.name AS designation_name
            FROM employees e
            LEFT JOIN designations d ON e.designation_id = d.id
            ORDER BY e.full_name ASC
        ")->fetch_all(MYSQLI_ASSOC);

        return $this->view('manpower-requisitions/create-requisition', [
            'current_route' => 'create-requisition',
            'page_title'    => 'Create Manpower Requisition',
            'projects'      => $projects,
            'users'         => $users,
            'designations'  => $designations,
            'employees'     => $employees
        ]);
    }

    public function store()
    {
        // 1. Get Department ID and Designation ID from session
        $deptId = $_SESSION['user']['department_id'] ?? 0;
        $designationId = $_SESSION['user']['designation_id'] ?? 0;
        $deptName = $_SESSION['user']['department_name'] ?? 'General';

        // 2. Prepare Job Description
        $jdRows = $_POST['job_description'] ?? [];
        $jdFiltered = array_filter($jdRows, fn($row) => !empty(trim($row)));
        $jdString = implode("\n", $jdFiltered);

        // 3. Prepare Requirement Type
        $reqTypes = [];
        if (!empty($_POST['req_project'])) $reqTypes[] = 'Project Based';
        if (!empty($_POST['req_replacement'])) $reqTypes[] = 'Replacement';
        if (!empty($_POST['req_new'])) $reqTypes[] = 'New Hiring';
        $reqTypeString = implode(", ", $reqTypes);

        // 4. Prepare data for Requisition model
        $data = [
            'created_by'              => $_SESSION['user']['id'],
            'department_id'           => $deptId,
            'designation_id'          => $designationId,
            'job_title'               => $_POST['job_title'] ?? '',
            'no_of_vacancies'         => $_POST['no_of_vacancies'] ?? 0,
            'job_experience'          => $_POST['job_experience'] ?? '',
            'to_be_filled_before'     => $_POST['to_be_filled_before'] ?? null,
            'age_limit'               => $_POST['age_limit'] ?? '',
            'report_to'               => $_POST['report_to'] ?? 0,
            'requirement_type'        => $reqTypeString,
            'project_id'              => $_POST['project_id'] ?? null,
            'replacement_for_id'      => $_POST['replacement_for_id'] ?? null,
            'reason'                  => $_POST['reason'] ?? '',
            'job_description'         => $jdString,
            'education_qualification' => $_POST['education_qualification'] ?? '',
            'special_skills'          => $_POST['special_skills'] ?? '',
            'status'                  => 'pending'
        ];

        $requisitionId = $this->requisitionModel->create($data);

        if ($requisitionId) {
            // 5. Create Approval Request
            $this->approvalModel->create([
                'department'      => $deptName,
                'request_type'    => 'manpower_requisition',
                'title'           => 'Manpower Requisition for ' . $data['job_title'],
                'source_table'    => 'manpower_requisitions',
                'source_id'       => $requisitionId,
                'requested_days'  => 0,
                'requested_by'    => $_SESSION['user']['id'],
                'approve_role'    => 'management', 
                'meta_data'       => json_encode([
                    'job_title'       => $data['job_title'],
                    'no_of_vacancies' => $data['no_of_vacancies'],
                    'project_id'      => $data['project_id']
                ]),
                'request_amount'  => 0
            ]);

            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['success'] = "Manpower Requisition created successfully and sent for Management approval.";
        } else {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error'] = "Failed to create Manpower Requisition.";
        }

        header("Location: {$GLOBALS['base_url']}/requisition-lists");
        exit;
    }

    public function find($id)
    {
        $requisition = $this->db->query("
            SELECT mr.*, 
                   d.name AS department_name, 
                   des.name AS designation_name,
                   p.name AS project_name,
                   u.full_name AS requested_by_name,
                   u_mgr.full_name AS report_to_name,
                   des_mgr.name AS report_to_designation,
                   e.full_name AS replacement_for_name,
                   ar.id AS approval_request_id,
                   ar.approved_by,
                   ar.approval_note,
                   ar.approved_at,
                   u_app.full_name AS approved_by_name,
                   u_hr.full_name AS hr_updated_by_name
            FROM manpower_requisitions mr
            LEFT JOIN departments d ON mr.department_id = d.id
            LEFT JOIN designations des ON mr.designation_id = des.id
            LEFT JOIN projects p ON mr.project_id = p.id
            LEFT JOIN users u ON mr.created_by = u.id
            LEFT JOIN users u_mgr ON mr.report_to = u_mgr.id
            LEFT JOIN designations des_mgr ON u_mgr.designation_id = des_mgr.id
            LEFT JOIN employees e ON mr.replacement_for_id = e.id
            LEFT JOIN approval_requests ar ON (ar.source_table = 'manpower_requisitions' AND ar.source_id = mr.id)
            LEFT JOIN users u_app ON ar.approved_by = u_app.id
            LEFT JOIN users u_hr ON mr.hr_updated_by = u_hr.id
            WHERE mr.id = " . (int)$id
        )->fetch_assoc();

        if (!$requisition) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error'] = "Manpower Requisition not found.";
            header("Location: {$GLOBALS['base_url']}/requisition-lists");
            exit;
        }

        return $this->view('manpower-requisitions/view-requisition', [
            'requisition' => $requisition
        ]);
    }

    public function approve($id)
    {
        $id = (int)$id;
        $isManagement = in_array($_SESSION['user']['role_id'] ?? 0, [1, 3]) || hasPermission("Manpower Requisition");
        if (!$isManagement) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error'] = "You do not have permission to approve requisitions.";
            header("Location: {$GLOBALS['base_url']}/view-requisition/{$id}");
            exit;
        }

        $userId = $_SESSION['user']['id'] ?? null;
        $note = $_POST['approval_note'] ?? 'Approved by Management';

        // 1. Update manpower_requisitions
        $stmt = $this->db->prepare("UPDATE manpower_requisitions SET status = 'approved', hr_status = 'on_processing', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // 2. Update approval_requests table
        $stmt2 = $this->db->prepare("UPDATE approval_requests SET status = 'approved', approved_by = ?, approval_note = ?, approved_at = NOW() WHERE source_table = 'manpower_requisitions' AND source_id = ?");
        $stmt2->bind_param("isi", $userId, $note, $id);
        $stmt2->execute();

        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['success'] = "Manpower Requisition #{$id} has been approved successfully and forwarded to HR for recruitment.";

        header("Location: {$GLOBALS['base_url']}/view-requisition/{$id}");
        exit;
    }

    public function reject($id)
    {
        $id = (int)$id;
        $isManagement = in_array($_SESSION['user']['role_id'] ?? 0, [1, 3]) || hasPermission("Manpower Requisition");
        if (!$isManagement) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error'] = "You do not have permission to reject requisitions.";
            header("Location: {$GLOBALS['base_url']}/view-requisition/{$id}");
            exit;
        }

        $userId = $_SESSION['user']['id'] ?? null;
        $reason = $_POST['reject_reason'] ?? 'Rejected by Management';

        // 1. Update manpower_requisitions
        $stmt = $this->db->prepare("UPDATE manpower_requisitions SET status = 'rejected', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // 2. Update approval_requests table
        $stmt2 = $this->db->prepare("UPDATE approval_requests SET status = 'rejected', approved_by = ?, approval_note = ?, approved_at = NOW() WHERE source_table = 'manpower_requisitions' AND source_id = ?");
        $stmt2->bind_param("isi", $userId, $reason, $id);
        $stmt2->execute();

        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['success'] = "Manpower Requisition #{$id} has been rejected.";

        header("Location: {$GLOBALS['base_url']}/view-requisition/{$id}");
        exit;
    }

    public function updateHrStatus($id)
    {
        $id = (int)$id;
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');

        $isHr = in_array($roleId, [1, 3, 4]) 
                || hasPermission("HRM Management") 
                || hasPermission("Hr Manage")
                || str_contains($roleName, 'hr');

        if (!$isHr) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error'] = "Only HR Team or Management can update recruitment status.";
            header("Location: {$GLOBALS['base_url']}/view-requisition/{$id}");
            exit;
        }

        $hrStatus = $_POST['hr_status'] ?? 'on_processing';
        $hrRemark = $_POST['hr_remark'] ?? '';
        $userId   = $_SESSION['user']['id'] ?? null;

        $stmt = $this->db->prepare("
            UPDATE manpower_requisitions 
            SET hr_status = ?, 
                hr_remark = ?, 
                hr_updated_by = ?, 
                hr_updated_at = NOW(), 
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("ssii", $hrStatus, $hrRemark, $userId, $id);
        $stmt->execute();

        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['success'] = "HR Recruitment Status updated successfully.";

        header("Location: {$GLOBALS['base_url']}/view-requisition/{$id}");
        exit;
    }
}
