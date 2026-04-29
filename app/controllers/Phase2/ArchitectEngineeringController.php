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

        if (in_array($roleId, [1, 2, 13, 15])) {
            $records = $this->model->getAll();
        } else {
            $stmt = $this->db->prepare("
                SELECT ae.*, p.name AS project_name
                FROM architect_engineering ae
                LEFT JOIN projects p ON ae.project_id = p.id
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
            'page_title'   => 'Architect & Engineering'
        ]);
    }

    public function create() {
        $userId   = $_SESSION['user']['id']  ?? 0;
        $roleId   = $_SESSION['user']['role_id'] ?? 0;
        $projects = $this->getMyAssignedProjects($userId, $roleId, 'architect_id');
        $users    = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/architect_engineering/create-architect-engineering', [
            'current_route' => 'con-architect-engineering',
            'page_title'    => 'Add Architect & Engineering',
            'projects' => $projects,
            'users'    => $users,
        ]);
    }

    public function store() {
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
            setSuccess("Architect & Engineering record created successfully.");
        } else {
            setError("Failed to create record.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
        exit;
    }

    public function edit() {
        $id     = $_GET['id'] ?? 0;
        $record = $this->model->find($id);

        if (!$record) {
            setError("Record not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
            exit;
        }

        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $users    = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/architect_engineering/edit-architect-engineering', [
            'current_route' => 'con-architect-engineering',
            'page_title'    => 'Edit Architect & Engineering',
            'record'   => $record,
            'projects' => $projects,
            'users'    => $users,
        ]);
    }

    public function update() {
        $id = $_POST['id'] ?? 0;

        // Capture OLD status before update
        $oldRecord = $this->model->find($id);
        $oldStatus = $oldRecord['status'] ?? 'draft';

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
        $id = $_POST['id'] ?? 0;
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
