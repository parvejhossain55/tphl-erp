<?php
namespace Phase2;

use Controller;
use Con_WorkingPlan;
use Project;
use Con_Material;

class Con_WorkingPlanController extends Controller
{
    protected $db;
    protected $planModel;
    protected $projectModel;
    protected $materialModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->planModel = new Con_WorkingPlan($this->db);
        $this->projectModel = new Project($this->db);
        $this->materialModel = new Con_Material($this->db);
    }

    public function index()
    {
        $plans = $this->planModel->getAll();
        $this->view('phase2/working_plans/index', [
            'current_route' => 'con-working-plans',
            'page_title'    => 'Project Working Plans',
            'plans'         => $plans
        ]);
    }

    public function create()
    {
        $projects = $this->projectModel->getAll();
        $materials = $this->materialModel->getAll();

        $this->view('phase2/working_plans/create', [
            'current_route' => 'con-working-plans',
            'page_title'    => 'New Working Plan Entry',
            'projects'      => $projects,
            'materials'     => $materials
        ]);
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'project_id'       => $_POST['project_id'],
            'material_id'      => $_POST['material_id'],
            'year'             => $_POST['year'],
            'month'            => $_POST['month'],
            'planned_quantity' => $_POST['planned_quantity'],
            'estimated_cost'   => $_POST['estimated_cost'] ?? 0,
            'created_by'       => $_SESSION['user']['id']
        ];

        if ($this->planModel->createPlan($data)) {
            setSuccess('Working plan added.');
            header("Location: {$GLOBALS['base_url']}/con-working-plans");
            exit;
        }

        setError('Failed to add plan. Possible duplicate for same period.');
        header("Location: {$GLOBALS['base_url']}/con-working-plan-create");
        exit;
    }

    public function budgetExtends()
    {
        $extends = $this->planModel->getBudgetExtends();
        $projects = $this->projectModel->getAll();

        $this->view('phase2/working_plans/budget_extends', [
            'current_route' => 'con-working-plans',
            'page_title'    => 'Budget Extension Requests',
            'extends'       => $extends,
            'projects'      => $projects
        ]);
    }

    public function budgetExtendStore()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'project_id'   => $_POST['project_id'],
            'requested_by' => $_SESSION['user']['id'],
            'title'        => trim($_POST['title']),
            'amount'       => $_POST['amount'],
            'reason'       => $_POST['reason'] ?? ''
        ];

        if ($this->planModel->createBudgetRequest($data)) {
            setSuccess('Budget extension requested.');
            header("Location: {$GLOBALS['base_url']}/con-budget-extends");
            exit;
        }

        setError('Failed to submit request.');
        header("Location: {$GLOBALS['base_url']}/con-budget-extends");
        exit;
    }
}
