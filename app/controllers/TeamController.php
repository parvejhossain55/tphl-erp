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

        // Dynamic Role-Permission Guard: Requires 'Sales Team' permission (or Admin/Super Admin)
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!hasPermission('Sales Team') && !in_array($roleId, [1, 3])) {
            setError("You do not have permission to access Teams Management.");
            header("Location: {$GLOBALS['base_url']}/dashboard");
            exit;
        }
    }

    public function index()
    {
        $teams = $this->teamModel->getAllWithMembers();
        $salesEmployees = $this->teamModel->getSalesEmployees();

        $this->view("sales-developments/teams/teams", [
            "current_route" => "teams",
            "page_title" => "Teams Management",
            "teams" => $teams,
            "salesEmployees" => $salesEmployees,
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
}
