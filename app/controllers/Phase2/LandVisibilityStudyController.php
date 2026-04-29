<?php
namespace Phase2;

use Controller;
use Con_LandVisibilityStudy;
use Project;

require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';

class LandVisibilityStudyController extends Controller {
    protected $db;
    protected $model;
    protected $projectModel;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new Con_LandVisibilityStudy($db);
        $this->projectModel = new Project($db);
        
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $userId  = $_SESSION['user']['id']  ?? 0;
        $roleId  = $_SESSION['user']['role_id'] ?? 0;
        $isPmOrAdmin = in_array($roleId, [1, 2, 15, 13]); // PM, Admin, QC

        if ($isPmOrAdmin) {
            $studies = $this->model->getAll();
        } else {
            // Only show studies for projects where landman_id = this user
            $stmt = $this->db->prepare("
                SELECT lvs.*, p.name AS project_name
                FROM land_visibility_study lvs
                LEFT JOIN projects p ON lvs.project_id = p.id
                WHERE p.landman_id = ?
                ORDER BY lvs.id DESC
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $studies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/land_visibility_study/land-visibility-study', [
            'current_route' => 'con-land-visibility-studies',
            'studies' => $studies,
            'page_title' => 'Land Visibility Study'
        ]);
    }

    public function create() {
        $userId  = $_SESSION['user']['id']  ?? 0;
        $roleId  = $_SESSION['user']['role_id'] ?? 0;
        $projects = $this->getMyAssignedProjects($userId, $roleId, 'landman_id');

        $this->view('phase2/land_visibility_study/create-land-visibility-study', [
            'current_route' => 'con-land-visibility-studies',
            'page_title'    => 'Add Land Visibility Study',
            'projects' => $projects
        ]);
    }

    public function store() {
        $data = [
            'project_id' => $_POST['project_id'] ?? 0,
            'land_legal_doc_status' => $_POST['land_legal_doc_status'] ?? 'Pending',
            'digital_survey_report_link' => $_POST['digital_survey_report_link'] ?? null,
            'govt_approval_doc_link' => $_POST['govt_approval_doc_link'] ?? null,
            'others_doc_link' => $_POST['others_doc_link'] ?? null,
            'created_by' => $_SESSION['user']['id']
        ];
        
        if ($this->model->create($data)) {
            // ── Batch 2: Notify Architect to proceed with A&E ──
            $projectId   = (int)($data['project_id']);
            $projectName = $this->getProjectName($projectId);
            try {
                $wfMail = new \ProjectWorkflowMailService($this->db);
                $wfMail->notifyArchitectAfterLandStudy($projectId, $projectName);
            } catch (\Throwable $e) {
                error_log('[LandVisibility] Architect email error: ' . $e->getMessage());
            }
            setSuccess("Created Successfully");
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
            exit;
        } else {
            setError("Failed to create record");
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies/create");
            exit;
        }
    }

    public function edit() {
        $id = $_GET['id'] ?? 0;
        $study = $this->model->find($id);

        if (!$study) {
            setError("Record not found");
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
            exit;
        }

        $projectsQuery = "SELECT id, name FROM projects ORDER BY name ASC";
        $projects = $this->db->query($projectsQuery)->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/land_visibility_study/edit-land-visibility-study', [
            'current_route' => 'con-land-visibility-studies',
            'page_title'    => 'Edit Land Visibility Study',
            'study'    => $study,
            'projects' => $projects
        ]);
    }

    public function update() {
        $id = $_POST['id'] ?? 0;
        $data = [
            'project_id' => $_POST['project_id'] ?? 0,
            'land_legal_doc_status' => $_POST['land_legal_doc_status'] ?? 'Pending',
            'digital_survey_report_link' => $_POST['digital_survey_report_link'] ?? null,
            'govt_approval_doc_link' => $_POST['govt_approval_doc_link'] ?? null,
            'others_doc_link' => $_POST['others_doc_link'] ?? null
        ];

        if ($this->model->update($id, $data)) {
            setSuccess("Updated Successfully");
        } else {
            setError("Failed to update record");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
        exit;
    }

    // ─── Helper: get projects assigned to the current user ───────────
    private function getMyAssignedProjects(int $userId, int $roleId, string $roleCol): array
    {
        // PM (15), Admin (1,2), QC (13) see ALL projects
        if (in_array($roleId, [1, 2, 13, 15])) {
            return $this->db->query(
                "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC"
            )->fetch_all(MYSQLI_ASSOC);
        }

        $stmt = $this->db->prepare(
            "SELECT id, name FROM projects
             WHERE {$roleCol} = ? AND deleted_at IS NULL
             ORDER BY name ASC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function getProjectName(int $projectId): string
    {
        $stmt = $this->db->prepare("SELECT name FROM projects WHERE id = ?");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $row['name'] : 'Unknown Project';
    }
}
