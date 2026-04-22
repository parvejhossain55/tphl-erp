<?php
namespace Phase2;

use Controller;
use Con_InitialEstimate;
use Project;
use Con_ArchitectEngineering;
use Con_DesignType;
use Con_Material;

class Con_InitialEstimateController extends Controller
{
    protected $db;
    protected $model;
    protected $projectModel;
    protected $archModel;
    protected $designTypeModel;
    protected $materialModel;

    public function __construct($db)
    {
        $this->db              = $db;
        $this->model           = new Con_InitialEstimate($db);
        $this->projectModel    = new Project($db);
        $this->archModel       = new Con_ArchitectEngineering($db);
        $this->designTypeModel = new Con_DesignType($db);
        $this->materialModel   = new Con_Material($db);
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function index()
    {
        $budgets = $this->model->getAll();

        $this->view('phase2/initial_estimates/initial-estimate-lists', [
            'current_route' => 'con-estimates',
            'page_title'    => 'Initial Estimate Budgets',
            'budgets'       => $budgets,
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create()
    {
        $projects    = $this->projectModel->getAll();
        $archs       = $this->archModel->getAll();
        $designTypes = $this->designTypeModel->getAll();
        $materials   = $this->materialModel->getAll();

        $this->view('phase2/initial_estimates/create-initial-budget', [
            'current_route' => 'con-estimate-create',
            'page_title'    => 'Create Initial Estimate Budget',
            'projects'      => $projects,
            'archs'         => $archs,
            'designTypes'   => $designTypes,
            'materials'     => $materials,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // ── Header ──────────────────────────────────────────────────────────
        $header = [
            'project_id'       => (int)   ($_POST['project_id']       ?? 0),
            'architecture_id'  => (int)   ($_POST['architecture_id']  ?? 0),
            'start_date'       =>         ($_POST['start_date']       ?? null),
            'end_date'         =>         ($_POST['end_date']         ?? null),
            'total_area_sqft'  => (float) ($_POST['total_area_sqft']  ?? 0),
            'other_costs'      => (float) ($_POST['other_costs']      ?? 0),
            'created_by'       => (int)   ($_SESSION['user']['id']    ?? 0),
        ];

        if (!$header['project_id'] || !$header['architecture_id']) {
            setError('Project and Architecture are required.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-create");
            exit;
        }

        // ── Insert header ────────────────────────────────────────────────────
        $budgetId = $this->model->createBudget($header);
        if (!$budgetId) {
            setError('Failed to create budget. Please try again.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-create");
            exit;
        }

        // ── Material Items ───────────────────────────────────────────────────
        $matDesignTypes  = $_POST['item_design_type_id']  ?? [];
        $matMaterialIds  = $_POST['item_material_id']     ?? [];
        $matUnits        = $_POST['item_material_unit']   ?? [];
        $matQtys         = $_POST['item_estimated_qty']   ?? [];
        $matCosts        = $_POST['item_unit_cost']       ?? [];
        $matRemarks      = $_POST['item_remarks']         ?? [];

        $items = [];
        foreach ($matMaterialIds as $i => $mid) {
            if (empty($mid)) continue;
            $items[] = [
                'design_type_id'  => (int)   ($matDesignTypes[$i] ?? 0),
                'material_id'     => (int)   $mid,
                'material_unit'   =>          $matUnits[$i] ?? '',
                'estimated_qty'   => (float) ($matQtys[$i]  ?? 0),
                'unit_cost_approx'=> (float) ($matCosts[$i] ?? 0),
                'remarks'         =>          $matRemarks[$i] ?? '',
            ];
        }
        if (!empty($items)) {
            $this->model->addItems($budgetId, $items);
        }

        // ── Labor Rows ───────────────────────────────────────────────────────
        $labDesignTypes  = $_POST['labor_design_type_id']  ?? [];
        $labTypeNames    = $_POST['labor_type_name']        ?? [];
        $labRates        = $_POST['labor_rate_per_sqft']    ?? [];
        $labAreas        = $_POST['labor_area_sqft']        ?? [];

        $labors = [];
        foreach ($labTypeNames as $i => $name) {
            if (empty($name)) continue;
            $labors[] = [
                'design_type_id'  => (int)   ($labDesignTypes[$i] ?? 0),
                'labor_type_name' =>          $name,
                'rate_per_sqft'   => (float) ($labRates[$i] ?? 0),
                'area_sqft'       => (float) ($labAreas[$i] ?? 0),
            ];
        }
        if (!empty($labors)) {
            $this->model->addLabors($budgetId, $labors);
        }

        // ── Sync totals ──────────────────────────────────────────────────────
        $this->model->syncTotals($budgetId);

        setSuccess('Initial estimate budget created successfully.');
        header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$budgetId}");
        exit;
    }

    // =========================================================================
    // SHOW (Detail + Design-wise breakdown)
    // =========================================================================

    public function show($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $budget = $this->model->find($id);
        if (!$budget) {
            setError('Budget not found.');
            header("Location: {$GLOBALS['base_url']}/con-estimates");
            exit;
        }

        $items     = $this->model->getItemsByBudget($id);
        $labors    = $this->model->getLaborsByBudget($id);
        $breakdown = $this->model->getFullBreakdownByDesignType($id);

        // Load working plans linked to this budget (with approved_status)
        $wpModel      = new \Con_WorkingPlan($this->db);
        $workingPlans = $wpModel->getByBudget($id);

        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);

        $this->view('phase2/initial_estimates/show-initial-budget', [
            'current_route'  => 'con-estimates',
            'page_title'     => 'Estimate Detail — ' . $budget['project_name'],
            'budget'         => $budget,
            'items'          => $items,
            'labors'         => $labors,
            'breakdown'      => $breakdown,
            'locked'         => $this->model->isLocked($id),
            'workingPlans'   => $workingPlans,
            'currentUserId'  => $currentUserId,
        ]);
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function edit($id)
    {
        $budget = $this->model->find($id);
        if (!$budget) {
            setError('Budget not found.');
            header("Location: {$GLOBALS['base_url']}/con-estimates");
            exit;
        }

        if ($this->model->isLocked($id)) {
            setError('This budget is approved or cancelled and cannot be edited.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

        $items       = $this->model->getItemsByBudget($id);
        $labors      = $this->model->getLaborsByBudget($id);
        $projects    = $this->projectModel->getAll();
        $archs       = $this->archModel->getAll();
        $designTypes = $this->designTypeModel->getAll();
        $materials   = $this->materialModel->getAll();

        $this->view('phase2/initial_estimates/edit-initial-budget', [
            'current_route' => 'con-estimates',
            'page_title'    => 'Edit Estimate — ' . $budget['project_name'],
            'budget'        => $budget,
            'existingItems' => $items,
            'existingLabors'=> $labors,
            'projects'      => $projects,
            'archs'         => $archs,
            'designTypes'   => $designTypes,
            'materials'     => $materials,
        ]);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($this->model->isLocked($id)) {
            setError('This budget is approved or cancelled and cannot be edited.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

        // ── Header ──────────────────────────────────────────────────────────
        $header = [
            'project_id'      => (int)   ($_POST['project_id']      ?? 0),
            'architecture_id' => (int)   ($_POST['architecture_id'] ?? 0),
            'start_date'      =>         ($_POST['start_date']      ?? null),
            'end_date'        =>         ($_POST['end_date']        ?? null),
            'total_area_sqft' => (float) ($_POST['total_area_sqft'] ?? 0),
            'other_costs'     => (float) ($_POST['other_costs']     ?? 0),
            'updated_by'      => (int)   ($_SESSION['user']['id']   ?? 0),
        ];

        if (!$header['project_id'] || !$header['architecture_id']) {
            setError('Project and Architecture are required.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-edit/{$id}");
            exit;
        }

        $this->model->updateBudget($id, $header);

        // ── Replace items (delete all → re-insert) ───────────────────────
        $this->model->deleteItemsByBudget($id);

        $matDesignTypes = $_POST['item_design_type_id'] ?? [];
        $matMaterialIds = $_POST['item_material_id']    ?? [];
        $matUnits       = $_POST['item_material_unit']  ?? [];
        $matQtys        = $_POST['item_estimated_qty']  ?? [];
        $matCosts       = $_POST['item_unit_cost']      ?? [];
        $matRemarks     = $_POST['item_remarks']        ?? [];

        $items = [];
        foreach ($matMaterialIds as $i => $mid) {
            if (empty($mid)) continue;
            $items[] = [
                'design_type_id'   => (int)   ($matDesignTypes[$i] ?? 0),
                'material_id'      => (int)   $mid,
                'material_unit'    =>          $matUnits[$i] ?? '',
                'estimated_qty'    => (float) ($matQtys[$i]  ?? 0),
                'unit_cost_approx' => (float) ($matCosts[$i] ?? 0),
                'remarks'          =>          $matRemarks[$i] ?? '',
            ];
        }
        if (!empty($items)) $this->model->addItems($id, $items);

        // ── Replace labors (delete all → re-insert) ──────────────────────
        $this->model->deleteLaborsByBudget($id);

        $labDesignTypes = $_POST['labor_design_type_id'] ?? [];
        $labTypeNames   = $_POST['labor_type_name']       ?? [];
        $labRates       = $_POST['labor_rate_per_sqft']   ?? [];
        $labAreas       = $_POST['labor_area_sqft']       ?? [];

        $labors = [];
        foreach ($labTypeNames as $i => $name) {
            if (empty($name)) continue;
            $labors[] = [
                'design_type_id'  => (int)   ($labDesignTypes[$i] ?? 0),
                'labor_type_name' =>          $name,
                'rate_per_sqft'   => (float) ($labRates[$i] ?? 0),
                'area_sqft'       => (float) ($labAreas[$i] ?? 0),
            ];
        }
        if (!empty($labors)) $this->model->addLabors($id, $labors);

        // ── Sync totals ──────────────────────────────────────────────────
        $this->model->syncTotals($id);

        setSuccess('Budget updated successfully.');
        header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
        exit;
    }

    // =========================================================================
    // APPROVE
    // =========================================================================

    public function approveBudget($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($this->model->isLocked($id)) {
            setError('This budget is already approved or cancelled.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        if ($this->model->approveBudget($id, $userId)) {
            setSuccess('Budget approved successfully.');
        } else {
            setError('Approval failed. Budget may not be in pending state.');
        }

        header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
        exit;
    }

    // =========================================================================
    // CANCEL
    // =========================================================================

    public function cancelBudget($id)
    {
        $note = trim($_POST['rejection_note'] ?? '');

        if (empty($note)) {
            setError('Rejection note is required to cancel a budget.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

        if ($this->model->isLocked($id)) {
            setError('This budget is already approved or cancelled.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

        if ($this->model->cancelBudget($id, $note)) {
            setSuccess('Budget cancelled.');
        } else {
            setError('Cancellation failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
        exit;
    }
}
