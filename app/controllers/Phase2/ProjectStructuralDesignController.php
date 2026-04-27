<?php
namespace Phase2;

use Controller;
use ProjectStructuralDesign;

require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';

class ProjectStructuralDesignController extends Controller {
    protected $db;
    protected $model;

    public function __construct($db) {
        $this->db    = $db;
        $this->model = new ProjectStructuralDesign($db);

        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $userId = $_SESSION['user']['id']  ?? 0;
        $roleId = $_SESSION['user']['role_id'] ?? 0;

        if (in_array($roleId, [1, 2, 13, 15])) {
            $records = $this->model->getAll();
        } else {
            $stmt = $this->db->prepare("
                SELECT sd.*, p.name AS project_name
                FROM project_structural_designs sd
                LEFT JOIN projects p ON sd.project_id = p.id
                WHERE p.struct_engineer_id = ?
                ORDER BY sd.id DESC
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/structure_design/structure-design', [
            'current_route' => 'structure-design',
            'records' => $records,
            'page_title'   => 'Project Structure Design'
        ]);
    }

    public function create() {
        $userId   = $_SESSION['user']['id']  ?? 0;
        $roleId   = $_SESSION['user']['role_id'] ?? 0;
        $projects = $this->getMyAssignedProjects($userId, $roleId, 'struct_engineer_id');
        $users    = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/structure_design/create-structure-design', [
            'current_route' => 'structure-design',
            'page_title'    => 'Add Structure Design',
            'projects'      => $projects,
            'users'         => $users,
        ]);
    }

    public function store() {
        $designer_type = $_POST['designer_type'] ?? 'internal';

        if ($designer_type === 'internal') {
            // Auto: get name from session user
            $userId = $_SESSION['user']['id'];
            $uRow   = $this->db->query("SELECT full_name FROM users WHERE id = {$userId} LIMIT 1")->fetch_assoc();
            $designer_name    = $uRow['full_name'] ?? '';
            $designer_phone   = null;
            $designer_address = null;
            $company_name     = null;
        } else {
            $userId           = null;
            $designer_name    = $_POST['designer_name'] ?? '';
            $designer_phone   = $_POST['designer_phone'] ?? null;
            $designer_address = $_POST['designer_address'] ?? null;
            $company_name     = $_POST['company_name'] ?? null;
        }

        $data = [
            'project_id'       => $_POST['project_id'] ?? 0,
            'designer_type'    => $designer_type,
            'designer_name'    => $designer_name,
            'designer_phone'   => $designer_phone,
            'designer_address' => $designer_address,
            'design_link'      => $_POST['design_link'] ?? '',
            'company_name'     => $company_name,
            'created_by'       => $_SESSION['user']['id'],
        ];

        if ($this->model->create($data)) {
            // ── Batch 5: Notify Site Engineer to proceed with Soil Test ──
            $projectId   = (int)($data['project_id']);
            $projectName = $this->getProjectName($projectId);
            try {
                $wfMail = new \ProjectWorkflowMailService($this->db);
                $wfMail->notifySiteEngineerAfterStructuralDesign($projectId, $projectName);
            } catch (\Throwable $e) {
                error_log('[StructDesign] SiteEng email error: ' . $e->getMessage());
            }
            setSuccess("Project structure design created successfully.");
        } else {
            setError("Failed to create structure design.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/structure-design");
        exit;
    }

    public function edit() {
        $id     = $_GET['id'] ?? 0;
        $record = $this->model->find($id);

        if (!$record) {
            setError("Record not found.");
            header("Location: " . $GLOBALS['base_url'] . "/structure-design");
            exit;
        }

        $projects     = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $users        = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/structure_design/edit-structure-design', [
            'current_route' => 'structure-design',
            'page_title'    => 'Edit Structure Design',
            'record'        => $record,
            'projects'      => $projects,
            'users'         => $users,
        ]);
    }

    public function update() {
        $id   = $_POST['id'] ?? 0;
        
        $designer_type = $_POST['designer_type'] ?? 'internal';
        
        $designer_name = '';
        if ($designer_type === 'internal') {
            // Get the user's full name based on the selected user_id
            $user_id = (int)($_POST['internal_designer_user_id'] ?? 0);
            if ($user_id > 0) {
                $user = $this->db->query("SELECT full_name FROM users WHERE id = $user_id")->fetch_assoc();
                $designer_name = $user['full_name'] ?? '';
            } else {
                // Keep existing name if user unselected or not changed
                $record = $this->model->find($id);
                $designer_name = $record['designer_name'] ?? '';
            }
        } else {
            $designer_name = $_POST['designer_name'] ?? '';
        }

        $data = [
            'project_id'       => $_POST['project_id'] ?? 0,
            'designer_type'    => $designer_type,
            'designer_name'    => $designer_name,
            'designer_phone'   => $_POST['designer_phone'] ?? null,
            'designer_address' => $_POST['designer_address'] ?? null,
            'company_name' => $_POST['company_name'] ?? null,
            'design_link'      => $_POST['design_link'] ?? '',
        ];

        if ($this->model->update($id, $data)) {
            setSuccess("Updated successfully.");
        } else {
            setError("Failed to update structure design.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/structure-design");
        exit;
    }

    public function delete() {
        $id = $_POST['id'] ?? 0;
        if ($this->model->delete($id)) {
            setSuccess("Deleted successfully.");
        } else {
            setError("Failed to delete.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/structure-design");
        exit;
    }

    private function getProjectName(int $id): string
    {
        if ($id <= 0) return 'N/A';
        $row = $this->db->query("SELECT name FROM projects WHERE id = {$id} LIMIT 1")->fetch_assoc();
        return $row['name'] ?? 'N/A';
    }

    private function getMyAssignedProjects(int $userId, int $roleId, string $roleCol): array
    {
        if (in_array($roleId, [1, 2, 13, 15])) {
            return $this->db->query(
                "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC"
            )->fetch_all(MYSQLI_ASSOC);
        }
        $stmt = $this->db->prepare(
            "SELECT id, name FROM projects WHERE {$roleCol} = ? AND deleted_at IS NULL ORDER BY name ASC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
