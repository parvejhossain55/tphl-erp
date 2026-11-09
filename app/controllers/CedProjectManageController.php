<?php

class CedProjectManageController extends Controller
{
    protected $db;
    protected $assignmentModel;

    public function __construct($db)
    {
        $this->db = $db;
        require_once dirname(__DIR__) . '/models/CedProjectAssignment.php';
        $this->assignmentModel = new CedProjectAssignment($this->db);
    }

    /**
     * Check if the current user has management privileges over CED projects (Head of CED, Admin, Super Admin)
     */
    protected function canManageCed(): bool
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $secRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $priRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        return in_array($roleId, [1, 3, 33, 38, 39], true)
            || in_array($secRoleId, [1, 3, 38, 39], true)
            || in_array($priRoleId, [1, 3, 38, 39], true)
            || strpos($roleName, 'head of client experience') !== false
            || (function_exists('isHeadOfClientExperience') && isHeadOfClientExperience());
    }

    /**
     * View: Manage Projects for CED (Head of CED & Admin)
     */
    public function manageProjects()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!$this->canManageCed()) {
            setError("You do not have permission to manage CED project assignments.");
            header("Location: {$GLOBALS['base_url']}/ced-projects-history");
            exit;
        }

        $agents = $this->assignmentModel->getAvailableCedAgents();
        $projects = $this->assignmentModel->getAllProjects();
        $search = trim($_GET['search'] ?? '');
        $summary = $this->assignmentModel->getPortfolioSummary($search);

        $this->view("ced/projects/manage-projects", [
            "current_route" => "manage-projects-for-ced",
            "page_title"    => "Manage Projects for CED",
            "agents"        => $agents,
            "projects"      => $projects,
            "summary"       => $summary,
            "search"        => $search
        ]);
    }

    /**
     * AJAX: Get currently assigned project IDs for an agent
     */
    public function getAgentProjectsAjax($agentId)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->canManageCed()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $agentId = (int)$agentId;
        $assignedProjectIds = $this->assignmentModel->getAgentAssignedProjectIds($agentId);

        echo json_encode([
            'success' => true,
            'agent_id' => $agentId,
            'assigned_project_ids' => $assignedProjectIds
        ]);
        exit;
    }

    /**
     * AJAX: Save project assignments for an agent
     */
    public function saveAssignmentsAjax()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->canManageCed()) {
            echo json_encode(['success' => false, 'message' => 'Permission denied. Only Head of CED and Admin can assign projects.']);
            exit;
        }

        $agentId = (int)($_POST['user_id'] ?? 0);
        $projectIds = isset($_POST['project_ids']) && is_array($_POST['project_ids']) 
            ? array_map('intval', $_POST['project_ids']) 
            : [];
        $currentUserId = (int)($_SESSION['user']['id'] ?? 1);

        if ($agentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid CED agent selected.']);
            exit;
        }

        $saved = $this->assignmentModel->saveAgentProjectAssignments($agentId, $projectIds, $currentUserId);

        if ($saved) {
            echo json_encode([
                'success' => true,
                'message' => 'Projects successfully assigned to the agent.',
                'assigned_count' => count($projectIds)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save project assignments. Please try again.']);
        }
        exit;
    }

    /**
     * View: My Projects History / CED Projects History
     */
    public function myProjectsHistory()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        $currentUserRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $canManage = $this->canManageCed();

        // Agents available for filter
        $agents = $this->assignmentModel->getAvailableCedAgents();

        // Target agent logic
        $selectedAgentId = isset($_GET['agent_id']) && $_GET['agent_id'] !== '' ? (int)$_GET['agent_id'] : null;
        $search = trim($_GET['search'] ?? '');
        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;

        // If regular agent (Role 7 or 37) and NOT manager, lock to self
        if (!$canManage) {
            $selectedAgentId = $currentUserId;
        }

        // Summary metrics (supports search and project filter)
        $allAgentsSummary = $canManage ? $this->assignmentModel->getPortfolioSummary($search, null, $selectedProjectId) : [];

        // If a specific agent is selected (or regular agent viewing self)
        $targetAgentSummary = null;
        $projectDetails = [];
        $assignedProjects = [];
        $activeAgentId = $selectedAgentId ?: ($canManage ? null : $currentUserId);

        $allProjects = $this->assignmentModel->getAllProjects();

        if ($activeAgentId) {
            $assignedProjectIds = $this->assignmentModel->getAgentAssignedProjectIds($activeAgentId);
            $assignedProjects = array_values(array_filter($allProjects, fn($p) => in_array((int)$p['id'], $assignedProjectIds, true)));
            $agentSum = $this->assignmentModel->getPortfolioSummary(null, $activeAgentId, $selectedProjectId);
            $targetAgentSummary = $agentSum[0] ?? null;
            $projectDetails = $this->assignmentModel->getAgentProjectDetails($activeAgentId, $selectedProjectId);
        }

        $this->view("ced/projects/my-projects-history", [
            "current_route"       => "ced-projects-history",
            "page_title"          => "CED Projects History",
            "canManage"           => $canManage,
            "agents"              => $agents,
            "selectedAgentId"     => $selectedAgentId,
            "selectedProjectId"   => $selectedProjectId,
            "search"              => $search,
            "allAgentsSummary"    => $allAgentsSummary,
            "targetAgentSummary"  => $targetAgentSummary,
            "projectDetails"      => $projectDetails,
            "allProjects"         => $allProjects,
            "assignedProjects"    => $assignedProjects,
            "currentUserId"       => $currentUserId
        ]);
    }
}
