<?php

class TeamController extends Controller
{
    protected $db;
    protected $teamModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->teamModel = new Team($this->db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Dynamic Role-Permission Guard: Requires 'Sales Team' permission, Head of Sales (9), or Admin/Management
        $roleId    = (int)($_SESSION['user']['role_id'] ?? 0);
        $secRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        if (!hasPermission('Sales Team') && !in_array($roleId, [1, 3, 9, 33, 34, 35], true) && $secRoleId !== 9) {
            setError("You do not have permission to access Teams Management.");
            header("Location: {$GLOBALS['base_url']}/dashboard");
            exit;
        }
    }

    protected function canAssignSalesManager(): bool
    {
        $roleId    = (int)($_SESSION['user']['role_id'] ?? 0);
        $secRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $roleName  = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));

        return in_array($roleId, [1, 3, 9], true)
            || $secRoleId === 9
            || str_contains($roleName, 'head of sales')
            || str_contains($roleName, 'sales team head');
    }

    public function index()
    {
        $teams = $this->teamModel->getAllWithMembers();
        $salesEmployees = $this->teamModel->getSalesEmployees();
        $salesManagers = $this->teamModel->getSalesManagers();

        $this->view("sales-developments/teams/teams", [
            "current_route" => "teams",
            "page_title" => "Teams Management",
            "teams" => $teams,
            "salesEmployees" => $salesEmployees,
            "salesManagers" => $salesManagers,
            "canAssignSalesManager" => $this->canAssignSalesManager(),
        ]);
    }

    public function store()
    {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'status' => isset($_POST['status']) ? intval($_POST['status']) : 0,
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['name'])) {
            setError("Team Name is required.");
            header("Location: {$GLOBALS['base_url']}/teams");
            exit;
        }

        $teamId = $this->teamModel->create($data);
        if ($teamId) {
            $employeeIds = $_POST['employee_ids'] ?? [];
            if (!is_array($employeeIds)) {
                $employeeIds = [];
            }
            $leaderId = (int)($_POST['leader_id'] ?? 0);

            $this->teamModel->syncTeamMembers($teamId, $employeeIds, $leaderId);

            if ($this->canAssignSalesManager() && isset($_POST['manager_user_id'])) {
                $managerUserId = (int)($_POST['manager_user_id'] ?? 0);
                $note = trim((string)($_POST['manager_note'] ?? ''));
                $assignedBy = (int)($_SESSION['user']['id'] ?? 0) ?: null;
                $this->teamModel->syncTeamManager($teamId, $managerUserId, $assignedBy, $note !== '' ? $note : null);
            }

            setSuccess("Team created successfully with assigned members and leader.");
            header("Location: {$GLOBALS['base_url']}/teams");
            exit;
        } else {
            setError("Failed to create team.");
            header("Location: {$GLOBALS['base_url']}/teams");
            exit;
        }
    }

    public function update()
    {
        $teamId = (int)($_POST['id'] ?? 0);
        $data = [
            'id' => $teamId,
            'name' => trim($_POST['name'] ?? ''),
            'status' => isset($_POST['status']) ? intval($_POST['status']) : 0,
        ];

        if ($teamId <= 0 || empty($data['name'])) {
            setError("Invalid team data.");
            header("Location: {$GLOBALS['base_url']}/teams");
            exit;
        }

        if ($this->teamModel->update($data)) {
            $employeeIds = $_POST['employee_ids'] ?? [];
            if (!is_array($employeeIds)) {
                $employeeIds = [];
            }
            $leaderId = (int)($_POST['leader_id'] ?? 0);

            $this->teamModel->syncTeamMembers($teamId, $employeeIds, $leaderId);

            if ($this->canAssignSalesManager() && isset($_POST['manager_user_id'])) {
                $managerUserId = (int)($_POST['manager_user_id'] ?? 0);
                $note = trim((string)($_POST['manager_note'] ?? ''));
                $assignedBy = (int)($_SESSION['user']['id'] ?? 0) ?: null;
                $this->teamModel->syncTeamManager($teamId, $managerUserId, $assignedBy, $note !== '' ? $note : null);
            }

            setSuccess("Team updated successfully.");
            header("Location: {$GLOBALS['base_url']}/teams");
            exit;
        } else {
            setError("Failed to update team.");
            header("Location: {$GLOBALS['base_url']}/teams");
            exit;
        }
    }

    public function delete()
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id && $this->teamModel->delete($id)) {
            setSuccess("Team deleted successfully.");
            header("Location: {$GLOBALS['base_url']}/teams");
            exit;
        } else {
            setError("Failed to delete team.");
            header("Location: {$GLOBALS['base_url']}/teams");
            exit;
        }
    }

    public function managerHistory()
    {
        header('Content-Type: application/json');
        $teamId = (int)($_GET['team_id'] ?? 0);
        if ($teamId <= 0) {
            echo json_encode(['success' => false, 'history' => []]);
            exit;
        }
        $history = $this->teamModel->getTeamManagerHistory($teamId);
        echo json_encode(['success' => true, 'history' => $history]);
        exit;
    }

    public function assignManagerTeams()
    {
        if (!$this->canAssignSalesManager()) {
            setError("You do not have permission to assign Cluster Heads.");
            header("Location: {$GLOBALS['base_url']}/teams");
            exit;
        }

        $managerUserId = (int)($_POST['manager_user_id'] ?? 0);
        $teamIds = $_POST['team_ids'] ?? [];
        if (!is_array($teamIds)) {
            $teamIds = [];
        }
        $note = trim((string)($_POST['note'] ?? ''));
        $assignedBy = (int)($_SESSION['user']['id'] ?? 0) ?: null;

        if ($managerUserId <= 0) {
            setError("Please select a Cluster Head.");
            header("Location: {$GLOBALS['base_url']}/teams");
            exit;
        }

        if ($this->teamModel->syncManagerTeams($managerUserId, $teamIds, $assignedBy, $note !== '' ? $note : null)) {
            setSuccess("Cluster Head team assignments updated successfully.");
        } else {
            setError("Failed to update Cluster Head team assignments.");
        }

        header("Location: {$GLOBALS['base_url']}/teams");
        exit;
    }
}
