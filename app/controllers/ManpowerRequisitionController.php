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
    }

    public function index()
    {
        $requisitions = $this->db->query("
            SELECT mr.*, d.name AS department_name, u.full_name AS requested_by_name
            FROM manpower_requisitions mr
            LEFT JOIN departments d ON mr.department_id = d.id
            LEFT JOIN users u ON mr.created_by = u.id
            ORDER BY mr.created_at DESC
        ")->fetch_all(MYSQLI_ASSOC);

        return $this->view('manpower-requisitions/requisition-lists', [
            'current_route' => 'requisition-lists',
            'page_title' => 'Manpower Requisition Lists',
            'requisitions' => $requisitions
        ]);
    }

    public function create()
    {
        $projects = $this->projectModel->getAll();
        $users = $this->userModel->getAll();
        $designations = $this->designationModel->getAll();

        return $this->view('manpower-requisitions/create-requisition', [
            'current_route' => 'create-requisition',
            'page_title' => 'Create Manpower Requisition',
            'projects' => $projects,
            'users' => $users,
            'designations' => $designations
        ]);
    }

    public function store()
    {
        // 1. Get Department ID and Designation ID from session (as clarified by user)
        $deptId = $_SESSION['user']['department_id'] ?? 0;
        $designationId = $_SESSION['user']['designation_id'] ?? 0;
        $deptName = $_SESSION['user']['department_name'] ?? 'General';

        // 2. Prepare Job Description (combine array into string)
        $jdRows = $_POST['job_description'] ?? [];
        $jdFiltered = array_filter($jdRows, fn($row) => !empty(trim($row)));
        $jdString = implode("\n", $jdFiltered);

        // 3. Prepare Requirement Type (combine checkboxes)
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
                    'job_title' => $data['job_title'],
                    'no_of_vacancies' => $data['no_of_vacancies'],
                    'project_id' => $data['project_id']
                ]),
                'request_amount'  => 0
            ]);

            $_SESSION['success'] = "Manpower Requisition created successfully and sent for approval.";
        } else {
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
                   u.full_name AS requested_by_name
            FROM manpower_requisitions mr
            LEFT JOIN departments d ON mr.department_id = d.id
            LEFT JOIN designations des ON mr.designation_id = des.id
            LEFT JOIN projects p ON mr.project_id = p.id
            LEFT JOIN users u ON mr.created_by = u.id
            WHERE mr.id = " . (int)$id
        )->fetch_assoc();

        if (!$requisition) {
            setError("Manpower Requisition not found.");
            header("Location: {$GLOBALS['base_url']}/requisition-lists");
            exit;
        }
        return $this->view('manpower-requisitions/view-requisition', [
            'requisition' => $requisition
        ]);
    }

}
