<?php
namespace Phase2;

use Controller;
use Con_ArchitectEngineering;

require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';

class ArchitectEngineeringController extends Controller {
    protected $db;
    protected $model;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new Con_ArchitectEngineering($db);

        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $userId  = $_SESSION['user']['id']  ?? 0;
        $roleId  = $_SESSION['user']['role_id'] ?? 0;

        if (in_array($roleId, [1, 2, 3, 33, 13, 15, 22])) {
            $records = $this->model->getAll();
        } else {
            $stmt = $this->db->prepare("
                SELECT ae.*, p.name AS project_name, u.full_name AS user_name
                FROM architect_engineering ae
                LEFT JOIN projects p ON ae.project_id = p.id
                LEFT JOIN users u ON ae.user_id = u.id
                WHERE p.architect_id = ?
                ORDER BY ae.id DESC
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/architect_engineering/architect-engineering', [
            'current_route' => 'con-architect-engineering',
            'records' => $records,
            'page_title'   => 'Architecture Design Records',
        ]);
    }

    public function create() {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 18) {
            setError('Only Architects can add Architecture records.');
            header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
            exit;
        }

        $userId   = $_SESSION['user']['id']  ?? 0;
        $roleId   = $_SESSION['user']['role_id'] ?? 0;
        $projects = $this->getMyAssignedProjects($userId, $roleId, 'architect_id');
        $users    = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/architect_engineering/create-architect-engineering', [
            'current_route' => 'con-architect-engineering',
            'page_title'    => 'Add Architecture',
            'projects' => $projects,
            'users'    => $users,
        ]);
    }

    public function store() {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 18) {
            setError('Only Architects can add Architecture records.');
            header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
            exit;
        }

        $data = [
            'project_id'                    => $_POST['project_id'] ?? 0,
            'user_id'                       => $_SESSION['user']['id'], // auto from session
            'full_architecture_design_link' => $_POST['full_architecture_design_link'] ?? null,
            'notes'                         => $_POST['notes'] ?? null,
            'status'                        => $_POST['status'] ?? 'draft',
            'created_by'                    => $_SESSION['user']['id'],
        ];

        if ($this->model->create($data)) {
            $projectId   = (int)($data['project_id']);
            $projectName = $this->getProjectName($projectId);
            $status      = $data['status'] ?? 'draft';
            try {
                $wfMail = new \ProjectWorkflowMailService($this->db);
                if ($status === 'partially') {
                    // Only PM gets notified
                    $wfMail->notifyPmOnAePartial($projectId, $projectName);
                } elseif ($status === 'fully') {
                    // PM + SE + Draftsman (Draftsman = Action Required)
                    $wfMail->notifyDraftsmanAfterAE($projectId, $projectName);
                }
                // 'draft' → no email
            } catch (\Throwable $e) {
                error_log('[AE] Email error: ' . $e->getMessage());
            }
            setSuccess("Architecture record created successfully.");
        } else {
            setError("Failed to create record.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
        exit;
    }

    public function edit() {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 18) {
            setError('Only Architects can edit Architecture records.');
            header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
            exit;
        }

        $id     = $_GET['id'] ?? 0;
        $record = $this->model->find($id);

        if (!$record) {
            setError("Record not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
            exit;
        }

        if ($record['status'] === 'fully') {
            setError("Cannot edit a fully completed architecture record.");
            header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
            exit;
        }

        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $users    = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/architect_engineering/edit-architect-engineering', [
            'current_route' => 'con-architect-engineering',
            'page_title'    => 'Edit Architecture',
            'record'   => $record,
            'projects' => $projects,
            'users'    => $users,
        ]);
    }

    public function update() {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 18) {
            setError('Only Architects can edit Architecture records.');
            header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
            exit;
        }

        $id = $_POST['id'] ?? 0;

        // Capture OLD status before update
        $oldRecord = $this->model->find($id);
        if (!$oldRecord) {
            setError("Record not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
            exit;
        }
        
        $oldStatus = $oldRecord['status'] ?? 'draft';

        if ($oldStatus === 'fully') {
            setError("Cannot update a fully completed architecture record.");
            header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
            exit;
        }

        $data = [
            'project_id'                    => $_POST['project_id'] ?? 0,
            'user_id'                       => $_SESSION['user']['id'], // auto from session
            'full_architecture_design_link' => $_POST['full_architecture_design_link'] ?? null,
            'notes'                         => $_POST['notes'] ?? null,
            'status'                        => $_POST['status'] ?? 'draft',
        ];
        $newStatus = $data['status'];

        if ($this->model->update($id, $data)) {
            $projectId   = (int)($data['project_id']);
            $projectName = $this->getProjectName($projectId);

            try {
                $wfMail = new \ProjectWorkflowMailService($this->db);

                if ($newStatus === 'partially' && $oldStatus !== 'partially') {
                    // Changed TO partially → notify PM only
                    $wfMail->notifyPmOnAePartial($projectId, $projectName);

                } elseif ($newStatus === 'fully' && $oldStatus !== 'fully') {
                    // Changed TO fully (from draft OR partially) → notify PM + SE + Draftsman
                    $wfMail->notifyDraftsmanAfterAE($projectId, $projectName);
                }
                // No change in status → no email
            } catch (\Throwable $e) {
                error_log('[AE] Update email error: ' . $e->getMessage());
            }

            setSuccess("Updated successfully.");
        } else {
            setError("Failed to update record.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
        exit;
    }

    public function delete() {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 18) {
            setError('Only Architects can delete Architecture records.');
            header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
            exit;
        }

        $id = $_POST['id'] ?? 0;
        
        $record = $this->model->find($id);
        if ($record && ($record['status'] ?? 'draft') === 'fully') {
            setError("Cannot delete a fully completed architecture record.");
            header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
            exit;
        }

        if ($this->model->delete($id)) {
            setSuccess("Deleted successfully.");
        } else {
            setError("Failed to delete record.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
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

    // ─── CSV Import Method ───────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-architect-engineering');
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-architect-engineering');
            exit;
        }

        $file   = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if ($handle === FALSE) {
            setError('Failed to open CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-architect-engineering');
            exit;
        }

        $header       = array_map('trim', fgetcsv($handle));
        $header[0]    = ltrim($header[0], "\xEF\xBB\xBF"); // Strip UTF-8 BOM (Excel CSV)
        $successCount = 0;
        $errorCount   = 0;
        $errors       = [];
        $db           = $this->db;
        $createdBy    = $_SESSION['user']['id'] ?? 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty(array_filter($data))) continue;
            if (count($header) !== count($data)) {
                file_put_contents(__DIR__ . '/import_debug.log',
                    "SKIP: header_count=" . count($header) . " data_count=" . count($data) . "\n", FILE_APPEND);
                continue;
            }
            $row = array_combine($header, $data);

            // Debug log
            file_put_contents(__DIR__ . '/import_debug.log',
                date('H:i:s') . " ROW: " . json_encode($row) . "\n", FILE_APPEND);

            // ── Flexible key resolver (handles "Project_Name (required)" etc.) ──
            $resolveKey = function(array $row, string $prefix): string {
                if (array_key_exists($prefix, $row)) return $row[$prefix];
                foreach ($row as $k => $v) {
                    if (stripos(trim($k), $prefix) === 0) return $v;
                }
                return '';
            };

            // ── Required: Project_Name ────────────────────────────────────
            $projectName = trim($resolveKey($row, 'Project_Name'));
            if (empty($projectName)) {
                $errorCount++;
                $errors[] = 'Missing Project_Name';
                continue;
            }

            // Resolve project_id
            $stmt = $db->prepare('SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1');
            $stmt->bind_param('s', $projectName);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) {
                $errorCount++;
                $errors[] = "Project '$projectName' not found";
                continue;
            }
            $projectId = (int) $res->fetch_assoc()['id'];

            // ── Optional: Architect_Email → user_id ───────────────────────
            $architectEmail  = trim($resolveKey($row, 'Architect_Email'));
            $architectUserId = 0;
            if (!empty($architectEmail)) {
                $stmtU = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $stmtU->bind_param('s', $architectEmail);
                $stmtU->execute();
                $resU = $stmtU->get_result();
                if ($resU->num_rows > 0) {
                    $architectUserId = (int) $resU->fetch_assoc()['id'];
                } else {
                    $errors[] = "Warning: Architect email '$architectEmail' not found — using uploader as architect";
                    $architectUserId = $createdBy;
                }
            } else {
                $architectUserId = $createdBy;
            }

            // ── Other columns ─────────────────────────────────────────────
            $designLink = trim($resolveKey($row, 'Design_Link'));
            $notes      = trim($resolveKey($row, 'Notes'));
            $status     = trim($resolveKey($row, 'Status'));
            $allowedStatuses = ['draft', 'partially', 'fully'];
            if (!in_array($status, $allowedStatuses)) {
                $status = 'draft';
            }

            // ── INSERT ────────────────────────────────────────────────────
            $ins = $db->prepare(
                'INSERT INTO architect_engineering
                 (project_id, user_id, full_architecture_design_link, notes, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $ins->bind_param('iisssi', $projectId, $architectUserId, $designLink, $notes, $status, $createdBy);

            if ($ins->execute()) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "DB error for '$projectName': " . $db->error;
            }
        }

        fclose($handle);

        if ($errorCount > 0) {
            setError("Imported $successCount. Failed $errorCount. " . implode(' | ', array_slice($errors, 0, 5)));
        } else {
            setSuccess("Successfully imported $successCount architect records.");
        }

        header('Location: ' . $GLOBALS['base_url'] . '/con-architect-engineering');
        exit;
    }

}
