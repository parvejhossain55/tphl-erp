<?php
namespace Phase2;

use Controller;
use Con_InitialEstimate;
use Project;
use Con_ArchitectEngineering;
use Con_DesignType;
use Con_Material;
use Con_WorkType;

class Con_InitialEstimateController extends Controller
{
    protected $db;
    protected $model;
    protected $projectModel;
    protected $archModel;
    protected $designTypeModel;
    protected $materialModel;
    protected $workTypeModel;

    public function __construct($db)
    {
        $this->db              = $db;
        $this->model           = new Con_InitialEstimate($db);
        $this->projectModel    = new Project($db);
        $this->archModel       = new Con_ArchitectEngineering($db);
        $this->designTypeModel = new Con_DesignType($db);
        $this->materialModel   = new Con_Material($db);
        $this->workTypeModel   = new Con_WorkType($db);
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
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        // Only show projects assigned to this estimator
        $stmt = $this->db->prepare(
            "SELECT id, name FROM projects WHERE estimator_id = ? ORDER BY name ASC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $archs       = $this->archModel->getAll();
        $designTypes = $this->designTypeModel->getAll();
        $materials   = $this->materialModel->getAll();
        $workTypes   = $this->model->getWorkTypes();

        $this->view('phase2/initial_estimates/create-initial-budget', [
            'current_route' => 'con-estimate-create',
            'page_title'    => 'Create Initial Estimate Budget',
            'projects'      => $projects,
            'archs'         => $archs,
            'designTypes'   => $designTypes,
            'materials'     => $materials,
            'workTypes'     => $workTypes,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // ── Header ──────────────────────────────────────────────────────────
        $workTypeId = (int)($_POST['work_type_id'] ?? 0);
        // Fetch design_type_id from the selected work_type
        $wtRow = $workTypeId ? $this->workTypeModel->find($workTypeId) : null;
        $designTypeId = $wtRow ? (int)$wtRow['design_type_id'] : 0;

        $header = [
            'project_id'       => (int)   ($_POST['project_id']       ?? 0),
            'architecture_id'  => (int)   ($_POST['architecture_id']  ?? 0),
            'work_type_id'     => $workTypeId,
            'start_date'       =>         ($_POST['start_date']       ?? null),
            'end_date'         =>         ($_POST['end_date']         ?? null),
            'other_costs'      => (float) ($_POST['other_costs']      ?? 0),
            'other_cost_detail'=>         ($_POST['other_cost_detail']?? null),
            'budget_link'      =>         ($_POST['budget_link']      ?? null),
            'work_details'     =>         ($_POST['work_details']     ?? null),
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
        $matWorkTypes    = $_POST['item_work_type_id']    ?? [];
        $matDesignTypes  = $_POST['item_design_type_id']  ?? [];
        $matMaterialIds  = $_POST['item_material_id']     ?? [];
        $matUnits        = $_POST['item_material_unit']   ?? [];
        $matQtys         = $_POST['item_estimated_qty']   ?? [];
        $matCosts        = $_POST['item_unit_cost']       ?? [];
        $matRemarks      = $_POST['item_remarks']         ?? [];

        $items = [];
        foreach ($matMaterialIds as $i => $mid) {
            if (empty($mid)) continue;
            $wtId     = (int)($matWorkTypes[$i]  ?? 0);
            $dtId     = (int)($matDesignTypes[$i] ?? 0);
            // fallback: fetch from work_type if design_type_id not posted
            if (!$dtId && $wtId) {
                $wr = $this->workTypeModel->find($wtId);
                $dtId = $wr ? (int)$wr['design_type_id'] : 0;
            }
            $items[] = [
                'work_type_id'    => $wtId,
                'design_type_id'  => $dtId,
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
        $labWorkTypes    = $_POST['labor_work_type_id']    ?? [];
        $labDesignTypes  = $_POST['labor_design_type_id']  ?? [];
        $labTypeNames    = $_POST['labor_type_name']        ?? [];
        $labRates        = $_POST['labor_rate_per_sqft']    ?? [];
        $labAreas        = $_POST['labor_area_sqft']        ?? [];
        $labDescriptions = $_POST['labor_description']      ?? [];

        $labors = [];
        foreach ($labTypeNames as $i => $name) {
            if (empty($name)) continue;
            $wtId = (int)($labWorkTypes[$i]  ?? 0);
            $dtId = (int)($labDesignTypes[$i] ?? 0);
            if (!$dtId && $wtId) {
                $wr = $this->workTypeModel->find($wtId);
                $dtId = $wr ? (int)$wr['design_type_id'] : 0;
            }
            $labors[] = [
                'work_type_id'    => $wtId,
                'design_type_id'  => $dtId,
                'labor_type_name' =>          $name,
                'rate_per_sqft'   => (float) ($labRates[$i] ?? 0),
                'area_sqft'       => (float) ($labAreas[$i] ?? 0),
                'description'     =>          $labDescriptions[$i] ?? '',
            ];
        }
        if (!empty($labors)) {
            $this->model->addLabors($budgetId, $labors);
        }

        // ── Sync totals ──────────────────────────────────────────────────────
        $this->model->syncTotals($budgetId);

        // ── Send Email Notification ──────────────────────────────────────────
        require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
        $mailService = new \ProjectWorkflowMailService($this->db);
        
        // Fetch project name for the email
        $projectModel = new \Project($this->db);
        $project = $projectModel->find($header['project_id']);
        if ($project) {
            $mailService->notifyApproversForNewInitialBudget($header['project_id'], $project['name'], $budgetId);
        }

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
        $currentRoleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // ── Approval permission ──────────────────────────────────────────────
        // Roles 1 (MD), 3 (Director), 15 (PM) can approve — but NEVER the person who created it
        $approverRoles = [1, 3, 15];
        $createdBy     = (int)($budget['created_by'] ?? 0);
        $canApprove    = in_array($currentRoleId, $approverRoles)
                         && ($currentUserId !== $createdBy);

        // Fetch creator info (name + designation) to show in approval banner
        $creatorInfo = [];
        if ($createdBy) {
            $stmt = $this->db->prepare("
                SELECT u.full_name,
                       COALESCE(d.name, '') AS designation
                FROM   users u
                LEFT JOIN employees e  ON e.user_id = u.id
                LEFT JOIN designations d ON d.id = e.designation_id
                WHERE  u.id = ? LIMIT 1");
            $stmt->bind_param('i', $createdBy);
            $stmt->execute();
            $creatorInfo = $stmt->get_result()->fetch_assoc() ?? [];
        }

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
            'canApprove'     => $canApprove,
            'creatorInfo'    => $creatorInfo,
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
        $workTypes   = $this->model->getWorkTypes();

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
            'workTypes'     => $workTypes,
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
        $workTypeId   = (int)($_POST['work_type_id']      ?? 0);
        $wtRow        = $workTypeId ? $this->workTypeModel->find($workTypeId) : null;
        $designTypeId = $wtRow ? (int)$wtRow['design_type_id'] : 0;

        $header = [
            'project_id'      => (int)   ($_POST['project_id']      ?? 0),
            'architecture_id' => (int)   ($_POST['architecture_id'] ?? 0),
            'work_type_id'    => $workTypeId,
            'start_date'      =>         ($_POST['start_date']      ?? null),
            'end_date'        =>         ($_POST['end_date']        ?? null),
            'total_area_sqft' => (float) ($_POST['total_area_sqft'] ?? 0),
            'other_costs'     => (float) ($_POST['other_costs']     ?? 0),
            'other_cost_detail'=>         ($_POST['other_cost_detail']?? null),
            'budget_link'      =>         ($_POST['budget_link']      ?? null),
            'work_details'     =>         ($_POST['work_details']     ?? null),
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

        $matWorkTypes   = $_POST['item_work_type_id']   ?? [];
        $matDesignTypes = $_POST['item_design_type_id'] ?? [];
        $matMaterialIds = $_POST['item_material_id']    ?? [];
        $matUnits       = $_POST['item_material_unit']  ?? [];
        $matQtys        = $_POST['item_estimated_qty']  ?? [];
        $matCosts       = $_POST['item_unit_cost']      ?? [];
        $matRemarks     = $_POST['item_remarks']        ?? [];

        $items = [];
        foreach ($matMaterialIds as $i => $mid) {
            if (empty($mid)) continue;
            $wtId = (int)($matWorkTypes[$i]  ?? 0);
            $dtId = (int)($matDesignTypes[$i] ?? 0);
            if (!$dtId && $wtId) {
                $wr = $this->workTypeModel->find($wtId);
                $dtId = $wr ? (int)$wr['design_type_id'] : 0;
            }
            $items[] = [
                'work_type_id'     => $wtId,
                'design_type_id'   => $dtId,
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

        $labWorkTypes   = $_POST['labor_work_type_id']   ?? [];
        $labDesignTypes = $_POST['labor_design_type_id'] ?? [];
        $labTypeNames   = $_POST['labor_type_name']       ?? [];
        $labRates       = $_POST['labor_rate_per_sqft']   ?? [];
        $labAreas       = $_POST['labor_area_sqft']       ?? [];
        $labDescriptions = $_POST['labor_description']     ?? [];

        $labors = [];
        foreach ($labTypeNames as $i => $name) {
            if (empty($name)) continue;
            $wtId = (int)($labWorkTypes[$i]  ?? 0);
            $dtId = (int)($labDesignTypes[$i] ?? 0);
            if (!$dtId && $wtId) {
                $wr = $this->workTypeModel->find($wtId);
                $dtId = $wr ? (int)$wr['design_type_id'] : 0;
            }
            $labors[] = [
                'work_type_id'    => $wtId,
                'design_type_id'  => $dtId,
                'labor_type_name' =>          $name,
                'rate_per_sqft'   => (float) ($labRates[$i] ?? 0),
                'area_sqft'       => (float) ($labAreas[$i] ?? 0),
                'description'     =>          $labDescriptions[$i] ?? '',
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

        $userId   = (int)($_SESSION['user']['id']   ?? 0);
        $roleId   = (int)($_SESSION['user']['role_id'] ?? 0);

        // Role guard: only 1, 3, 15
        if (!in_array($roleId, [1, 3, 15])) {
            setError('You do not have permission to approve budgets.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

        // Creator cannot approve their own submission
        $budget = $this->model->find($id);
        if ($budget && (int)($budget['created_by'] ?? 0) === $userId) {
            setError('You cannot approve a budget that you created.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

        if ($this->model->isLocked($id)) {
            setError('This budget is already approved or cancelled.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

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
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id']   ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Role guard: only 1, 3, 15 can reject
        if (!in_array($roleId, [1, 3, 15])) {
            setError('You do not have permission to reject budgets.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

        // Creator cannot reject their own submission
        $budget = $this->model->find($id);
        if ($budget && (int)($budget['created_by'] ?? 0) === $userId) {
            setError('You cannot reject a budget that you created.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

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
            setSuccess('Budget rejected.');
        } else {
            setError('Cancellation failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
        exit;
    }

    // =========================================================================
    // AJAX — Quick-add material from estimate create page
    // POST /con-estimate-ajax-add-material
    // Returns JSON { success, id, name, unit, error? }
    // =========================================================================

    public function ajaxAddMaterial()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $name  = trim($_POST['name'] ?? '');
        $unit  = trim($_POST['unit'] ?? '');
        $min   = (int)($_POST['min_stock_level'] ?? 0);
        $uid   = (int)($_SESSION['user']['id'] ?? 0);

        if (!$name || !$unit) {
            echo json_encode(['success' => false, 'error' => 'Name and Unit are required.']);
            exit;
        }

        $data = ['name' => $name, 'unit' => $unit, 'min_stock_level' => $min, 'created_by' => $uid];
        $id   = $this->materialModel->create($data);

        if ($id) {
            echo json_encode(['success' => true, 'id' => $id, 'name' => $name, 'unit' => $unit]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save material.']);
        }
        exit;
    }

    // =========================================================================
    // AJAX — Architecture records for a project (GET ?project_id=N)
    // Returns JSON array of { id, label, design_link }
    // =========================================================================

    public function ajaxArchByProject()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);

        if (!$projectId) {
            echo json_encode([]);
            exit;
        }

        // architect_engineering records linked to this project
        $stmt = $this->db->prepare(
            "SELECT ae.id,
                    CONCAT('Arch #', ae.id, ' — ', p.name, ' (by ', u.full_name, ')') AS label,
                    ae.full_architecture_design_link AS design_link
             FROM architect_engineering ae
             LEFT JOIN projects p ON p.id = ae.project_id
             LEFT JOIN users    u ON u.id = ae.user_id
             WHERE ae.project_id = ?
             ORDER BY ae.id DESC
             LIMIT 20"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($rows);
        exit;
    }

    // =========================================================================
    // AJAX — All designs (arch + project drawings) for a project
    // GET ?project_id=N → returns { archs: [...], drawings: [...] }
    // =========================================================================

    public function ajaxDesignsByProject()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);

        if (!$projectId) {
            echo json_encode(['archs' => [], 'drawings' => []]);
            exit;
        }

        // Architecture records
        $stmt = $this->db->prepare(
            "SELECT ae.id,
                    ae.full_architecture_design_link AS design_link,
                    ae.notes,
                    u.full_name AS uploaded_by,
                    ae.created_at
             FROM architect_engineering ae
             LEFT JOIN users u ON u.id = ae.user_id
             WHERE ae.project_id = ?
             ORDER BY ae.id DESC"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $archs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Project Drawings
        $stmt2 = $this->db->prepare(
            "SELECT pd.id,
                    pd.design_link,
                    pd.version,
                    pd.status,
                    dt.name AS design_type,
                    u.full_name AS uploaded_by,
                    pd.created_at
             FROM project_drawings pd
             LEFT JOIN design_types dt ON dt.id = pd.design_type_id
             LEFT JOIN users        u  ON u.id  = pd.created_by
             WHERE pd.project_id = ?
             ORDER BY pd.id DESC"
        );
        $stmt2->bind_param('i', $projectId);
        $stmt2->execute();
        $drawings = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['archs' => $archs, 'drawings' => $drawings]);
        exit;
    }
}
