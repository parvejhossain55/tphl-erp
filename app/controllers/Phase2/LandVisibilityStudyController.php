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
        $isPmOrAdmin = in_array($roleId, [1, 2, 3, 33, 13, 15, 22]);

        if ($isPmOrAdmin) {
            $studies = $this->model->getAll();
        } else {
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
        if (!in_array((int)($_SESSION['user']['role_id'] ?? 0), [14])) {
            setError('Only Land Officers can add Land Visibility Studies.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
            exit;
        }

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
        if (!in_array((int)($_SESSION['user']['role_id'] ?? 0), [14])) {
            setError('Only Land Officers can add Land Visibility Studies.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
            exit;
        }

        $data = [
            'project_id' => $_POST['project_id'] ?? 0,
            'land_legal_doc_status' => $_POST['land_legal_doc_status'] ?? 'Pending',
            'digital_survey_report_link' => $_POST['digital_survey_report_link'] ?? null,
            'govt_approval_doc_link' => $_POST['govt_approval_doc_link'] ?? null,
            'others_doc_link' => $_POST['others_doc_link'] ?? null,
            'created_by' => $_SESSION['user']['id']
        ];
        
        if ($this->model->create($data)) {
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
        if (!in_array((int)($_SESSION['user']['role_id'] ?? 0), [14])) {
            setError('Only Land Officers can edit Land Visibility Studies.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
            exit;
        }

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
        if (!in_array((int)($_SESSION['user']['role_id'] ?? 0), [14])) {
            setError('Only Land Officers can edit Land Visibility Studies.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
            exit;
        }

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
        if (in_array($roleId, [1, 2, 3, 33, 13, 15, 22])) {
            return $this->db->query(
                "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC"
            )->fetch_all(MYSQLI_ASSOC);
        }

        $stmt = $this->db->prepare(
            "SELECT id, name FROM projects 
             WHERE deleted_at IS NULL 
               AND (pm_id = ? OR site_engineer_id = ? OR landman_id = ? OR struct_engineer_id = ? 
                    OR operation_head_id = ? OR draftsman_id = ? OR architect_id = ? 
                    OR estimator_id = ? OR qc_id = ? OR operator_id = ? OR created_by = ?)
             ORDER BY name ASC"
        );
        $stmt->bind_param('iiiiiiiiiii', $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId);
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

    public function delete() {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        if (!$id) {
            setError('Invalid record ID.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
            exit;
        }

        if ($this->model->delete($id)) {
            setSuccess('Record deleted successfully.');
        } else {
            setError('Failed to delete record.');
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
        exit;
    }

    // ─── CSV Import Method ───────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
            exit;
        }

        $file   = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");

        if ($handle === FALSE) {
            setError('Failed to open CSV file.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
            exit;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            setError('CSV file is empty.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
            exit;
        }

        $header       = array_map('trim', $header);
        $header[0]    = ltrim($header[0], "\xEF\xBB\xBF"); // Strip UTF-8 BOM (Excel CSV)
        $successCount = 0;
        $errorCount   = 0;
        $errors       = [];
        $db           = $this->db;
        $userId       = $_SESSION['user']['id'] ?? 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty(array_filter($data))) continue;

            $row         = array_combine($header, $data);
            $projectName = trim($row['Project_Name'] ?? '');
            $status      = trim($row['Land_Legal_Doc_Status'] ?? 'Pending');
            $surveyLink  = trim($row['Digital_Survey_Report_Link'] ?? '');
            $govtLink    = trim($row['Govt_Approval_Doc_Link'] ?? '');
            $othersLink  = trim($row['Others_Doc_Link'] ?? '');

            if (empty($projectName)) {
                $errorCount++;
                $errors[] = "Row missing Project_Name.";
                continue;
            }

            $stmt = $db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->bind_param('s', $projectName);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) {
                $errorCount++;
                $errors[] = "Project '$projectName' not found.";
                continue;
            }
            $projectId = $res->fetch_assoc()['id'];

            $insertData = [
                'project_id'                 => $projectId,
                'land_legal_doc_status'      => $status,
                'digital_survey_report_link' => $surveyLink,
                'govt_approval_doc_link'     => $govtLink,
                'others_doc_link'            => $othersLink,
                'created_by'                 => $userId
            ];

            if ($this->model->create($insertData)) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Failed to save data for Project '$projectName'.";
            }
        }

        fclose($handle);

        if ($errorCount > 0) {
            $errStr = implode(" | ", array_slice($errors, 0, 5));
            setError("Imported $successCount records. Failed $errorCount. Errors: $errStr");
        } else {
            setSuccess("Successfully imported $successCount records.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-land-visibility-studies");
        exit;
    }
}
