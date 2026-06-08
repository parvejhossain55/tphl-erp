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
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id']      ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // PM (15,22) and Management (3) see all including drafts
        // Others see only their own drafts + all non-draft records
        $budgets = $this->model->getAll($userId, $roleId);

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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $filter = "1=1";
        if (!in_array($roleId, [1, 3, 22])) {
            $filter = "(pm_id = $userId OR site_engineer_id = $userId OR landman_id = $userId OR struct_engineer_id = $userId OR draftsman_id = $userId OR architect_id = $userId OR estimator_id = $userId OR qc_id = $userId OR operator_id = $userId OR created_by = $userId)";
        }

        $rProjs = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND $filter ORDER BY name ASC");
        $projects = $rProjs ? $rProjs->fetch_all(MYSQLI_ASSOC) : [];

        $archs           = $this->archModel->getAll();
        $designTypes     = $this->model->getDesignTypes();
        $workTypes       = $this->model->getWorkTypes();
        $workTypesGrouped= $this->model->getWorkTypesGrouped();
        $materials       = $this->materialModel->getAll();

        $this->view('phase2/initial_estimates/create-initial-budget', [
            'current_route'    => 'con-estimate-create',
            'page_title'       => 'Create Initial Estimate Budget',
            'projects'         => $projects,
            'archs'            => $archs,
            'designTypes'      => $designTypes,
            'workTypes'        => $workTypes,
            'workTypesGrouped' => $workTypesGrouped,
            'materials'        => $materials,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        // Determine status: draft or pending (submitted)
        $submitAction = $_POST['submit_action'] ?? 'draft';
        $status       = ($submitAction === 'submit') ? 'pending' : 'draft';

        // ── Header ──────────────────────────────────────────────────────────
        $header = [
            'project_id'        => (int)   ($_POST['project_id']        ?? 0),
            'architecture_id'   => (int)   ($_POST['architecture_id']   ?? 0),
            'drawing_id'        => (int)   ($_POST['drawing_id']        ?? 0) ?: null,
            'work_type_id'      => null,
            'start_date'        => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date'          => !empty($_POST['end_date'])   ? $_POST['end_date']   : null,
            'total_area_sqft'   => (float) ($_POST['total_area_sqft']   ?? 0),
            'other_costs'       => (float) ($_POST['other_costs']       ?? 0),
            'other_cost_detail' =>          $_POST['other_cost_detail'] ?? null,
            'budget_link'       =>          $_POST['budget_link']       ?? null,
            'work_details'      =>          $_POST['work_details']      ?? null,
            'weight_percentage' => (float) ($_POST['weight_percentage'] ?? 100.00),
            'created_by'        => (int)   ($_SESSION['user']['id']     ?? 0),
            'status'            => $status,
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

        // ── QS Material Items (Design-type-wise blocks) ───────────────────────
        // Posted as flat arrays indexed per item row:
        // item_design_type_id[], item_work_type_id[], item_sl_no[],
        // item_location[], item_nos[], item_length[], item_width[], item_height[],
        // item_volume[], item_material_id[], item_unit[], item_unit_cost[], item_remarks[]
        $dtIds       = $_POST['item_design_type_id'] ?? [];
        $wtIds       = $_POST['item_work_type_id']   ?? [];
        $phaseItemIds= $_POST['item_phase_item_id']  ?? [];
        $slNos       = $_POST['item_sl_no']          ?? [];
        $locations   = $_POST['item_location']        ?? [];
        $nosArr      = $_POST['item_nos']             ?? [];
        $lengths     = $_POST['item_length']          ?? [];
        $widths      = $_POST['item_width']           ?? [];
        $heights     = $_POST['item_height']          ?? [];
        $volumes     = $_POST['item_volume']          ?? [];
        $matIds      = $_POST['item_material_id']     ?? [];
        $units       = $_POST['item_unit']            ?? [];
        $costs       = $_POST['item_unit_cost']       ?? [];
        $remarks     = $_POST['item_remarks']         ?? [];

        $items = [];
        foreach ($dtIds as $i => $dtId) {
            if (empty($dtId) && empty($matIds[$i])) continue;
            $volQty = (float)($volumes[$i] ?? 0);
            if (!$volQty) {
                $nos = (float)($nosArr[$i] ?? 1) ?: 1;
                $l   = (float)($lengths[$i] ?? 1) ?: 1;
                $w   = (float)($widths[$i]  ?? 1) ?: 1;
                $h   = (float)($heights[$i] ?? 1) ?: 1;
                $volQty = $nos * $l * $w * $h;
            }
            $items[] = [
                'design_type_id'  => (int)   $dtId,
                'work_type_id'    => (int)  ($wtIds[$i]     ?? 0),
                'phase_item_id'   => (int)  ($phaseItemIds[$i] ?? 0) ?: null,
                'sl_no'           => (int)  ($slNos[$i]     ?? 0),
                'location_desc'   =>         $locations[$i] ?? '',
                'nos'             => (float)($nosArr[$i]    ?? null),
                'length_ft'       => (float)($lengths[$i]   ?? null),
                'width_ft'        => (float)($widths[$i]    ?? null),
                'height_ft'       => (float)($heights[$i]   ?? null),
                'volume_qty'      => $volQty,
                'material_id'     => (int)  ($matIds[$i]    ?? 0) ?: null,
                'material_unit'   =>         $units[$i]     ?? '',
                'estimated_qty'   => $volQty,
                'unit_cost_approx'=> (float)($costs[$i]     ?? 0),
                'remarks'         =>         $remarks[$i]   ?? '',
            ];
        }
        if (!empty($items)) {
            $this->model->addItems($budgetId, $items);
        }

        // ── Labor Rows ───────────────────────────────────────────────────────
        $labDtIds        = $_POST['labor_design_type_id'] ?? [];
        $labWtIds        = $_POST['labor_work_type_id']   ?? [];
        $labPhaseItemIds = $_POST['labor_phase_item_id']   ?? [];
        $labTypeNames    = $_POST['labor_type_name']       ?? [];
        $labRates        = $_POST['labor_rate_per_sqft']   ?? [];
        $labAreas        = $_POST['labor_area_sqft']       ?? [];
        $labDescriptions = $_POST['labor_description']     ?? [];

        $labors = [];
        foreach ($labTypeNames as $i => $name) {
            if (empty($name)) continue;
            $labors[] = [
                'design_type_id'  => (int)  ($labDtIds[$i]       ?? 0),
                'work_type_id'    => (int)  ($labWtIds[$i]       ?? 0),
                'phase_item_id'   => (int)  ($labPhaseItemIds[$i]  ?? 0) ?: null,
                'labor_type_name' =>          $name,
                'rate_per_sqft'   => (float)($labRates[$i]       ?? 0),
                'area_sqft'       => (float)($labAreas[$i]       ?? 0),
                'description'     =>          $labDescriptions[$i] ?? '',
            ];
        }
        if (!empty($labors)) {
            $this->model->addLabors($budgetId, $labors);
        }

        // ── Sync totals ──────────────────────────────────────────────────────
        $this->model->syncTotals($budgetId);

        // ── Email only on submit (not draft) ─────────────────────────────────
        if ($status === 'pending') {
            require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
            $mailService = new \ProjectWorkflowMailService($this->db);
            $projectModel = new \Project($this->db);
            $project = $projectModel->find($header['project_id']);
            if ($project) {
                $mailService->notifyApproversForNewInitialBudget($header['project_id'], $project['name'], $budgetId);
            }
            setSuccess('Estimate submitted successfully and sent for approval.');
        } else {
            setSuccess('Estimate saved as draft. You can continue editing anytime.');
        }

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

        // Load phase monthly targets linked to this project as working plans
        $projectId = (int)($budget['project_id'] ?? 0);
        $workingPlans = [];
        if ($projectId > 0) {
            $stmt = $this->db->prepare("
                SELECT pmt.id,
                       pmt.target_month_number AS month,
                       pmt.target_year AS year,
                       pp.phase_name AS design_type_name,
                       pmt.responsible_team AS work_description,
                       pmt.total_target_weight_pct AS planned_quantity,
                       0.00 AS estimated_cost,
                       pmt.planning_status AS approved_status,
                       u.full_name AS created_by_name
                FROM phase_monthly_targets pmt
                INNER JOIN project_phases pp ON pp.id = pmt.phase_id
                LEFT JOIN users u ON u.id = pmt.created_by
                WHERE pmt.project_id = ?
                ORDER BY pmt.target_year DESC, pmt.target_month_number DESC
            ");
            $stmt->bind_param('i', $projectId);
            $stmt->execute();
            $workingPlans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $monthlyPlanSummary = [];

        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        $currentRoleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // ── Approval permission ──────────────────────────────────────────────
        // Roles 1 (MD), 3 (Director), 15 (PM), 22 (PM Head) can approve
        $approverRoles = [1, 3, 15, 22];
        $createdBy     = (int)($budget['created_by'] ?? 0);
        $canApprove    = in_array($currentRoleId, $approverRoles);

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
            'current_route'      => 'con-estimates',
            'page_title'         => 'Estimate Detail — ' . $budget['project_name'],
            'budget'             => $budget,
            'items'              => $items,
            'labors'             => $labors,
            'breakdown'          => $breakdown,
            'locked'             => $this->model->isLocked($id),
            'workingPlans'       => $workingPlans,
            'monthlyPlanSummary' => $monthlyPlanSummary,
            'currentUserId'      => $currentUserId,
            'canApprove'         => $canApprove,
            'creatorInfo'        => $creatorInfo,
        ]);
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

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

        $items    = $this->model->getItemsByBudget($id);
        $labors   = $this->model->getLaborsByBudget($id);
        $projects = $this->projectModel->getAll();
        $archs    = $this->archModel->getAll();
        $materials= $this->materialModel->getAll();

        // Work types grouped by design_type_id (same as create)
        $workTypesGrouped = $this->model->getWorkTypesGrouped();

        // All design types
        $designTypes = $this->designTypeModel->getAll();

        // Project drawings for the budget's project
        $pid   = (int)$budget['project_id'];
        $stmt  = $this->db->prepare(
            "SELECT pd.id, pd.design_type_id, pd.phase_id, pd.design_link, pd.version, pd.status,
                    dt.name AS design_type,
                    pp.phase_name,
                    pp.weight_percentage AS phase_weight
             FROM project_drawings pd
             LEFT JOIN design_types dt ON dt.id = pd.design_type_id
             LEFT JOIN project_phases pp ON pp.id = pd.phase_id
             WHERE pd.project_id = ?
             ORDER BY pd.design_type_id ASC, pd.id DESC"
        );
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        $projectDrawings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Deduplicate by design_type_id (keep first/latest)
        $drawingsMap = [];
        foreach ($projectDrawings as $d) {
            if (!isset($drawingsMap[$d['design_type_id']])) {
                $drawingsMap[$d['design_type_id']] = $d;
            }
        }
        $projectDrawings = array_values($drawingsMap);

        // Load project phase items
        $phaseItemModel = new \Con_PhaseItem($this->db);
        $projectPhaseItems = $phaseItemModel->getByProject($pid);

        // Load timeline
        $stmtTl = $this->db->prepare("SELECT project_start_date, construction_start_date, construction_end_date, expected_handover_date FROM project_timelines WHERE project_id = ? LIMIT 1");
        $stmtTl->bind_param('i', $pid);
        $stmtTl->execute();
        $projectTimeline = $stmtTl->get_result()->fetch_assoc() ?: null;

        // Group existing items by design_type_id then composite phaseItemId_wtId key
        $itemsByDt = [];
        foreach ($items as $item) {
            $dtId = $item['design_type_id'] ?? 0;
            $phaseItemId = $item['phase_item_id'] ?? 0;
            $wtId = $item['work_type_id']   ?? 0;
            $compositeKey = $phaseItemId . '_' . $wtId;
            if (!isset($itemsByDt[$dtId])) $itemsByDt[$dtId] = [];
            if (!isset($itemsByDt[$dtId][$compositeKey])) $itemsByDt[$dtId][$compositeKey] = [];
            $itemsByDt[$dtId][$compositeKey][] = $item;
        }

        // Group existing labors by design_type_id
        $laborsByDt = [];
        foreach ($labors as $l) {
            $dtId = $l['design_type_id'] ?? 0;
            if (!isset($laborsByDt[$dtId])) $laborsByDt[$dtId] = [];
            $laborsByDt[$dtId][] = $l;
        }

        $this->view('phase2/initial_estimates/edit-initial-budget', [
            'current_route'   => 'con-estimates',
            'page_title'      => 'Edit Estimate — ' . $budget['project_name'],
            'budget'          => $budget,
            'projects'        => $projects,
            'archs'           => $archs,
            'designTypes'     => $designTypes,
            'materials'       => $materials,
            'workTypesGrouped'=> $workTypesGrouped,
            'projectDrawings' => $projectDrawings,
            'projectPhaseItems' => $projectPhaseItems,
            'projectTimeline' => $projectTimeline,
            'itemsByDt'       => $itemsByDt,
            'laborsByDt'      => $laborsByDt,
        ]);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($this->model->isLocked($id)) {
            setError('This budget is approved or cancelled and cannot be edited.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

        // Status: draft or pending
        $submitAction = $_POST['submit_action'] ?? 'draft';
        $status       = ($submitAction === 'submit') ? 'pending' : 'draft';

        // ── Header ──────────────────────────────────────────────────────────
        $header = [
            'project_id'        => (int)   ($_POST['project_id']        ?? 0),
            'architecture_id'   => (int)   ($_POST['architecture_id']   ?? 0),
            'drawing_id'        => (int)   ($_POST['drawing_id']        ?? 0) ?: null,
            'work_type_id'      => null,
            'start_date'        => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date'          => !empty($_POST['end_date'])   ? $_POST['end_date']   : null,
            'total_area_sqft'   => (float) ($_POST['total_area_sqft']   ?? 0),
            'other_costs'       => (float) ($_POST['other_costs']       ?? 0),
            'other_cost_detail' =>          $_POST['other_cost_detail'] ?? null,
            'budget_link'       =>          $_POST['budget_link']       ?? null,
            'work_details'      =>          $_POST['work_details']      ?? null,
            'weight_percentage' => (float) ($_POST['weight_percentage'] ?? 100.00),
            'status'            => $status,
        ];

        if (!$header['project_id'] || !$header['architecture_id']) {
            setError('Project and Architecture are required.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-edit/{$id}");
            exit;
        }

        $this->model->updateBudget($id, $header);

        // ── Replace items (delete all → re-insert QS rows) ───────────────
        $this->model->deleteItemsByBudget($id);

        $dtIds        = $_POST['item_design_type_id'] ?? [];
        $wtIds        = $_POST['item_work_type_id']   ?? [];
        $phaseItemIds = $_POST['item_phase_item_id']  ?? [];
        $slNos        = $_POST['item_sl_no']          ?? [];
        $locations    = $_POST['item_location_desc']  ?? [];
        $nosArr       = $_POST['item_nos']            ?? [];
        $lengths      = $_POST['item_length_ft']      ?? [];
        $widths       = $_POST['item_width_ft']       ?? [];
        $heights      = $_POST['item_height_ft']      ?? [];
        $matIds       = $_POST['item_material_id']    ?? [];
        $matUnits     = $_POST['item_material_unit']  ?? [];
        $estQtys      = $_POST['item_estimated_qty']  ?? [];
        $unitCosts    = $_POST['item_unit_cost']      ?? [];
        $remarks      = $_POST['item_remarks']        ?? [];

        $items = [];
        foreach ($matIds as $i => $mid) {
            if (empty($mid)) continue;
            $nos = (float)($nosArr[$i] ?? 0);
            $l   = (float)($lengths[$i] ?? 0);
            $w   = (float)($widths[$i]  ?? 0);
            $h   = (float)($heights[$i] ?? 0);
            $vol = ($nos * $l * $w * $h) ?: ((float)($estQtys[$i] ?? 0));
            $items[] = [
                'design_type_id'  => (int)($dtIds[$i]  ?? 0),
                'work_type_id'    => (int)($wtIds[$i]  ?? 0),
                'phase_item_id'   => (int)($phaseItemIds[$i] ?? 0) ?: null,
                'sl_no'           => (int)($slNos[$i]  ?? 0) ?: null,
                'location_desc'   => $locations[$i] ?? null,
                'nos'             => $nos ?: null,
                'length_ft'       => $l   ?: null,
                'width_ft'        => $w   ?: null,
                'height_ft'       => $h   ?: null,
                'volume_qty'      => $vol ?: null,
                'material_id'     => (int)$mid,
                'material_unit'   => $matUnits[$i] ?? '',
                'estimated_qty'   => (float)($estQtys[$i]  ?? 0),
                'unit_cost_approx'=> (float)($unitCosts[$i] ?? 0),
                'remarks'         => $remarks[$i] ?? '',
            ];
        }
        if (!empty($items)) $this->model->addItems($id, $items);

        // ── Replace labors (delete all → re-insert) ──────────────────────
        $this->model->deleteLaborsByBudget($id);

        $labDtIds       = $_POST['labor_design_type_id'] ?? [];
        $labWtIds       = $_POST['labor_work_type_id']   ?? [];
        $labPhaseItemIds = $_POST['labor_phase_item_id'] ?? [];
        $labNames       = $_POST['labor_type_name']      ?? [];
        $labRates       = $_POST['labor_rate_per_sqft']  ?? [];
        $labAreas       = $_POST['labor_area_sqft']      ?? [];
        $labDescs       = $_POST['labor_description']    ?? [];

        $labors = [];
        foreach ($labNames as $i => $name) {
            if (empty($name)) continue;
            $labors[] = [
                'design_type_id'  => (int)($labDtIds[$i] ?? 0),
                'work_type_id'    => (int)($labWtIds[$i] ?? 0),
                'phase_item_id'   => (int)($labPhaseItemIds[$i] ?? 0) ?: null,
                'labor_type_name' =>       $name,
                'rate_per_sqft'   => (float)($labRates[$i] ?? 0),
                'area_sqft'       => (float)($labAreas[$i] ?? 0),
                'description'     =>       $labDescs[$i] ?? '',
            ];
        }
        if (!empty($labors)) $this->model->addLabors($id, $labors);

        // ── Sync totals ──────────────────────────────────────────────────
        $this->model->syncTotals($id);

        $msg = ($status === 'draft') ? 'Draft saved successfully.' : 'Estimate submitted for approval.';
        setSuccess($msg);
        header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
        exit;
    }

    // =========================================================================
    // APPROVE
    // =========================================================================

    public function approveBudget($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id']      ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Only PM (15,22) and Management (3) can approve
        if (!in_array($roleId, [3, 15, 22])) {
            setError('You do not have permission to approve estimates.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

        $budget = $this->model->find($id);
        if (!$budget) {
            setError('Estimate not found.');
            header("Location: {$GLOBALS['base_url']}/con-estimates");
            exit;
        }

        $status = $budget['status'] ?? '';

        // ── PM Approval: pending → pm_approved ──────────────────────────
        if ($status === 'pending') {
            if (!in_array($roleId, [15, 22])) {
                setError('শুধুমাত্র PM (role 15/22) pending estimate approve করতে পারবেন।');
                header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
                exit;
            }
            if ($this->model->pmApproveBudget($id, $userId)) {
                setSuccess('✅ PM Approved — এখন Management Final Approval বাকি।');
            } else {
                setError('PM Approval failed. Estimate may not be in pending state.');
            }

        // ── Management Final Approval: pm_approved → approved ───────────
        } elseif ($status === 'pm_approved') {
            if ($roleId !== 3) {
                setError('শুধুমাত্র Management (role 3) Final Approve করতে পারবেন।');
                header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
                exit;
            }
            if ($this->model->approveBudget($id, $userId)) {
                setSuccess('✅ Estimate Fully Approved by Management.');
            } else {
                setError('Final approval failed. Estimate must be PM-approved first.');
            }

        } else {
            setError('এই estimate approve করা সম্ভব না। Current status: ' . htmlspecialchars($status));
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

        // Role guard: only 1, 3, 15, 22 can reject
        if (!in_array($roleId, [1, 3, 22])) {
            setError('You do not have permission to reject budgets.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

        // Creator cannot reject their own submission (except PM Head/MD)
        $budget = $this->model->find($id);
        if ($budget && (int)($budget['created_by'] ?? 0) === $userId && !in_array($roleId, [1, 3, 22])) {
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
            
            // Trigger rejection email
            require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
            $mailService = new \ProjectWorkflowMailService($this->db);
            $b = $this->model->find($id);
            if ($b && !empty($b['project_id'])) {
                $mailService->notifyAfterBudgetRejection((int)$b['project_id'], $b['project_name'] ?? 'Project', $id, $note);
            }
        } else {
            setError('Cancellation failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
        exit;
    }

    // =========================================================================
    // PRINT VIEW (standalone — no sidebar/header)
    // =========================================================================

    public function printView($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $budget = $this->model->find($id);
        if (!$budget) {
            die('Budget not found.');
        }

        $items     = $this->model->getItemsByBudget($id);
        $labors    = $this->model->getLaborsByBudget($id);
        $breakdown = $this->model->getFullBreakdownByDesignType($id);

        // Load phase monthly targets linked to this project as working plans
        $projectId = (int)($budget['project_id'] ?? 0);
        $workingPlans = [];
        if ($projectId > 0) {
            $stmt = $this->db->prepare("
                SELECT pmt.id,
                       pmt.target_month_number AS month,
                       pmt.target_year AS year,
                       pp.phase_name AS design_type_name,
                       pmt.responsible_team AS work_description,
                       pmt.total_target_weight_pct AS planned_quantity,
                       0.00 AS estimated_cost,
                       pmt.planning_status AS approved_status,
                       u.full_name AS created_by_name
                FROM phase_monthly_targets pmt
                INNER JOIN project_phases pp ON pp.id = pmt.phase_id
                LEFT JOIN users u ON u.id = pmt.created_by
                WHERE pmt.project_id = ?
                ORDER BY pmt.target_year DESC, pmt.target_month_number DESC
            ");
            $stmt->bind_param('i', $projectId);
            $stmt->execute();
            $workingPlans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $base_url   = $GLOBALS['base_url'];
        $page_title = 'Initial Estimate — ' . ($budget['project_name'] ?? 'Print');

        require_once dirname(__DIR__, 2) . '/views/phase2/initial_estimates/print-initial-budget.php';
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
            echo json_encode(['archs' => [], 'drawings' => [], 'phase_items' => []]);
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

        // Project Drawings — all statuses (Submitted + Approved both shown)
        $stmt2 = $this->db->prepare(
            "SELECT pd.id,
                    pd.design_type_id,
                    pd.phase_id,
                    pd.design_link,
                    pd.version,
                    pd.status,
                    dt.name AS design_type,
                    u.full_name AS uploaded_by,
                    pd.created_at,
                    pp.phase_name,
                    pp.weight_percentage AS phase_weight
             FROM project_drawings pd
             LEFT JOIN design_types dt ON dt.id = pd.design_type_id
             LEFT JOIN users        u  ON u.id  = pd.created_by
             LEFT JOIN project_phases pp ON pp.id = pd.phase_id
             WHERE pd.project_id = ?
             ORDER BY pd.design_type_id ASC, pd.id DESC"
        );
        $stmt2->bind_param('i', $projectId);
        $stmt2->execute();
        $drawings = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

        // Load project phase items
        $phaseItemModel = new \Con_PhaseItem($this->db);
        $phaseItems = $phaseItemModel->getByProject($projectId);

        // Load timeline
        $stmtTl = $this->db->prepare("SELECT project_start_date, construction_start_date, construction_end_date, expected_handover_date FROM project_timelines WHERE project_id = ? LIMIT 1");
        $stmtTl->bind_param('i', $projectId);
        $stmtTl->execute();
        $timeline = $stmtTl->get_result()->fetch_assoc() ?: null;

        echo json_encode(['archs' => $archs, 'drawings' => $drawings, 'phase_items' => $phaseItems, 'timeline' => $timeline]);
        exit;
    }

    // =========================================================================
    // CSV IMPORT  (Budget + Items + Labors — one flat CSV)
    // POST /con-estimate-import
    // =========================================================================
    public function importCsv()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
            exit;
        }

        // ── Sample CSV download ───────────────────────────────────────────────
        if (isset($_GET['sample'])) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="initial_estimate_sample.csv"');
            echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
            echo "Row_Type,Project_Name,Work_Type_Name,Total_Area_Sqft,Other_Costs,Start_Date,End_Date,Material_Name,Material_Unit,Estimated_Qty,Unit_Cost,Item_Remarks,Labor_Type_Name,Rate_Per_Sqft,Area_Sqft,Labor_Description\n";
            echo "BUDGET,Ashuliya Model Town,Brick Work,1200.00,5000,2025-01-01,2025-06-30,,,,,,,,,\n";
            echo "ITEM,,,,,,,,,Rod (12mm),kg,500,85.00,Main structure,,,,\n";
            echo "ITEM,,,,,,,,Cement (50kg bag),bag,120,650.00,Foundation,,,,\n";
            echo "LABOR,,,,,,,,,,,,,Mason,45.00,1200.00,Ground floor masonry\n";
            echo "LABOR,,,,,,,,,,,,,Helper,25.00,1200.00,Support work\n";
            echo "BUDGET,Mirpur Housing Project,,800.00,2000,2025-03-01,2025-09-30,,,,,,,,,\n";
            echo "ITEM,,,,,,,,Sand (cubic ft),cft,300,120.00,,,,,\n";
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
            exit;
        }

        // ── Read file with UTF-8 support (Bengali fix) ────────────────────────
        $raw = file_get_contents($_FILES['csv_file']['tmp_name']);
        
        // Strip UTF-8 BOM if present (exactly like Con_MaterialController)
        $raw = ltrim($raw, "\xEF\xBB\xBF");
        
        // Normalize line endings
        $raw = str_replace("\r\n", "\n", $raw);
        $raw = str_replace("\r", "\n", $raw);
        
        $lines = array_filter(explode("\n", $raw), fn($l) => trim($l) !== '');
        $lines = array_values($lines);

        if (empty($lines)) {
            setError('CSV file is empty.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
            exit;
        }

        // Parse header row
        $headerRow = array_map('trim', str_getcsv(array_shift($lines)));
        if (empty($headerRow) || !in_array('Row_Type', $headerRow)) {
            setError('Invalid CSV format. First column must include "Row_Type". Download the sample CSV.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
            exit;
        }

        $db     = $this->db;
        $db->set_charset('utf8mb4'); // Ensure connection uses UTF-8 for Bengali support
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        $successBudgets = 0;
        $successItems   = 0;
        $successLabors  = 0;
        $errorCount     = 0;
        $errors         = [];

        // Current budget state (carried across ITEM/LABOR rows)
        $currentBudgetId  = null;
        $currentProjectId = null;

        // ── Helper: resolve project_id ────────────────────────────────────────
        $resolveProject = function (string $name) use ($db): ?int {
            $name = trim($name);
            if (!$name) return null;
            $s = $db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1");
            $s->bind_param('s', $name);
            $s->execute();
            $r = $s->get_result()->fetch_assoc();
            return $r ? (int)$r['id'] : null;
        };

        // ── Helper: resolve architecture_id from project_id ──────────────────
        $resolveArch = function (int $projectId) use ($db): int {
            $s = $db->prepare("SELECT id FROM architect_engineering WHERE project_id = ? ORDER BY id ASC LIMIT 1");
            $s->bind_param('i', $projectId);
            $s->execute();
            $r = $s->get_result()->fetch_assoc();
            return $r ? (int)$r['id'] : 0;
        };

        // ── Helper: resolve work_type_id ──────────────────────────────────────
        $resolveWorkType = function (string $name) use ($db): ?int {
            $name = trim($name);
            if (!$name) return null;
            $s = $db->prepare("SELECT id FROM work_types WHERE work_type_name = ? LIMIT 1");
            $s->bind_param('s', $name);
            $s->execute();
            $r = $s->get_result()->fetch_assoc();
            return $r ? (int)$r['id'] : null;
        };

        // ── Helper: resolve material_id and unit ─────────────────────────────
        $resolveMaterial = function (string $name) use ($db): ?array {
            $name = trim($name);
            if (!$name) return null;
            $s = $db->prepare("SELECT id, unit FROM con_materials WHERE name = ? LIMIT 1");
            $s->bind_param('s', $name);
            $s->execute();
            $r = $s->get_result()->fetch_assoc();
            return $r ?: null;
        };

        // ── Helper: resolve design_type_id from work_type_id ─────────────────
        $resolveDesignType = function (?int $wtId) use ($db): int {
            if (!$wtId) return 0;
            $s = $db->prepare("SELECT design_type_id FROM work_types WHERE id = ? LIMIT 1");
            $s->bind_param('i', $wtId);
            $s->execute();
            $r = $s->get_result()->fetch_assoc();
            return $r ? (int)($r['design_type_id'] ?? 0) : 0;
        };

        // ── Helper: resolve user_id from email ─────────────────────────────
        $resolveUserByEmail = function (string $email) use ($db): ?int {
            $email = trim($email);
            if (!$email) return null;
            $s = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $s->bind_param('s', $email);
            $s->execute();
            $r = $s->get_result()->fetch_assoc();
            return $r ? (int)$r['id'] : null;
        };

        // ── Process rows ──────────────────────────────────────────────────────
        foreach ($lines as $lineNo => $line) {
            $line = trim($line);
            if ($line === '') continue;

            $cols = array_map('trim', str_getcsv($line));
            $hCount = count($headerRow);
            // Ensure col count matches header exactly (pad if short, truncate if long)
            if (count($cols) < $hCount) {
                $cols = array_pad($cols, $hCount, '');
            } elseif (count($cols) > $hCount) {
                $cols = array_slice($cols, 0, $hCount);
            }
            $row = array_combine($headerRow, $cols);
            if ($row === false) continue; // safety guard
  
          $rowType = strtoupper(trim($row['Row_Type'] ?? ''));

            // ─ BUDGET row ────────────────────────────────────────────────────
            if ($rowType === 'BUDGET') {
                $projectName  = trim($row['Project_Name']  ?? '');
                $workTypeName = trim($row['Work_Type_Name'] ?? '');

                if (!$projectName) {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNo + 2) . ": Missing Project_Name in BUDGET row";
                    $currentBudgetId = null;
                    continue;
                }

                $projectId = $resolveProject($projectName);
                if (!$projectId) {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNo + 2) . ": Project '$projectName' not found";
                    $currentBudgetId = null;
                    continue;
                }

                $archId = $resolveArch($projectId);

                // Budget-level fields (no work_type_id at budget level)
                $areaSqft        = (float)($row['Total_Area_Sqft']    ?? 0);
                $otherCost       = (float)($row['Other_Costs']         ?? 0);
                $otherCostDetail = trim($row['Other_Cost_Detail']      ?? '');
                $budgetLink      = trim($row['Budget_Link']            ?? '');
                $startDate       = trim($row['Start_Date']             ?? '') ?: null;
                $endDate         = trim($row['End_Date']               ?? '') ?: null;
                $statusVal       = strtolower(trim($row['Status']      ?? 'pending'));
                $statusVal       = in_array($statusVal, ['pending','approved','cancelled']) ? $statusVal : 'pending';
                $approvalDate    = trim($row['Approval_Date']          ?? '') ?: null;

                // Resolve approved_by from email
                $approvedByEmail = trim($row['Approved_By_Email'] ?? '');
                $approvedById    = $approvedByEmail ? $resolveUserByEmail($approvedByEmail) : null;

                $ins = $db->prepare("
                    INSERT INTO initial_estimate_budgets
                        (project_id, architecture_id, total_area_sqft,
                         other_costs, other_cost_detail, budget_link, work_details,
                         start_date, end_date,
                         status, approved_by, approval_date,
                         created_by,
                         material_cost_total, labor_cost_total, total_estimated_cost)
                    VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, 0, 0, 0)
                ");

                if (!$ins) {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNo + 2) . ": DB prepare error: " . $db->error;
                    $currentBudgetId = null;
                    continue;
                }

                $ins->bind_param('iiddssssssi',
                    $projectId, $archId, $areaSqft,
                    $otherCost, $otherCostDetail, $budgetLink,
                    $startDate, $endDate,
                    $statusVal, $approvedById, $approvalDate,
                    $userId
                );

                if ($ins->execute()) {
                    $currentBudgetId  = (int)$db->insert_id;
                    $currentProjectId = $projectId;
                    $successBudgets++;
                } else {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNo + 2) . ": Budget insert failed: " . $ins->error;
                    $currentBudgetId = null;
                }

            // ─ ITEM row ──────────────────────────────────────────────────────
            } elseif ($rowType === 'ITEM') {
                if (!$currentBudgetId) {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNo + 2) . ": ITEM row has no active BUDGET above it";
                    continue;
                }

                $materialName = trim($row['Material_Name'] ?? '');
                if (!$materialName) {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNo + 2) . ": Missing Material_Name in ITEM row";
                    continue;
                }

                $mat = $resolveMaterial($materialName);
                if (!$mat) {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNo + 2) . ": Material '$materialName' not found in con_materials";
                    continue;
                }

                $materialId   = (int)$mat['id'];
                $materialUnit = trim($row['Material_Unit'] ?? '') ?: $mat['unit'];
                $qty          = (float)($row['Estimated_Qty'] ?? 0);
                $unitCost     = (float)($row['Unit_Cost']     ?? 0);
                $remarks      = trim($row['Item_Remarks']     ?? '');

                // design_type_id from ITEM's work_type
                $workTypeName = trim($row['Work_Type_Name'] ?? '');
                $wtId = $resolveWorkType($workTypeName);
                $dtId = $resolveDesignType($wtId);

                // fallback: first design_type
                if (!$dtId) {
                    $r = $db->query("SELECT id FROM design_types ORDER BY id ASC LIMIT 1");
                    $dtId = $r ? (int)($r->fetch_assoc()['id'] ?? 1) : 1;
                }

                $ins = $db->prepare("
                    INSERT INTO initial_estimate_items
                        (budget_id, design_type_id, material_id, material_unit,
                         estimated_qty, unit_cost_approx, remarks, work_type_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                if (!$ins) {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNo + 2) . ": Item DB prepare error: " . $db->error;
                    continue;
                }

                $ins->bind_param('iiisddsi',
                    $currentBudgetId, $dtId, $materialId, $materialUnit,
                    $qty, $unitCost, $remarks, $wtId
                );

                if ($ins->execute()) {
                    $successItems++;
                } else {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNo + 2) . ": Item insert failed: " . $ins->error;
                }

            // ─ LABOR row ─────────────────────────────────────────────────────
            } elseif ($rowType === 'LABOR') {
                if (!$currentBudgetId) {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNo + 2) . ": LABOR row has no active BUDGET above it";
                    continue;
                }

                $laborName = trim($row['Labor_Type_Name'] ?? '');
                if (!$laborName) {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNo + 2) . ": Missing Labor_Type_Name in LABOR row";
                    continue;
                }

                $rate  = (float)($row['Rate_Per_Sqft']     ?? 0);
                $area  = (float)($row['Area_Sqft']          ?? 0);
                $desc  = trim($row['Labor_Description']     ?? '');

                // design_type_id from LABOR's work_type
                $workTypeName = trim($row['Work_Type_Name'] ?? '');
                $wtId = $resolveWorkType($workTypeName);
                $dtId = $resolveDesignType($wtId);

                if (!$dtId) {
                    $r = $db->query("SELECT id FROM design_types ORDER BY id ASC LIMIT 1");
                    $dtId = $r ? (int)($r->fetch_assoc()['id'] ?? 1) : 1;
                }

                $ins = $db->prepare("
                    INSERT INTO initial_estimate_labors
                        (budget_id, design_type_id, labor_type_name,
                         rate_per_sqft, area_sqft, description, work_type_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                if (!$ins) {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNo + 2) . ": Labor DB prepare error: " . $db->error;
                    continue;
                }

                $ins->bind_param('iisddsi',
                    $currentBudgetId, $dtId, $laborName,
                    $rate, $area, $desc, $wtId
                );

                if ($ins->execute()) {
                    $successLabors++;
                } else {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNo + 2) . ": Labor insert failed: " . $ins->error;
                }

            } else {
                // Unknown row type — skip silently (empty rows etc.)
                if ($rowType !== '') {
                    $errors[] = "Line " . ($lineNo + 2) . ": Unknown Row_Type '$rowType' — skipped";
                }
            }
        }

        // ── Sync totals for ALL budgets that were created in this import ──────
        // Re-fetch budget IDs inserted in this session by querying recent ones
        // (simpler: syncTotals already does UPDATE via SUM from items/labors)
        // We track budgetIds via a helper approach:
        if ($successBudgets > 0) {
            // Sync last N budgets created by this user (safe approach)
            $recent = $db->query("
                SELECT id FROM initial_estimate_budgets
                WHERE created_by = $userId
                ORDER BY id DESC
                LIMIT $successBudgets
            ");
            if ($recent) {
                while ($br = $recent->fetch_assoc()) {
                    $this->model->syncTotals((int)$br['id']);
                }
            }
        }

        // ── Flash result ──────────────────────────────────────────────────────
        $msg = "✅ Imported: $successBudgets budget(s), $successItems item(s), $successLabors labor(s).";
        if ($errorCount > 0) {
            $msg .= " ❌ $errorCount error(s): " . implode(' | ', array_slice($errors, 0, 5));
            setError($msg);
        } else {
            setSuccess($msg);
        }

        header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
        exit;
    }
}

