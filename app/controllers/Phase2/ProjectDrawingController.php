<?php
namespace Phase2;

use Controller;
use Con_ProjectDrawing;

require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';

class ProjectDrawingController extends Controller {
    protected $db;
    protected $model;

    public function __construct($db) {
        $this->db    = $db;
        $this->model = new Con_ProjectDrawing($db);

        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $userId = $_SESSION['user']['id']  ?? 0;
        $roleId = $_SESSION['user']['role_id'] ?? 0;

        if (in_array($roleId, [1, 2, 3, 13, 15, 23])) {
            $records = $this->model->getAll();
        } else {
            $stmt = $this->db->prepare("
                SELECT d.*, p.name AS project_name, dt.name AS design_type_name, u.full_name AS internal_designer_name
                FROM project_drawings d
                LEFT JOIN projects p ON d.project_id = p.id
                LEFT JOIN design_types dt ON d.design_type_id = dt.id
                LEFT JOIN users u ON d.user_id = u.id
                WHERE p.draftsman_id = ?
                ORDER BY d.id DESC
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/project_drawings/project-drawings', [
            'current_route' => 'con-project-drawings',
            'records' => $records,
            'page_title'   => 'Project Drawings'
        ]);
    }

    public function create() {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $userId       = $_SESSION['user']['id']  ?? 0;
        $roleId       = $_SESSION['user']['role_id'] ?? 0;

        // For Draftsman role: only show projects where A&E is fully complete
        $projects = $this->getProjectsForDraftsman($userId, $roleId);

        $design_types = $this->db->query("SELECT id, name FROM design_types ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $users        = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        // Projects that are assigned to this draftsman but AE is not yet 'fully' done
        $blockedProjects = $this->getBlockedProjectsForDraftsman($userId, $roleId);

        $this->view('phase2/project_drawings/create-project-drawing', [
            'current_route'   => 'con-project-drawings',
            'page_title'      => 'Add Project Drawing',
            'projects'        => $projects,
            'design_types'    => $design_types,
            'users'           => $users,
            'blockedProjects' => $blockedProjects,
        ]);
    }

    public function store() {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $isInHouse = (int)($_POST['is_in_house'] ?? 1);
        $data = [
            'project_id'           => $_POST['project_id'] ?? 0,
            'design_type_id'       => $_POST['design_type_id'] ?? null,
            'is_in_house'          => $isInHouse,
            'user_id'              => $isInHouse ? $_SESSION['user']['id'] : null, // session if internal
            'designer_name'        => $isInHouse ? null : ($_POST['designer_name'] ?? null),
            'designer_designation' => $isInHouse ? null : ($_POST['designer_designation'] ?? null),
            'designer_company'     => $isInHouse ? null : ($_POST['designer_company'] ?? null),
            'design_link'          => $_POST['design_link'] ?? null,
            'version'              => $_POST['version'] ?? '1.0',
            'status'               => $_POST['status'] ?? 'Draft',
            'created_by'           => $_SESSION['user']['id'],
        ];

        if ($this->model->create($data)) {
            // ── Notify ALL team when drawing is submitted (Struct Engineer gets action-required) ──
            $projectId   = (int)($data['project_id']);
            $projectName = $this->getProjectName($projectId);
            try {
                $wfMail = new \ProjectWorkflowMailService($this->db);
                $wfMail->notifyStructuralEngineerAfterDrawings($projectId, $projectName);
            } catch (\Throwable $e) {
                error_log('[Drawing] Team email error: ' . $e->getMessage());
            }
            setSuccess("Project drawing created successfully.");
        } else {
            setError("Failed to create drawing.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-project-drawings");
        exit;
    }

    public function edit() {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $id     = $_GET['id'] ?? 0;
        $record = $this->model->find($id);

        if (!$record) {
            setError("Record not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-project-drawings");
            exit;
        }

        $projects     = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $design_types = $this->db->query("SELECT id, name FROM design_types ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $users        = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/project_drawings/edit-project-drawing', [
            'current_route' => 'con-project-drawings',
            'page_title'        => 'Edit Project Drawing',
            'record'       => $record,
            'projects'     => $projects,
            'design_types' => $design_types,
            'users'        => $users,
        ]);
    }

    public function update() {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $id   = $_POST['id'] ?? 0;
        $data = [
            'project_id'           => $_POST['project_id'] ?? 0,
            'design_type_id'       => $_POST['design_type_id'] ?? null,
            'is_in_house'          => $_POST['is_in_house'] ?? 1,
            'user_id'              => $_POST['user_id'] ?? null,
            'designer_name'        => $_POST['designer_name'] ?? null,
            'designer_designation' => $_POST['designer_designation'] ?? null,
            'designer_company'     => $_POST['designer_company'] ?? null,
            'design_link'          => $_POST['design_link'] ?? null,
            'version'              => $_POST['version'] ?? '1.0',
            'status'               => $_POST['status'] ?? 'Draft',
        ];

        if ($this->model->update($id, $data)) {
            // ── Batch 4: Notify Structural Engineer when drawing is Approved (update) ──
            if (($data['status'] ?? '') === 'Approved') {
                $projectId   = (int)($data['project_id']);
                $projectName = $this->getProjectName($projectId);
                try {
                    $wfMail = new \ProjectWorkflowMailService($this->db);
                    $wfMail->notifyStructuralEngineerAfterDrawings($projectId, $projectName);
                } catch (\Throwable $e) {
                    error_log('[Drawing] Struct update email error: ' . $e->getMessage());
                }
            }
            setSuccess("Updated successfully.");
        } else {
            setError("Failed to update drawing.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-project-drawings");
        exit;
    }

    public function delete() {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $id = $_POST['id'] ?? 0;
        if ($this->model->delete($id)) {
            setSuccess("Deleted successfully.");
        } else {
            setError("Failed to delete.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-project-drawings");
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
        if (in_array($roleId, [1, 2, 3, 13, 15, 23])) {
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

    /**
     * Returns projects assigned to this draftsman WHERE architect_engineering.status = 'fully'.
     * Admin/QC/PM (roles 1, 2, 13, 15) see all projects that have AE='fully'.
     */
    private function getProjectsForDraftsman(int $userId, int $roleId): array
    {
        if (in_array($roleId, [1, 2, 3, 13, 15, 23])) {
            // Admins/PM/QC see all projects with AE fully done
            return $this->db->query("
                SELECT DISTINCT p.id, p.name
                FROM projects p
                INNER JOIN architect_engineering ae ON ae.project_id = p.id AND ae.status = 'fully'
                WHERE p.deleted_at IS NULL
                ORDER BY p.name ASC
            ")->fetch_all(MYSQLI_ASSOC);
        }
        // Draftsman: only their assigned projects with AE = fully
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.id, p.name
            FROM projects p
            INNER JOIN architect_engineering ae ON ae.project_id = p.id AND ae.status = 'fully'
            WHERE p.draftsman_id = ? AND p.deleted_at IS NULL
            ORDER BY p.name ASC
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Returns projects assigned to this draftsman where AE is NOT yet fully done.
     * Used to show a locked/waiting notice on the create page.
     */
    private function getBlockedProjectsForDraftsman(int $userId, int $roleId): array
    {
        // Admins can create for any project regardless; no blocked state for them
        if (in_array($roleId, [1, 2, 3, 13, 15, 23])) {
            return [];
        }
        $stmt = $this->db->prepare("
            SELECT p.id, p.name,
                   COALESCE(ae.status, 'not_started') AS ae_status
            FROM projects p
            LEFT JOIN architect_engineering ae ON ae.project_id = p.id
            WHERE p.draftsman_id = ?
              AND p.deleted_at IS NULL
              AND (ae.id IS NULL OR ae.status != 'fully')
            ORDER BY p.name ASC
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ─── CSV Import Method ───────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-dashboard');
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-project-drawings');
            exit;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if ($handle === FALSE) { setError('Failed to open CSV file.'); header('Location: ' . $GLOBALS['base_url'] . '/con-project-drawings'); exit; }

        $header = array_map('trim', fgetcsv($handle));
        $header[0] = ltrim($header[0], "\xEF\xBB\xBF"); // Strip UTF-8 BOM
        
        $resolveKey = function($row, $targetKey) {
            $targetLower = strtolower($targetKey);
            foreach ($row as $key => $val) {
                $keyLower = strtolower(trim($key));
                if ($keyLower === $targetLower || strpos($keyLower, $targetLower) === 0) {
                    return $val;
                }
            }
            return '';
        };

        $successCount = 0; $errorCount = 0; $errors = [];
        $db = $this->db;
        $sessionUserId = $_SESSION['user']['id'] ?? 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty(array_filter($data))) continue;
            
            // Pad data array to match header length to prevent array_combine warnings
            $data = array_pad($data, count($header), '');
            $row = array_combine($header, $data);
            
            $projectName   = trim($resolveKey($row, 'Project_Name'));
            $designType    = trim($resolveKey($row, 'Design_Type_Name'));
            $isInHouseRaw  = trim($resolveKey($row, 'Is_In_House'));
            $internalEmail = trim($resolveKey($row, 'Internal_Designer_Email'));
            $designerName  = trim($resolveKey($row, 'Designer_Name'));
            $designerDesig = trim($resolveKey($row, 'Designer_Designation'));
            $designerComp  = trim($resolveKey($row, 'Designer_Company'));
            $designLink    = trim($resolveKey($row, 'Design_Link'));
            $version       = trim($resolveKey($row, 'Version'));
            $status        = trim($resolveKey($row, 'Status'));

            if (empty($projectName) || empty($designType)) { $errorCount++; $errors[] = "Missing Project Name or Design Type"; continue; }

            // Validate Project ID
            $stmt = $db->prepare('SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1');
            $stmt->bind_param('s', $projectName); $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) { $errorCount++; $errors[] = "Project '$projectName' not found"; continue; }
            $projectId = $res->fetch_assoc()['id'];

            // Validate Design Type ID
            $stmtD = $db->prepare('SELECT id FROM design_types WHERE name = ? LIMIT 1');
            $stmtD->bind_param('s', $designType); $stmtD->execute();
            $resD = $stmtD->get_result();
            if ($resD->num_rows === 0) { $errorCount++; $errors[] = "Design Type '$designType' not found"; continue; }
            $designTypeId = $resD->fetch_assoc()['id'];

            // Handle Is In House Logic
            $isInHouse = ($isInHouseRaw === '0' || strtolower($isInHouseRaw) === 'false' || strtolower($isInHouseRaw) === 'no') ? 0 : 1;
            
            $assignedUserId = null;
            $finalDesignerName = null;
            $finalDesignerDesig = null;
            $finalDesignerComp = null;

            if ($isInHouse == 1) {
                // Internal Designer: Find user by email
                if (!empty($internalEmail)) {
                    $stmtU = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                    $stmtU->bind_param('s', $internalEmail);
                    $stmtU->execute();
                    $resU = $stmtU->get_result();
                    if ($resU->num_rows > 0) {
                        $assignedUserId = $resU->fetch_assoc()['id'];
                    } else {
                        $errorCount++; $errors[] = "Internal Designer email '$internalEmail' not found"; continue;
                    }
                } else {
                    $assignedUserId = $sessionUserId; // Fallback to uploader if not provided
                }
            } else {
                // External Designer
                $finalDesignerName = $designerName;
                $finalDesignerDesig = $designerDesig;
                $finalDesignerComp = $designerComp;
            }

            $insertData = [
                'project_id'           => $projectId,
                'design_type_id'       => $designTypeId,
                'is_in_house'          => $isInHouse,
                'user_id'              => $assignedUserId,
                'designer_name'        => $finalDesignerName,
                'designer_designation' => $finalDesignerDesig,
                'designer_company'     => $finalDesignerComp,
                'design_link'          => !empty($designLink) ? $designLink : null,
                'version'              => !empty($version) ? $version : '1.0',
                'status'               => !empty($status) ? $status : 'Draft',
                'created_by'           => $sessionUserId
            ];

            if ($this->model->create($insertData)) { 
                $successCount++; 
            } else { 
                $errorCount++; $errors[] = "Failed to save data for Project '$projectName'"; 
            }
        }
        fclose($handle);
        
        if ($errorCount > 0) { 
            setError("Imported $successCount. Failed $errorCount. Errors: " . implode(' | ', array_slice($errors, 0, 5))); 
        } else { 
            setSuccess("Successfully imported $successCount records."); 
        }
        
        header('Location: ' . $GLOBALS['base_url'] . '/con-project-drawings');
        exit;
    }

}
