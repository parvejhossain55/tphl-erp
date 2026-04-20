<?php
namespace Phase2;

use Controller;
use Con_MaterialIssue;
use Con_Material;
use Con_Warehouse;
use Con_Stock;
use Project;

class Con_MaterialIssueController extends Controller
{
    protected $db;
    protected $issueModel;
    protected $materialModel;
    protected $warehouseModel;
    protected $stockModel;
    protected $projectModel;

    public function __construct($db)
    {
        $this->db             = $db;
        $this->issueModel     = new Con_MaterialIssue($this->db);
        $this->materialModel  = new Con_Material($this->db);
        $this->warehouseModel = new Con_Warehouse($this->db);
        $this->stockModel     = new Con_Stock($this->db);
        $this->projectModel   = new Project($this->db);
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function index()
    {
        $issues = $this->issueModel->getAll();

        $this->view('phase2/material-issues/issue-lists', [
            'current_route' => 'con-material-issues',
            'page_title'    => 'Material Issues (Stock OUT)',
            'issues'        => $issues,
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create()
    {
        $projects   = $this->projectModel->getAll();
        $warehouses = $this->warehouseModel->getAll();
        $materials  = $this->materialModel->getAll();
        $stocks     = $this->stockModel->getCurrentStock();

        // Fetch Working Plans and Targets
        $workingPlanModel = new \Con_WorkingPlan($this->db);
        $workPlans = $workingPlanModel->getAll();
        
        $targets = [];
        try {
            $tRes = $this->db->query("SELECT * FROM target_fixed ORDER BY id DESC");
            if ($tRes) $targets = $tRes->fetch_all(MYSQLI_ASSOC);
        } catch (\Exception $e) {}

        $this->view('phase2/material-issues/create-issue', [
            'current_route' => 'con-material-issues-create',
            'page_title'    => 'New Material Issue',
            'projects'      => $projects,
            'warehouses'    => $warehouses,
            'materials'     => $materials,
            'stocks'        => $stocks,
            'workPlans'     => $workPlans,
            'targets'       => $targets,
        ]);
    }

    // =========================================================================
    // STORE — one row per material
    // =========================================================================

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id'] ?? 0);

        $data = [
            'project_id'   => (int)($_POST['project_id'] ?? 0),
            'warehouse_id' => (int)($_POST['warehouse_id'] ?? 0),
            'material_id'  => (int)($_POST['material_id'] ?? 0),
            'quantity'     => (float)($_POST['quantity'] ?? 0),
            'issued_to'    => trim($_POST['issued_to'] ?? ''),
            'purpose'      => trim($_POST['purpose'] ?? ''),
            'issued_by'    => $userId,
            'issue_date'   => $_POST['issue_date'] ?? date('Y-m-d'),
            'target_id'    => !empty($_POST['target_id']) ? (int)$_POST['target_id'] : null,
            'work_plan_id' => !empty($_POST['work_plan_id']) ? (int)$_POST['work_plan_id'] : null,
        ];

        if (empty($data['material_id']) || $data['quantity'] <= 0) {
            setError('Material and quantity are required.');
            header("Location: {$GLOBALS['base_url']}/con-material-issue-create");
            exit;
        }

        $id = $this->issueModel->create($data);
        if ($id) {
            setSuccess("Material Issue ISS-{$id} created. Stock deducted.");
            header("Location: {$GLOBALS['base_url']}/con-material-issue/{$id}");
        } else {
            setError('Failed to create issue. Check stock availability.');
            header("Location: {$GLOBALS['base_url']}/con-material-issue-create");
        }
        exit;
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show($id)
    {
        $issue = $this->issueModel->find($id);
        if (!$issue) {
            setError('Material Issue not found.');
            header("Location: {$GLOBALS['base_url']}/con-material-issues");
            exit;
        }

        $this->view('phase2/material-issues/show-issue', [
            'current_route' => 'con-material-issues',
            'page_title'    => 'Material Issue ISS-' . $id,
            'issue'         => $issue,
        ]);
    }
}
