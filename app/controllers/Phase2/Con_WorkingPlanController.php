<?php
namespace Phase2;

use Controller;
use Con_WorkingPlan;
use Project;
use Con_InitialEstimate;
use Con_DesignType;

class Con_WorkingPlanController extends Controller

{
    protected $db;
    protected $model;
    protected $projectModel;
    protected $budgetModel;
    protected $designTypeModel;

    public function __construct($db)
    {
        $this->db              = $db;
        $this->model           = new Con_WorkingPlan($db);
        $this->projectModel    = new Project($db);
        $this->budgetModel     = new Con_InitialEstimate($db);
        $this->designTypeModel = new Con_DesignType($db);
    }

    // =========================================================================
    // WORKING PLANS — LIST
    // =========================================================================

    public function index()
    {
        $plans = $this->model->getAll();

        $this->view('phase2/working_plans/working-plan-lists', [
            'current_route' => 'con-working-plans',
            'page_title'    => 'Project Working Plans',
            'plans'         => $plans,
        ]);
    }

    // =========================================================================
    // WORKING PLANS — CREATE
    // =========================================================================

    public function create()
    {
        $projects    = $this->projectModel->getAll();
        $budgets     = $this->budgetModel->getAll();
        $designTypes = $this->designTypeModel->getAll();

        $this->view('phase2/working_plans/create-working-plan', [
            'current_route' => 'con-working-plan-create',
            'page_title'    => 'New Working Plan Entry',
            'projects'      => $projects,
            'budgets'       => $budgets,
            'designTypes'   => $designTypes,
        ]);
    }

