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

        if (in_array($roleId, [1, 2, 3, 33, 13, 15, 22])) {
            $records = $this->model->getAll();
        } elseif ($roleId == 12) {
            $stmt = $this->db->prepare("
                SELECT sd.*, p.name AS project_name
                FROM project_structural_designs sd
                LEFT JOIN projects p ON sd.project_id = p.id
                WHERE p.site_engineer_id = ? AND p.deleted_at IS NULL
                ORDER BY sd.id DESC
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $stmt = $this->db->prepare("
                SELECT sd.*, p.name AS project_name
                FROM project_structural_designs sd
                LEFT JOIN projects p ON sd.project_id = p.id
                WHERE p.struct_engineer_id = ? AND p.deleted_at IS NULL
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
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 21) {
            setError('Only Structural Engineers can add Structure Designs.');
            header("Location: " . $GLOBALS['base_url'] . "/structure-design");
            exit;
        }

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
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 21) {
            setError('Only Structural Engineers can add Structure Designs.');
            header("Location: " . $GLOBALS['base_url'] . "/structure-design");
            exit;
        }

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
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 21) {
            setError('Only Structural Engineers can edit Structure Designs.');
            header("Location: " . $GLOBALS['base_url'] . "/structure-design");
            exit;
        }

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
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 21) {
            setError('Only Structural Engineers can edit Structure Designs.');
            header("Location: " . $GLOBALS['base_url'] . "/structure-design");
            exit;
        }

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
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 21) {
            setError('Only Structural Engineers can delete Structure Designs.');
            header("Location: " . $GLOBALS['base_url'] . "/structure-design");
            exit;
        }

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
        if (in_array($roleId, [1, 2, 3, 33, 13, 15, 20, 22])) {
            return $this->db->query(
                "SELECT id, name FROM projects WHERE deleted_at IS NULL AND under_construction = 1 ORDER BY name ASC"
            )->fetch_all(MYSQLI_ASSOC);
        }
        $stmt = $this->db->prepare(
            "SELECT id, name FROM projects 
             WHERE deleted_at IS NULL AND under_construction = 1
               AND (pm_id = ? OR site_engineer_id = ? OR landman_id = ? OR struct_engineer_id = ? 
                    OR operation_head_id = ? OR draftsman_id = ? OR architect_id = ? 
                    OR estimator_id = ? OR qc_id = ? OR operator_id = ? OR created_by = ?)
             ORDER BY name ASC"
        );
        $stmt->bind_param('iiiiiiiiiii', $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ─── CSV Import Method ────────────────────────────────────────────────
    public function importCsv() {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 21) {
            setError('Only Structural Engineers can import Structure Designs.');
            header("Location: " . $GLOBALS['base_url'] . "/structure-design");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/structure-design');
            exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/structure-design');
            exit;
        }
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) { setError('Cannot open file.'); header('Location: ' . $GLOBALS['base_url'] . '/structure-design'); exit; }
        
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
            $designerTypeRaw  = trim($resolveKey($row, 'Designer_Type'));
            $internalEmail = trim($resolveKey($row, 'Internal_Designer_Email'));
            $designerName  = trim($resolveKey($row, 'Designer_Name'));
            $designerPhone = trim($resolveKey($row, 'Designer_Phone'));
            $designerAddress = trim($resolveKey($row, 'Designer_Address'));
            $companyName   = trim($resolveKey($row, 'Company_Name'));
            $designLink    = trim($resolveKey($row, 'Design_Link'));

            if (empty($projectName)) { $errorCount++; $errors[] = "Missing Project_Name"; continue; }
            if (empty($designerTypeRaw)) { $errorCount++; $errors[] = "Missing Designer_Type for '$projectName'"; continue; }
            
            $stmt = $db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->bind_param("s", $projectName); $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) { $errorCount++; $errors[] = "Project '$projectName' not found"; continue; }
            $projectId = $res->fetch_assoc()['id'];
            
            // Handle Designer Type Logic
            $designerType = (strtolower($designerTypeRaw) === 'external') ? 'external' : 'internal';
            
            $finalDesignerName = '';
            $finalDesignerPhone = null;
            $finalDesignerAddress = null;
            $finalCompanyName = null;

            if ($designerType === 'internal') {
                if (!empty($internalEmail)) {
                    $stmtU = $db->prepare('SELECT full_name FROM users WHERE email = ? LIMIT 1');
                    $stmtU->bind_param('s', $internalEmail);
                    $stmtU->execute();
                    $resU = $stmtU->get_result();
                    if ($resU->num_rows > 0) {
                        $finalDesignerName = $resU->fetch_assoc()['full_name'];
                    } else {
                        $errorCount++; $errors[] = "Internal Designer email '$internalEmail' not found"; continue;
                    }
                } else {
                    // Fallback to session user
                    $uRow   = $db->query("SELECT full_name FROM users WHERE id = {$sessionUserId} LIMIT 1")->fetch_assoc();
                    $finalDesignerName = $uRow['full_name'] ?? '';
                }
            } else {
                $finalDesignerName = $designerName;
                $finalDesignerPhone = $designerPhone;
                $finalDesignerAddress = $designerAddress;
                $finalCompanyName = $companyName;
            }

            $ins = $db->prepare("INSERT INTO project_structural_designs (project_id, designer_type, designer_name, company_name, designer_phone, designer_address, design_link, created_by) VALUES (?,?,?,?,?,?,?,?)");
            $ins->bind_param("issssssi", $projectId, $designerType, $finalDesignerName, $finalCompanyName, $finalDesignerPhone, $finalDesignerAddress, $designLink, $sessionUserId);
            
            if ($ins->execute()) { 
                $successCount++; 
            } else { 
                $errorCount++; $errors[] = "Failed for Project '$projectName'"; 
            }
        }
        fclose($handle);
        
        if ($errorCount > 0) {
            setError("Imported $successCount. Failed $errorCount. " . implode(' | ', array_slice($errors, 0, 5)));
        } else {
            setSuccess("Successfully imported $successCount records.");
        }
        
        header('Location: ' . $GLOBALS['base_url'] . '/structure-design');
        exit;
    }
}
