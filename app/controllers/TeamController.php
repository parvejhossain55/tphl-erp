<?php

class TeamController extends Controller
{
    protected $db;
    protected $teamModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->teamModel = new Team($this->db);
    }

    public function index()
    {
        $teams = $this->teamModel->getAll();
        $this->view("sales-developments/teams/teams", [
            "current_route" => "teams",
            "page_title" => "Teams Management",
            "teams" => $teams,
        ]);
    }

    public function store()
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'status' => isset($_POST['status']) ? 1 : 0,
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];

        if ($this->teamModel->create($data)) {
            setSuccess("Team created successfully.");
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
        $data = [
            'id' => $_POST['id'] ?? 0,
            'name' => $_POST['name'] ?? '',
            'status' => isset($_POST['status']) ? intval($_POST['status']) : 0,
        ];

        if ($this->teamModel->update($data)) {
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
        $id = $_POST['id'] ?? 0;
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