    // =========================================================================
    // WORKING PLANS — STORE
    // =========================================================================

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'project_id'        => (int)   ($_POST['project_id']        ?? 0),
            'initial_budget_id' => (int)   ($_POST['initial_budget_id'] ?? 0),
            'design_type_id'    => (int)   ($_POST['design_type_id']    ?? 0),
            'year'              => (int)   ($_POST['year']              ?? date('Y')),
            'month'             => (int)   ($_POST['month']             ?? date('n')),
            'work_description'  =>          trim($_POST['work_description'] ?? ''),
            'planned_quantity'  => (float) ($_POST['planned_quantity']  ?? 0),
            'estimated_cost'    => (float) ($_POST['estimated_cost']    ?? 0),
            'created_by'        => (int)   ($_SESSION['user']['id']     ?? 0),
        ];

        if (!$data['project_id'] || !$data['initial_budget_id']) {
            setError('Project and Budget are required.');
            header("Location: {$GLOBALS['base_url']}/con-working-plan-create");
            exit;
        }

        $result = $this->model->createPlan($data);
        if ($result['ok']) {
            setSuccess('Working plan entry added successfully.');
            header("Location: {$GLOBALS['base_url']}/con-working-plans");
        } else {
            setError($result['error']);
            header("Location: {$GLOBALS['base_url']}/con-working-plan-create");
        }
        exit;
    }

    // =========================================================================
    // WORKING PLANS — EDIT
    // =========================================================================

    public function edit($id)
    {
        $plan = $this->model->find($id);
        if (!$plan) {
            setError('Plan entry not found.');
            header("Location: {$GLOBALS['base_url']}/con-working-plans");
            exit;
        }

        $projects    = $this->projectModel->getAll();
        $budgets     = $this->budgetModel->getAll();
        $designTypes = $this->designTypeModel->getAll();

        $this->view('phase2/working_plans/edit-working-plan', [
            'current_route' => 'con-working-plans',
            'page_title'    => 'Edit Working Plan',
            'plan'          => $plan,
            'projects'      => $projects,
            'budgets'       => $budgets,
            'designTypes'   => $designTypes,
        ]);
    }

    // =========================================================================
    // WORKING PLANS — UPDATE
    // =========================================================================

    public function update($id)
    {
        $data = [
            'project_id'        => (int)   ($_POST['project_id']        ?? 0),
            'initial_budget_id' => (int)   ($_POST['initial_budget_id'] ?? 0),
            'design_type_id'    => (int)   ($_POST['design_type_id']    ?? 0),
            'year'              => (int)   ($_POST['year']              ?? date('Y')),
            'month'             => (int)   ($_POST['month']             ?? date('n')),
            'work_description'  =>          trim($_POST['work_description'] ?? ''),
            'planned_quantity'  => (float) ($_POST['planned_quantity']  ?? 0),
            'estimated_cost'    => (float) ($_POST['estimated_cost']    ?? 0),
        ];

        $result = $this->model->updatePlan($id, $data);
        if ($result['ok']) {
            setSuccess('Working plan updated.');
        } else {
            setError($result['error']);
        }
        header("Location: {$GLOBALS['base_url']}/con-working-plans");
        exit;
    }

    // =========================================================================
    // WORKING PLANS — DELETE
    // =========================================================================

    public function delete($id)
    {
        if ($this->model->deletePlan($id)) {
            setSuccess('Plan entry deleted.');
        } else {
            setError('Cannot delete — plan may be locked or not found.');
        }
        header("Location: {$GLOBALS['base_url']}/con-working-plans");
        exit;
    }

    // =========================================================================
    // BUDGET EXTENSIONS — LIST
    // =========================================================================

    public function budgetExtends()
    {
        $extends  = $this->model->getBudgetExtends();
        $projects = $this->projectModel->getAll();
        $budgets  = $this->budgetModel->getAll();
        $report   = $this->model->getBudgetStatusReport();

        $this->view('phase2/working_plans/budget-extend-lists', [
            'current_route' => 'con-budget-extends',
            'page_title'    => 'Budget Extension Requests',
            'extends'       => $extends,
            'projects'      => $projects,
            'budgets'       => $budgets,
            'report'        => $report,
        ]);
    }

    // =========================================================================
    // BUDGET EXTENSIONS — CREATE FORM
    // =========================================================================

    public function budgetExtendCreate()
    {
        $projects = $this->projectModel->getAll();
        $budgets  = $this->budgetModel->getAll();

        $this->view('phase2/working_plans/create-budget-extend', [
            'current_route' => 'con-budget-extends',
            'page_title'    => 'Request Budget Extension',
            'projects'      => $projects,
            'budgets'       => $budgets,
        ]);
    }

    // =========================================================================
    // BUDGET EXTENSIONS — STORE
    // =========================================================================

    public function budgetExtendStore()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'project_id'        => (int)   ($_POST['project_id']        ?? 0),
            'initial_budget_id' => (int)   ($_POST['initial_budget_id'] ?? 0),
            'requested_by'      => (int)   ($_SESSION['user']['id']     ?? 0),
            'title'             =>          trim($_POST['title']         ?? ''),
            'amount'            => (float) ($_POST['amount']            ?? 0),
            'reason'            =>          trim($_POST['reason']        ?? ''),
            'docs_link'         =>          trim($_POST['docs_link']     ?? ''),
        ];

        if (!$data['project_id'] || !$data['initial_budget_id'] || empty($data['title']) || $data['amount'] <= 0) {
            setError('All fields are required and amount must be > 0.');
            header("Location: {$GLOBALS['base_url']}/con-budget-extend-create");
            exit;
        }

        // Handle file upload (PDF/Image evidence)
        $data['attachment_file'] = uploadFile('attachment_file', 'budget_extends');

        if ($this->model->createBudgetRequest($data)) {
            setSuccess('Budget extension request submitted successfully.');
            header("Location: {$GLOBALS['base_url']}/con-budget-extends");
        } else {
            setError('Failed to submit request. Please try again.');
            header("Location: {$GLOBALS['base_url']}/con-budget-extend-create");
        }
        exit;
    }

    // =========================================================================
    // BUDGET EXTENSIONS — APPROVE
    // =========================================================================

    public function approveExtend($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId  = (int)($_SESSION['user']['id'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        $result = $this->model->approveBudgetExtend($id, $userId, $remarks);
        if ($result['ok']) {
            setSuccess('Budget extension request approved. Revised budget updated.');
        } else {
            setError($result['error']);
        }
        header("Location: {$GLOBALS['base_url']}/con-budget-extends");
        exit;
    }

    // =========================================================================
    // BUDGET EXTENSIONS — REJECT
    // =========================================================================

    public function rejectExtend($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId  = (int)($_SESSION['user']['id'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        if (empty($remarks)) {
            setError('Remarks are required when rejecting a request.');
            header("Location: {$GLOBALS['base_url']}/con-budget-extends");
            exit;
        }

        $result = $this->model->rejectBudgetExtend($id, $userId, $remarks);
        if ($result['ok']) {
            setSuccess('Budget extension request rejected.');
        } else {
            setError($result['error']);
        }
        header("Location: {$GLOBALS['base_url']}/con-budget-extends");
        exit;
    }

    // =========================================================================
    // AJAX — Project Data (working plans + budgets for a given project)
    // GET: ?project_id=N
    // Returns JSON: { work_plans: [...], budgets: [...] }
    // =========================================================================

    public function ajaxGetProjectData()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);

        if (!$projectId) {
            echo json_encode(['work_plans' => [], 'budgets' => []]);
            exit;
        }

        // Working plans for this project
        $plans = $this->model->getByProject($projectId);
        $workPlans = array_map(function ($p) {
            return [
                'id'                => $p['id'],
                'initial_budget_id' => $p['initial_budget_id'],
                'label'             => ($p['year'] ?? '') . '/' . str_pad($p['month'] ?? '', 2, '0', STR_PAD_LEFT)
                                       . ' — ' . ($p['design_type_name'] ?? 'Plan')
                                       . ' — ' . mb_substr($p['work_description'] ?? '', 0, 40),
                'budget_label'      => '৳ ' . number_format($p['budget_total'] ?? 0, 2),
                'estimated_cost'    => $p['estimated_cost'] ?? 0,
                'work_description'  => $p['work_description'] ?? '',
                'design_type'       => $p['design_type_name'] ?? '',
            ];
        }, $plans);

        // Approved budgets for this project
        $stmt = $this->db->prepare(
            "SELECT id, total_estimated_cost, status
             FROM initial_estimate_budgets
             WHERE project_id = ? AND status = 'approved'
             ORDER BY id DESC"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $rawBudgets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $budgets = array_map(function ($b) {
            return [
                'id'    => $b['id'],
                'label' => 'Budget #' . $b['id'] . ' — ৳ ' . number_format($b['total_estimated_cost'], 2),
                'total' => $b['total_estimated_cost'],
            ];
        }, $rawBudgets);

        echo json_encode(['work_plans' => $workPlans, 'budgets' => $budgets]);
        exit;
    }
}
