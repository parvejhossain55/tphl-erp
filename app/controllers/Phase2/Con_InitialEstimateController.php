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
        $spentMap = $this->model->getSpentTotalsGroupedByProject();

        $filter = "1=1";
        if (!in_array($roleId, [1, 3, 33, 22])) {
            $filter = "(pm_id = $userId OR site_engineer_id = $userId OR landman_id = $userId OR struct_engineer_id = $userId OR draftsman_id = $userId OR architect_id = $userId OR estimator_id = $userId OR qc_id = $userId OR operator_id = $userId OR created_by = $userId)";
        }

        $rProjs = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND $filter ORDER BY name ASC");
        $projects = $rProjs ? $rProjs->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/initial_estimates/initial-estimate-lists', [
            'current_route' => 'con-estimates',
            'page_title'    => 'Initial Estimate Budgets',
            'budgets'       => $budgets,
            'spentMap'      => $spentMap,
            'projects'      => $projects,
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $filter = "1=1";
        if (!in_array($roleId, [1, 3, 33, 22])) {
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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
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
        // item_location[], item_floor_tag[], item_nos[], item_length[], item_width[], item_height[],
        // item_volume[], item_material_id[], item_unit[], item_unit_cost[], item_remarks[]
        $dtIds       = $_POST['item_design_type_id'] ?? [];
        $wtIds       = $_POST['item_work_type_id']   ?? [];
        $phaseItemIds= $_POST['item_phase_item_id']  ?? [];
        $slNos       = $_POST['item_sl_no']          ?? [];
        $locations   = $_POST['item_location']        ?? [];
        $floorTags   = $_POST['item_floor_tag']      ?? [];
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
            
            $floorTag = $floorTags[$i] ?? 'Typical_Floor|';
            $parts = explode('|', $floorTag);
            $floorType = !empty($parts[0]) ? $parts[0] : 'Typical_Floor';
            $floorNo = (isset($parts[1]) && $parts[1] !== '') ? (int)$parts[1] : null;

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
                'floor_type'      =>         $floorType,
                'floor_no'        =>         $floorNo,
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
        $labFloorTags    = $_POST['labor_floor_tag']       ?? [];
        $labRates        = $_POST['labor_rate_per_sqft']   ?? [];
        $labAreas        = $_POST['labor_area_sqft']       ?? [];
        $labDescriptions = $_POST['labor_description']     ?? [];

        $labors = [];
        foreach ($labTypeNames as $i => $name) {
            if (empty($name)) continue;
            
            $floorTag = $labFloorTags[$i] ?? 'Typical_Floor|';
            $parts = explode('|', $floorTag);
            $floorType = !empty($parts[0]) ? $parts[0] : 'Typical_Floor';
            $floorNo = (isset($parts[1]) && $parts[1] !== '') ? (int)$parts[1] : null;

            $labors[] = [
                'design_type_id'  => (int)  ($labDtIds[$i]       ?? 0),
                'work_type_id'    => (int)  ($labWtIds[$i]       ?? 0),
                'phase_item_id'   => (int)  ($labPhaseItemIds[$i]  ?? 0) ?: null,
                'labor_type_name' =>          $name,
                'floor_type'      =>          $floorType,
                'floor_no'        =>          $floorNo,
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
        $approverRoles = [1, 3, 33, 15, 22];
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

        $spentTotals = $this->model->getSpentTotals($projectId);
        $spentDetails = $this->model->getSpentDetails($projectId);
        // Calculate totals for tabs in modal
        $materialsTotal = 0.0;
        foreach ($spentDetails['materials'] as $m) {
            $materialsTotal += (float)$m['total_cost'];
        }
        $contractorsTotal = 0.0;
        foreach ($spentDetails['contractors'] as $c) {
            $contractorsTotal += (float)$c['amount_paid'];
        }
        $spentDetails['materials_total'] = $materialsTotal;
        $spentDetails['contractors_total'] = $contractorsTotal;

        // Load project floors
        $stmtFl = $this->db->prepare("SELECT id, floor_no, floor_type, slab_area_sqft FROM project_floors WHERE project_id = ? ORDER BY floor_no ASC");
        $stmtFl->bind_param('i', $projectId);
        $stmtFl->execute();
        $projectFloors = $stmtFl->get_result()->fetch_all(MYSQLI_ASSOC);

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
            'spentTotal'         => $spentTotals['total_spent'],
            'spentDetails'       => $spentDetails,
            'projectFloors'      => $projectFloors,
        ]);
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
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

        // Load project floors
        $stmtFl = $this->db->prepare("SELECT id, floor_no, floor_type, slab_area_sqft FROM project_floors WHERE project_id = ? ORDER BY floor_no ASC");
        $stmtFl->bind_param('i', $pid);
        $stmtFl->execute();
        $projectFloors = $stmtFl->get_result()->fetch_all(MYSQLI_ASSOC);

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
            'projectFloors'   => $projectFloors,
            'itemsByDt'       => $itemsByDt,
            'laborsByDt'      => $laborsByDt,
        ]);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
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
        $floorTags    = $_POST['item_floor_tag']      ?? [];
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
            
            $floorTag = $floorTags[$i] ?? 'Typical_Floor|';
            $parts = explode('|', $floorTag);
            $floorType = !empty($parts[0]) ? $parts[0] : 'Typical_Floor';
            $floorNo = (isset($parts[1]) && $parts[1] !== '') ? (int)$parts[1] : null;

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
                'floor_type'      => $floorType,
                'floor_no'        => $floorNo,
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
        $labFloorTags   = $_POST['labor_floor_tag']      ?? [];
        $labRates       = $_POST['labor_rate_per_sqft']  ?? [];
        $labAreas       = $_POST['labor_area_sqft']      ?? [];
        $labDescs       = $_POST['labor_description']    ?? [];

        $labors = [];
        foreach ($labNames as $i => $name) {
            if (empty($name)) continue;
            
            $floorTag = $labFloorTags[$i] ?? 'Typical_Floor|';
            $parts = explode('|', $floorTag);
            $floorType = !empty($parts[0]) ? $parts[0] : 'Typical_Floor';
            $floorNo = (isset($parts[1]) && $parts[1] !== '') ? (int)$parts[1] : null;

            $labors[] = [
                'design_type_id'  => (int)($labDtIds[$i] ?? 0),
                'work_type_id'    => (int)($labWtIds[$i] ?? 0),
                'phase_item_id'   => (int)($labPhaseItemIds[$i] ?? 0) ?: null,
                'labor_type_name' =>       $name,
                'floor_type'      =>       $floorType,
                'floor_no'        =>       $floorNo,
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

        // Only PM (15,22) and Management (1,3) can approve
        if (!in_array($roleId, [1, 3, 33, 15, 22])) {
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
            if (!in_array($roleId, [1, 3, 33])) {
                setError('শুধুমাত্র Management (role 1/3) Final Approve করতে পারবেন।');
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
        if (!in_array($roleId, [1, 3, 33, 15, 22])) {
            setError('You do not have permission to reject budgets.');
            header("Location: {$GLOBALS['base_url']}/con-estimate-show/{$id}");
            exit;
        }

        // Creator cannot reject their own submission (except PM / PM Head / Management)
        $budget = $this->model->find($id);
        if ($budget && (int)($budget['created_by'] ?? 0) === $userId && !in_array($roleId, [1, 3, 33, 15, 22])) {
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

        // Load project floors
        $projectFloors = [];
        if ($projectId > 0) {
            $stmtFl = $this->db->prepare("SELECT id, floor_no, floor_type, slab_area_sqft FROM project_floors WHERE project_id = ? ORDER BY floor_no ASC");
            $stmtFl->bind_param('i', $projectId);
            $stmtFl->execute();
            $projectFloors = $stmtFl->get_result()->fetch_all(MYSQLI_ASSOC);
        }

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

        // Load project floors
        $stmtFl = $this->db->prepare("SELECT id, floor_no, floor_type, slab_area_sqft FROM project_floors WHERE project_id = ? ORDER BY floor_no ASC");
        $stmtFl->bind_param('i', $projectId);
        $stmtFl->execute();
        $floors = $stmtFl->get_result()->fetch_all(MYSQLI_ASSOC);

        // Fetch project metadata to parse building type if floors are empty
        if (empty($floors)) {
            $stmtProj = $this->db->prepare("SELECT building_type, floor_wise_unit_meta FROM projects WHERE id = ? LIMIT 1");
            $stmtProj->bind_param('i', $projectId);
            $stmtProj->execute();
            $project = $stmtProj->get_result()->fetch_assoc();

            if ($project && !empty($project['building_type'])) {
                // Calculate typical units sum from floor_wise_unit_meta
                $unitMetaSum = 0;
                if (!empty($project['floor_wise_unit_meta'])) {
                    $meta = json_decode($project['floor_wise_unit_meta'], true);
                    $typicalUnits = $meta['typical_floor'] ?? [];
                    foreach ($typicalUnits as $u) {
                        $unitMetaSum += (float)($u['sqft'] ?? 0);
                    }
                }
                if ($unitMetaSum <= 0) {
                    $unitMetaSum = 1500.00; // default fallback
                }

                // Parse building type string (e.g. B+G+9, B2+B1+G+14)
                $str = strtoupper(str_replace(' ', '', $project['building_type']));
                $parts = explode('+', $str);

                $basements = [];
                $ground = false;
                $typicalCount = 0;

                foreach ($parts as $part) {
                    if (preg_match('/^B(\d*)$/', $part, $matches)) {
                        $num = !empty($matches[1]) ? (int)$matches[1] : 1;
                        if (!in_array(-$num, $basements)) {
                            $basements[] = -$num;
                        }
                    } elseif (preg_match('/^(\d+)B$/', $part, $matches)) {
                        $count = (int)$matches[1];
                        for ($i = 1; $i <= $count; $i++) {
                            if (!in_array(-$i, $basements)) {
                                $basements[] = -$i;
                            }
                        }
                    } elseif ($part === 'G') {
                        $ground = true;
                    } else {
                        $num = (int)$part;
                        if ($num > 0) {
                            $typicalCount = $num;
                        }
                    }
                }

                // Ensure lower basements exist
                if (!empty($basements)) {
                    $minB = min($basements);
                    for ($i = -1; $i >= $minB; $i--) {
                        if (!in_array($i, $basements)) {
                            $basements[] = $i;
                        }
                    }
                }
                sort($basements);

                // Insert generated floors into database
                $this->db->begin_transaction();
                try {
                    $ins_stmt = $this->db->prepare("INSERT INTO project_floors (project_id, floor_no, floor_type, unit_area_sqft, extra_sqft, slab_area_sqft, height_ft) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    
                    // 1. Basements
                    foreach ($basements as $bNo) {
                        $floor_type = 'Basement';
                        $unit_area = 0.00;
                        $extra_sqft = $unitMetaSum;
                        $slab_area = $unitMetaSum;
                        $height = 11.0;
                        $ins_stmt->bind_param('iisdddd', $projectId, $bNo, $floor_type, $unit_area, $extra_sqft, $slab_area, $height);
                        $ins_stmt->execute();
                    }

                    // 2. Ground Floor
                    if ($ground) {
                        $bNo = 0;
                        $floor_type = 'Ground_Floor';
                        $unit_area = $unitMetaSum;
                        $extra_sqft = 0.00;
                        $slab_area = $unitMetaSum;
                        $height = 10.0;
                        $ins_stmt->bind_param('iisdddd', $projectId, $bNo, $floor_type, $unit_area, $extra_sqft, $slab_area, $height);
                        $ins_stmt->execute();
                    }

                    // 3. Typical Floors
                    if ($typicalCount > 0) {
                        for ($i = 1; $i <= $typicalCount; $i++) {
                            $floor_type = 'Typical_Floor';
                            $unit_area = $unitMetaSum;
                            $extra_sqft = 0.00;
                            $slab_area = $unitMetaSum;
                            $height = 10.0;
                            $ins_stmt->bind_param('iisdddd', $projectId, $i, $floor_type, $unit_area, $extra_sqft, $slab_area, $height);
                            $ins_stmt->execute();
                        }
                    }

                    $this->db->commit();

                    // Re-fetch floors from database
                    $stmtFl->execute();
                    $floors = $stmtFl->get_result()->fetch_all(MYSQLI_ASSOC);

                } catch (\Exception $e) {
                    $this->db->rollback();
                }
            }
        }

        echo json_encode(['archs' => $archs, 'drawings' => $drawings, 'phase_items' => $phaseItems, 'timeline' => $timeline, 'floors' => $floors]);
        exit;
    }

    // =========================================================================
    // CSV IMPORT  (Budget + Items + Labors — one flat CSV)
    // POST /con-estimate-import
    // =========================================================================
    public function importCsv()
    {
        // ── Sample CSV download ───────────────────────────────────────────────
        if (isset($_GET['sample'])) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="initial_estimate_sample.csv"');
            echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
            echo "Row_Type,Project_Name,Estimator_Email,Approved_By_Email,Total_Area_Sqft,Other_Costs,Other_Cost_Detail,Drive_Link,Start_Date,End_Date,Work_Type_Name,Default_Phase_Name,Phase_Item_Name,Material_Name,Material_Unit,Estimated_Qty,Unit_Cost,Item_Remarks,Labor_Type_Name,Rate_Per_Sqft,Area_Sqft,Labor_Description,Floor_Type,Floor_No,Location\n";
            echo "BUDGET,test project from developer2,estimator1@gmail.com,project.manager1@gmail.com,1200.00,5000,Fencing and security,https://drive.google.com/...,2026-01-01,2026-06-30,,,,,,,,,,,,,,,\n";
            echo "ITEM,,,,,,,,,,Brick Work,Structure,Foundation Slab,Rod (12mm),kg,500,85.00,Main structure,,,,,Typical_Floor,0,Basement Lift Core\n";
            echo "ITEM,,,,,,,,,,Plaster Work,Structure,Superstructure,Cement (50kg bag),bag,120,650.00,Foundation,,,,,Typical_Floor,0,Basement Wall\n";
            echo "LABOR,,,,,,,,,,Mason Work,Structure,Foundation Slab,,,,,,Mason,45.00,1200.00,Ground floor masonry,,,\n";
            echo "LABOR,,,,,,,,,,Helper Work,Structure,Superstructure,,,,,,Helper,25.00,1200.00,Support work,,,\n";
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
            exit;
        }

        // ── Read file with UTF-8 support (Bengali fix) ────────────────────────
        $raw = file_get_contents($_FILES['csv_file']['tmp_name']);
        
        // Strip UTF-8 BOM if present
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

        $db = $this->db;
        $db->set_charset('utf8mb4'); // Ensure connection uses UTF-8 for Bengali support

        $csvErrors = [];
        $parsedRows = [];

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

        // ── Helper: check if architect engineering exists ──────────────────
        $hasArchitectSetup = function (int $projectId) use ($db): bool {
            $s = $db->prepare("SELECT id FROM architect_engineering WHERE project_id = ? LIMIT 1");
            $s->bind_param('i', $projectId);
            $s->execute();
            return (bool)$s->get_result()->fetch_assoc();
        };

        // ── Phase 1: Parse and Validate all rows (First Pass) ────────────────
        $currentProjectName = null;
        $currentProjectId = null;
        $hasActiveBudget = false;
        $budgetExists = false;
        $lastBudgetLineNo = null;

        foreach ($lines as $lineNo => $line) {
            $line = trim($line);
            if ($line === '') continue;

            $cols = array_map('trim', str_getcsv($line));
            $hCount = count($headerRow);
            if (count($cols) < $hCount) {
                $cols = array_pad($cols, $hCount, '');
            } elseif (count($cols) > $hCount) {
                $cols = array_slice($cols, 0, $hCount);
            }
            $row = array_combine($headerRow, $cols);
            if ($row === false) continue;

            $actualLineNo = $lineNo + 2; // 1-indexed plus header row offset
            $row['__line_no'] = $actualLineNo;
            $rowType = strtoupper(trim($row['Row_Type'] ?? ''));

            if ($rowType === 'BUDGET') {
                $budgetExists = true;
                $lastBudgetLineNo = $actualLineNo;
                $projectName = trim($row['Project_Name'] ?? '');
                $estimatorEmail = trim($row['Estimator_Email'] ?? '');
                $approvedByEmail = trim($row['Approved_By_Email'] ?? '');

                if (!$projectName) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Project_Name', 'message' => 'Project Name is required for BUDGET.'];
                    $hasActiveBudget = false;
                    continue;
                }

                $projectId = $resolveProject($projectName);
                if (!$projectId) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Project_Name', 'message' => "Project '$projectName' not found in ERP."];
                    $hasActiveBudget = false;
                    continue;
                }

                if (!$hasArchitectSetup($projectId)) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Project_Name', 'message' => "Project '$projectName' has no Architect Engineering setup."];
                    $hasActiveBudget = false;
                    continue;
                }

                if (!$estimatorEmail) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Estimator_Email', 'message' => 'Estimator Email is required for BUDGET.'];
                    $hasActiveBudget = false;
                    continue;
                }

                $estimatorId = $resolveUserByEmail($estimatorEmail);
                if (!$estimatorId) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Estimator_Email', 'message' => "Estimator email '$estimatorEmail' not found in ERP."];
                    $hasActiveBudget = false;
                    continue;
                }

                if ($approvedByEmail) {
                    $approvedById = $resolveUserByEmail($approvedByEmail);
                    if (!$approvedById) {
                        $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Approved_By_Email', 'message' => "Approved By email '$approvedByEmail' not found in ERP."];
                        $hasActiveBudget = false;
                        continue;
                    }
                }

                $currentProjectName = $projectName;
                $currentProjectId = $projectId;
                $hasActiveBudget = true;
                $parsedRows[] = ['type' => 'BUDGET', 'data' => $row, 'project_id' => $projectId];

            } elseif ($rowType === 'ITEM') {
                if (!$hasActiveBudget) {
                    if ($budgetExists) {
                        $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Row_Type', 'message' => "ITEM row cannot be imported because the BUDGET row defined above it (Row $lastBudgetLineNo) has validation errors."];
                    } else {
                        $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Row_Type', 'message' => 'ITEM row does not have any BUDGET row defined above it. Every CSV must start with a BUDGET row.'];
                    }
                    continue;
                }

                $materialName = trim($row['Material_Name'] ?? '');
                $workTypeName = trim($row['Work_Type_Name'] ?? '');
                $phaseItemName = trim($row['Phase_Item_Name'] ?? '');
                $qty = $row['Estimated_Qty'] ?? '';
                $unitCost = $row['Unit_Cost'] ?? '';
                $unit = trim($row['Material_Unit'] ?? '');

                if (!$materialName) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Material_Name', 'message' => 'Material Name is required for ITEM.'];
                }
                if (!$unit) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Material_Unit', 'message' => 'Material Unit is required for ITEM.'];
                }
                if (!$workTypeName) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Work_Type_Name', 'message' => 'Work Type Name is required for ITEM.'];
                }
                if (!$phaseItemName) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Phase_Item_Name', 'message' => 'Phase Item Name is required for ITEM.'];
                }
                if ($qty === '' || !is_numeric($qty) || (float)$qty <= 0) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Estimated_Qty', 'message' => 'Estimated Qty must be a positive number.'];
                }
                if ($unitCost === '' || !is_numeric($unitCost) || (float)$unitCost <= 0) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Unit_Cost', 'message' => 'Unit Cost must be a positive number.'];
                }

                $parsedRows[] = ['type' => 'ITEM', 'data' => $row, 'project_id' => $currentProjectId];

            } elseif ($rowType === 'LABOR') {
                if (!$hasActiveBudget) {
                    if ($budgetExists) {
                        $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Row_Type', 'message' => "LABOR row cannot be imported because the BUDGET row defined above it (Row $lastBudgetLineNo) has validation errors."];
                    } else {
                        $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Row_Type', 'message' => 'LABOR row does not have any BUDGET row defined above it. Every CSV must start with a BUDGET row.'];
                    }
                    continue;
                }

                $laborName = trim($row['Labor_Type_Name'] ?? '');
                $workTypeName = trim($row['Work_Type_Name'] ?? '');
                $phaseItemName = trim($row['Phase_Item_Name'] ?? '');
                $rate = $row['Rate_Per_Sqft'] ?? '';
                $area = $row['Area_Sqft'] ?? '';

                if (!$laborName) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Labor_Type_Name', 'message' => 'Labor Type Name is required for LABOR.'];
                }
                if (!$workTypeName) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Work_Type_Name', 'message' => 'Work Type Name is required for LABOR.'];
                }
                if (!$phaseItemName) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Phase_Item_Name', 'message' => 'Phase Item Name is required for LABOR.'];
                }
                if ($rate === '' || !is_numeric($rate) || (float)$rate <= 0) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Rate_Per_Sqft', 'message' => 'Rate Per Sqft must be a positive number.'];
                }
                if ($area === '' || !is_numeric($area) || (float)$area <= 0) {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Area_Sqft', 'message' => 'Area Sqft must be a positive number.'];
                }

                $parsedRows[] = ['type' => 'LABOR', 'data' => $row, 'project_id' => $currentProjectId];

            } else {
                if ($rowType !== '') {
                    $csvErrors[] = ['row' => $actualLineNo, 'column' => 'Row_Type', 'message' => "Unknown Row_Type '$rowType'. Must be BUDGET, ITEM, or LABOR."];
                }
            }
        }

        // If there are validation errors, halt immediately
        if (!empty($csvErrors)) {
            $_SESSION['csv_import_errors'] = $csvErrors;
            setError("CSV validation failed. No records were imported.");
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
            exit;
        }

        // ── Phase 2: Transaction and Dynamic Insertion (Second Pass) ─────────
        $db->begin_transaction();

        try {
            // Helper resolved cache arrays to prevent repeating queries
            $cachedWorkTypes = [];
            $cachedPhases = [];
            $cachedPhaseItems = [];
            $cachedMaterials = [];

            // Helper to get or create Design Type ID
            $getDesignTypeId = function(string $name) use ($db): int {
                $name = trim($name);
                if (!$name) return 1; // Default Construction
                $s = $db->prepare("SELECT id FROM design_types WHERE name = ? LIMIT 1");
                $s->bind_param('s', $name);
                $s->execute();
                $r = $s->get_result()->fetch_assoc();
                if ($r) return (int)$r['id'];

                // Create dynamic design type
                $ins = $db->prepare("INSERT INTO design_types (name, status) VALUES (?, 'active')");
                $ins->bind_param('s', $name);
                $ins->execute();
                return (int)$db->insert_id;
            };

            // Helper to get or create Work Type ID
            $getOrCreateWorkType = function(string $name, int $dtId, ?string $defaultPhase, int $createdBy) use ($db, &$cachedWorkTypes): int {
                $key = strtolower($name) . '_' . $dtId;
                if (isset($cachedWorkTypes[$key])) return $cachedWorkTypes[$key];

                $s = $db->prepare("SELECT id FROM work_types WHERE work_type_name = ? AND design_type_id = ? LIMIT 1");
                $s->bind_param('si', $name, $dtId);
                $s->execute();
                $r = $s->get_result()->fetch_assoc();
                if ($r) {
                    $cachedWorkTypes[$key] = (int)$r['id'];
                    return (int)$r['id'];
                }

                $ins = $db->prepare("INSERT INTO work_types (work_type_name, design_type_id, default_phase_name, created_by) VALUES (?, ?, ?, ?)");
                if (!$ins) {
                    throw new \Exception("Prepare work_types insert failed: " . $db->error);
                }
                $ins->bind_param('sisi', $name, $dtId, $defaultPhase, $createdBy);
                $ins->execute();
                $id = (int)$db->insert_id;
                $cachedWorkTypes[$key] = $id;
                return $id;
            };

            // Helper to get or create Project Phase ID
            $getOrCreatePhase = function(int $projectId) use ($db, &$cachedPhases): int {
                if (isset($cachedPhases[$projectId])) return $cachedPhases[$projectId];

                $s = $db->prepare("SELECT id FROM project_phases WHERE project_id = ? ORDER BY id ASC LIMIT 1");
                $s->bind_param('i', $projectId);
                $s->execute();
                $r = $s->get_result()->fetch_assoc();
                if ($r) {
                    $cachedPhases[$projectId] = (int)$r['id'];
                    return (int)$r['id'];
                }

                $ins = $db->prepare("INSERT INTO project_phases (project_id, phase_name, status) VALUES (?, 'Construction Phase', 'In Progress')");
                $ins->bind_param('i', $projectId);
                $ins->execute();
                $id = (int)$db->insert_id;
                $cachedPhases[$projectId] = $id;
                return $id;
            };

            // Helper to get or create Phase Item ID
            $getOrCreatePhaseItem = function(int $phaseId, string $name, int $dtId, int $wtId) use ($db, &$cachedPhaseItems): int {
                $key = $phaseId . '_' . strtolower($name);
                if (isset($cachedPhaseItems[$key])) return $cachedPhaseItems[$key];

                $s = $db->prepare("SELECT id FROM project_phase_items WHERE phase_id = ? AND item_name = ? LIMIT 1");
                $s->bind_param('is', $phaseId, $name);
                $s->execute();
                $r = $s->get_result()->fetch_assoc();
                if ($r) {
                    $cachedPhaseItems[$key] = (int)$r['id'];
                    return (int)$r['id'];
                }

                $ins = $db->prepare("INSERT INTO project_phase_items (phase_id, item_name, design_type_id, work_type_id, status) VALUES (?, ?, ?, ?, 'Not Started')");
                $ins->bind_param('isii', $phaseId, $name, $dtId, $wtId);
                $ins->execute();
                $id = (int)$db->insert_id;
                $cachedPhaseItems[$key] = $id;
                return $id;
            };

            // Helper to get or create Material ID
            $getOrCreateMaterial = function(string $name, string $unit, int $createdBy) use ($db, &$cachedMaterials): int {
                $key = strtolower($name);
                if (isset($cachedMaterials[$key])) return $cachedMaterials[$key];

                $s = $db->prepare("SELECT id FROM con_materials WHERE name = ? LIMIT 1");
                $s->bind_param('s', $name);
                $s->execute();
                $r = $s->get_result()->fetch_assoc();
                if ($r) {
                    $cachedMaterials[$key] = (int)$r['id'];
                    return (int)$r['id'];
                }

                // Get or create a default category
                $catId = null;
                $cs = $db->query("SELECT id FROM con_material_categories LIMIT 1");
                if ($cs && $cr = $cs->fetch_assoc()) {
                    $catId = (int)$cr['id'];
                } else {
                    $db->query("INSERT INTO con_material_categories (name) VALUES ('General Materials')");
                    $catId = (int)$db->insert_id;
                }

                $ins = $db->prepare("INSERT INTO con_materials (category_id, name, unit, created_by) VALUES (?, ?, ?, ?)");
                $ins->bind_param('issi', $catId, $name, $unit, $createdBy);
                $ins->execute();
                $id = (int)$db->insert_id;
                $cachedMaterials[$key] = $id;
                return $id;
            };

            // Helper to resolve drawing_id
            $resolveDrawing = function(int $projectId, ?string $designTypeName) use ($db): ?int {
                $designTypeId = null;
                if ($designTypeName) {
                    $s = $db->prepare("SELECT id FROM design_types WHERE name = ? LIMIT 1");
                    $s->bind_param('s', $designTypeName);
                    $s->execute();
                    $r = $s->get_result()->fetch_assoc();
                    if ($r) $designTypeId = (int)$r['id'];
                }

                if ($designTypeId) {
                    $s = $db->prepare("SELECT id FROM project_drawings WHERE project_id = ? AND design_type_id = ? LIMIT 1");
                    $s->bind_param('ii', $projectId, $designTypeId);
                    $s->execute();
                    $r = $s->get_result()->fetch_assoc();
                    if ($r) return (int)$r['id'];
                }

                $s = $db->prepare("
                    SELECT pd.id 
                    FROM project_drawings pd
                    LEFT JOIN design_types dt ON pd.design_type_id = dt.id
                    WHERE pd.project_id = ? 
                    ORDER BY (LOWER(dt.name) LIKE '%architecture%' OR LOWER(dt.name) LIKE '%architectural%') DESC, pd.id ASC 
                    LIMIT 1
                ");
                $s->bind_param('i', $projectId);
                $s->execute();
                $r = $s->get_result()->fetch_assoc();
                return $r ? (int)$r['id'] : null;
            };

            $successBudgets = 0;
            $successItems = 0;
            $successLabors = 0;

            $activeBudgetId = null;
            $activeProjectId = null;
            $activeEstimatorId = null;
            $activeDesignTypesUsed = [];

            foreach ($parsedRows as $pRow) {
                $type = $pRow['type'];
                $row = $pRow['data'];

                if ($type === 'BUDGET') {
                    $projectId = (int)$pRow['project_id'];
                    $estimatorEmail = trim($row['Estimator_Email']);
                    $approvedByEmail = trim($row['Approved_By_Email'] ?? '');

                    $estimatorId = $resolveUserByEmail($estimatorEmail);
                    $approvedById = $approvedByEmail ? $resolveUserByEmail($approvedByEmail) : null;
                    
                    // Fetch architecture_id
                    $s = $db->prepare("SELECT id FROM architect_engineering WHERE project_id = ? LIMIT 1");
                    $s->bind_param('i', $projectId);
                    $s->execute();
                    $ae = $s->get_result()->fetch_assoc();
                    $archId = (int)$ae['id'];

                    $drawingId = $resolveDrawing($projectId, trim($row['Design_Type'] ?? ''));

                    $areaSqft = (float)($row['Total_Area_Sqft'] ?? 0);
                    $otherCost = (float)($row['Other_Costs'] ?? 0);
                    $otherCostDetail = trim($row['Other_Cost_Detail'] ?? '') ?: null;
                    $budgetLink = trim($row['Drive_Link'] ?? '') ?: null;
                    $startDate = trim($row['Start_Date'] ?? '') ?: null;
                    $endDate = trim($row['End_Date'] ?? '') ?: null;
                    $status = $approvedById ? 'approved' : 'pending';
                    $approvalDate = $approvedById ? date('Y-m-d H:i:s') : null;

                    $ins = $db->prepare("
                        INSERT INTO initial_estimate_budgets
                            (project_id, architecture_id, drawing_id, total_area_sqft,
                             other_costs, other_cost_detail, budget_link,
                             start_date, end_date, status, approved_by, approval_date, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $ins->bind_param('iiidssssssisi',
                        $projectId, $archId, $drawingId, $areaSqft,
                        $otherCost, $otherCostDetail, $budgetLink,
                        $startDate, $endDate, $status, $approvedById, $approvalDate, $estimatorId
                    );
                    $ins->execute();

                    $activeBudgetId = (int)$db->insert_id;
                    $activeProjectId = $projectId;
                    $activeEstimatorId = $estimatorId;
                    $activeDesignTypesUsed = [];
                    $successBudgets++;

                } elseif ($type === 'ITEM') {
                    $designTypeName = trim($row['Design_Type'] ?? '');
                    $dtId = $getDesignTypeId($designTypeName);
                    $activeDesignTypesUsed[$dtId] = true;

                    $workTypeName = trim($row['Work_Type_Name']);
                    $defaultPhaseName = trim($row['Default_Phase_Name'] ?? '') ?: null;
                    $wtId = $getOrCreateWorkType($workTypeName, $dtId, $defaultPhaseName, $activeEstimatorId);

                    $phaseId = $getOrCreatePhase($activeProjectId);
                    $phaseItemName = trim($row['Phase_Item_Name']);
                    $phaseItemId = $getOrCreatePhaseItem($phaseId, $phaseItemName, $dtId, $wtId);

                    $materialName = trim($row['Material_Name']);
                    $unit = trim($row['Material_Unit']);
                    $materialId = $getOrCreateMaterial($materialName, $unit, $activeEstimatorId);

                    $qty = (float)$row['Estimated_Qty'];
                    $unitCost = (float)$row['Unit_Cost'];
                    $remarks = trim($row['Item_Remarks'] ?? '') ?: null;
                    $location = trim($row['Location'] ?? '') ?: '';
                    $floorType = trim($row['Floor_Type'] ?? '') ?: 'Typical_Floor';
                    $floorNo = (int)($row['Floor_No'] ?? 0);

                    // Auto-increment serial number within budget
                    $sr = $db->query("SELECT IFNULL(MAX(sl_no), 0) + 1 AS next_sl FROM initial_estimate_items WHERE budget_id = $activeBudgetId");
                    $slNo = $sr ? (int)$sr->fetch_assoc()['next_sl'] : 1;

                    $ins = $db->prepare("
                        INSERT INTO initial_estimate_items
                            (budget_id, design_type_id, work_type_id, phase_item_id,
                             sl_no, location_desc, floor_type, floor_no,
                             material_id, material_unit, estimated_qty, unit_cost_approx, remarks)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $ins->bind_param('iiiisssiisdds',
                        $activeBudgetId, $dtId, $wtId, $phaseItemId,
                        $slNo, $location, $floorType, $floorNo,
                        $materialId, $unit, $qty, $unitCost, $remarks
                    );
                    $ins->execute();
                    $successItems++;

                } elseif ($type === 'LABOR') {
                    $designTypeName = trim($row['Design_Type'] ?? '');
                    $dtId = $getDesignTypeId($designTypeName);
                    $activeDesignTypesUsed[$dtId] = true;

                    $workTypeName = trim($row['Work_Type_Name']);
                    $defaultPhaseName = trim($row['Default_Phase_Name'] ?? '') ?: null;
                    $wtId = $getOrCreateWorkType($workTypeName, $dtId, $defaultPhaseName, $activeEstimatorId);

                    $phaseId = $getOrCreatePhase($activeProjectId);
                    $phaseItemName = trim($row['Phase_Item_Name']);
                    $phaseItemId = $getOrCreatePhaseItem($phaseId, $phaseItemName, $dtId, $wtId);

                    $laborName = trim($row['Labor_Type_Name']);
                    $rate = (float)$row['Rate_Per_Sqft'];
                    $area = (float)$row['Area_Sqft'];
                    $desc = trim($row['Labor_Description'] ?? '') ?: null;

                    $ins = $db->prepare("
                        INSERT INTO initial_estimate_labors
                            (budget_id, design_type_id, work_type_id, phase_item_id,
                             labor_type_name, rate_per_sqft, area_sqft, description)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $ins->bind_param('iiiisdds',
                        $activeBudgetId, $dtId, $wtId, $phaseItemId,
                        $laborName, $rate, $area, $desc
                    );
                    $ins->execute();
                    $successLabors++;
                }

                // Sync Totals for the current budget header
                if ($activeBudgetId) {
                    $this->model->syncTotals($activeBudgetId);

                    // Note: project_working_plans table was migrated to phase_monthly_targets in Module 3, so working plans are managed via monthly target planning.
                }
            }

            $db->commit();
            setSuccess("✅ Bulk Import Successful! Imported: $successBudgets budget(s), $successItems item(s), $successLabors labor(s).");

        } catch (\Exception $e) {
            $db->rollback();
            setError("❌ Database Transaction Error: " . $e->getMessage() . ". No records were imported.");
        }

        header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
        exit;
    }

    // =========================================================================
    // EXCEL IMPORT (Direct xlsx parsing)
    // POST /con-estimate-import-excel
    // =========================================================================
    public function importExcel()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
            exit;
        }

        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid Excel file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
            exit;
        }

        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $estimatorEmail = isset($_POST['estimator_email']) ? trim($_POST['estimator_email']) : '';
        $approvedByEmail = isset($_POST['approved_by_email']) ? trim($_POST['approved_by_email']) : '';
        $startDate = isset($_POST['start_date']) ? trim($_POST['start_date']) : null;
        $endDate = isset($_POST['end_date']) ? trim($_POST['end_date']) : null;
        $budgetLink = isset($_POST['budget_link']) ? trim($_POST['budget_link']) : null;
        $otherCosts = isset($_POST['other_costs']) ? (float)$_POST['other_costs'] : 0.0;
        $otherCostDetail = isset($_POST['other_cost_detail']) ? trim($_POST['other_cost_detail']) : null;

        if (!$projectId) {
            setError('Project is required.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
            exit;
        }

        if (!$estimatorEmail) {
            setError('Estimator Email is required.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
            exit;
        }

        $db = $this->db;
        $db->set_charset('utf8mb4');

        // Resolve estimator
        $s = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $s->bind_param('s', $estimatorEmail);
        $s->execute();
        $r = $s->get_result()->fetch_assoc();
        $estimatorId = $r ? (int)$r['id'] : null;

        if (!$estimatorId) {
            setError("Estimator email '$estimatorEmail' not found in ERP.");
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
            exit;
        }

        // Resolve approved_by
        $approvedById = null;
        if ($approvedByEmail) {
            $s = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $s->bind_param('s', $approvedByEmail);
            $s->execute();
            $r = $s->get_result()->fetch_assoc();
            $approvedById = $r ? (int)$r['id'] : null;

            if (!$approvedById) {
                setError("Approved By email '$approvedByEmail' not found in ERP.");
                header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
                exit;
            }
        }

        // Fetch architecture_id
        $s = $db->prepare("SELECT id FROM architect_engineering WHERE project_id = ? LIMIT 1");
        $s->bind_param('i', $projectId);
        $s->execute();
        $ae = $s->get_result()->fetch_assoc();
        if (!$ae) {
            setError("Selected project has no active Architect Engineering setup.");
            header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
            exit;
        }
        $archId = (int)$ae['id'];

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['excel_file']['tmp_name']);
            
            // ---------------------------------------------------------
            // 1. Identify and Parse BOQ Sheets
            // ---------------------------------------------------------
            $boqSheets = [
                'Structural work' => 'Structure',
                'Finshing work' => 'Finishing',
                'Electric item' => 'Electrical',
                'Electro Machanical' => 'Mechanical',
                'Utility and Security' => 'Utility & Security',
                'Paint work' => 'Paint',
                'Tiles works' => 'Tiles',
                'Wood,Metal,Aluminium' => 'Wood & Metal',
                'Sanitary' => 'Sanitary'
            ];

            $parsedItems = [];

            foreach ($boqSheets as $sheetName => $designType) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                if (!$sheet) continue;

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                $headerRowNo = null;
                $colMap = [];
                
                for ($r = 1; $r <= min($highestRow, 15); $r++) {
                    $rowVals = [];
                    for ($c = 'A'; $c <= $highestColumn; $c++) {
                        $rowVals[$c] = trim($sheet->getCell($c . $r)->getValue() ?? '');
                    }
                    $hasMat = false;
                    foreach ($rowVals as $c => $val) {
                        $lval = strtolower($val);
                        if (strpos($lval, 'material') !== false || strpos($lval, 'item name') !== false || strpos($lval, 'description') !== false || strpos($lval, 'work name') !== false) {
                            $hasMat = true;
                        }
                    }
                    if ($hasMat) {
                        $headerRowNo = $r;
                        foreach ($rowVals as $c => $val) {
                            $lval = strtolower($val);
                            if ($lval === 'sl' || $lval === 'sl no' || $lval === 'sl.') $colMap['sl'] = $c;
                            elseif (strpos($lval, 'work description') !== false || strpos($lval, 'work desc') !== false || strpos($lval, 'description of work') !== false) $colMap['work_desc'] = $c;
                            elseif (strpos($lval, 'material') !== false || strpos($lval, 'item name') !== false || strpos($lval, 'item description') !== false || $lval === 'description') $colMap['material_name'] = $c;
                            elseif (strpos($lval, 'unit price') !== false || strpos($lval, 'price') !== false || strpos($lval, 'rate') !== false) $colMap['rate'] = $c;
                            elseif (strpos($lval, 'unit') !== false) $colMap['unit'] = $c;
                            elseif ($lval === 'qty' || strpos($lval, 'quantity') !== false || strpos($lval, 'estimated qty') !== false) $colMap['qty'] = $c;
                            elseif (strpos($lval, 'floor') !== false) $colMap['floor'] = $c;
                            elseif (strpos($lval, 'remark') !== false) $colMap['remarks'] = $c;
                        }
                        break;
                    }
                }
                
                if (!$headerRowNo) continue;

                $activeWorkDesc = '';
                for ($r = $headerRowNo + 1; $r <= $highestRow; $r++) {
                    $workDesc = isset($colMap['work_desc']) ? trim($sheet->getCell($colMap['work_desc'] . $r)->getValue() ?? '') : '';
                    $materialName = isset($colMap['material_name']) ? trim($sheet->getCell($colMap['material_name'] . $r)->getValue() ?? '') : '';
                    $unit = isset($colMap['unit']) ? trim($sheet->getCell($colMap['unit'] . $r)->getValue() ?? '') : '';
                    $qty = isset($colMap['qty']) ? $sheet->getCell($colMap['qty'] . $r)->getCalculatedValue() : '';
                    $rate = isset($colMap['rate']) ? $sheet->getCell($colMap['rate'] . $r)->getCalculatedValue() : '';
                    $floor = isset($colMap['floor']) ? trim($sheet->getCell($colMap['floor'] . $r)->getValue() ?? '') : '';
                    $remarks = isset($colMap['remarks']) ? trim($sheet->getCell($colMap['remarks'] . $r)->getValue() ?? '') : '';

                    if (!$materialName && !$workDesc && !$qty && !$rate) continue;
                    if ($workDesc) $activeWorkDesc = $workDesc;
                    if (strpos(strtolower($materialName), 'total') !== false || strpos(strtolower($workDesc), 'total') !== false) continue;

                    if ($materialName && $qty && $rate && is_numeric($qty) && is_numeric($rate) && (float)$qty > 0 && (float)$rate > 0) {
                        $parsedItems[] = [
                            'design_type' => $designType,
                            'work_desc' => $activeWorkDesc ?: 'General Works',
                            'material_name' => $materialName,
                            'unit' => $unit,
                            'qty' => (float)$qty,
                            'rate' => (float)$rate,
                            'floor' => $floor,
                            'remarks' => $remarks
                        ];
                    }
                }
            }

            // ---------------------------------------------------------
            // 2. Identify and Parse Labor Sheet (Sub-Contractor Bill)
            // ---------------------------------------------------------
            $laborSheet = $spreadsheet->getSheetByName('Sub-Contractor Bill');
            $parsedLabors = [];
            if ($laborSheet) {
                $highestRow = $laborSheet->getHighestRow();
                $headerRowNo = null;
                $colMap = [];
                for ($r = 1; $r <= 10; $r++) {
                    $vals = [];
                    for ($col = 'A'; $col <= 'K'; $col++) {
                        $vals[$col] = strtolower(trim($laborSheet->getCell($col . $r)->getValue() ?? ''));
                    }
                    if (in_array('description', $vals) || in_array('bill type', $vals)) {
                        $headerRowNo = $r;
                        foreach ($vals as $col => $val) {
                            if ($val === 'sl') $colMap['sl'] = $col;
                            elseif ($val === 'description') $colMap['desc'] = $col;
                            elseif ($val === 'bill type') $colMap['bill_type'] = $col;
                            elseif ($val === 'unit') $colMap['unit'] = $col;
                            elseif ($val === 'rate') $colMap['rate'] = $col;
                            elseif ($val === 'sft' || $val === 'qty') $colMap['sft'] = $col;
                            elseif ($val === 'floor') $colMap['floor'] = $col;
                            elseif ($val === 'remarks') $colMap['remarks'] = $col;
                        }
                        break;
                    }
                }

                if ($headerRowNo) {
                    $activeDesc = '';
                    for ($r = $headerRowNo + 1; $r <= $highestRow; $r++) {
                        $desc = isset($colMap['desc']) ? trim($laborSheet->getCell($colMap['desc'] . $r)->getValue() ?? '') : '';
                        $billType = isset($colMap['bill_type']) ? trim($laborSheet->getCell($colMap['bill_type'] . $r)->getValue() ?? '') : '';
                        $unit = isset($colMap['unit']) ? trim($laborSheet->getCell($colMap['unit'] . $r)->getValue() ?? '') : '';
                        $rate = isset($colMap['rate']) ? $laborSheet->getCell($colMap['rate'] . $r)->getCalculatedValue() : '';
                        $sft = isset($colMap['sft']) ? $laborSheet->getCell($colMap['sft'] . $r)->getCalculatedValue() : '';
                        $floor = isset($colMap['floor']) ? $laborSheet->getCell($colMap['floor'] . $r)->getCalculatedValue() : '';
                        $remarks = isset($colMap['remarks']) ? trim($laborSheet->getCell($colMap['remarks'] . $r)->getValue() ?? '') : '';

                        if (!$desc && !$billType && !$rate) continue;
                        if ($desc) $activeDesc = $desc;
                        if (strpos(strtolower($activeDesc), 'total') !== false || strpos(strtolower($billType), 'total') !== false) continue;

                        if ($rate && is_numeric($rate) && (float)$rate > 0) {
                            $parsedLabors[] = [
                                'contractor' => $activeDesc,
                                'bill_type' => $billType,
                                'unit' => $unit,
                                'rate' => (float)$rate,
                                'sft' => $sft ? (float)$sft : 1.0,
                                'floor' => $floor ? (int)$floor : 1,
                                'remarks' => $remarks
                            ];
                        }
                    }
                }
            }

            if (empty($parsedItems) && empty($parsedLabors)) {
                setError('No valid estimate items or labors found in the Excel workbook.');
                header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
                exit;
            }

            // ---------------------------------------------------------
            // 3. Database Transaction & Insertions
            // ---------------------------------------------------------
            $db->begin_transaction();

            $cachedWorkTypes = [];
            $cachedPhases = [];
            $cachedPhaseItems = [];
            $cachedMaterials = [];

            $getDesignTypeId = function(string $name) use ($db): int {
                $name = trim($name);
                if (!$name) return 1;
                $s = $db->prepare("SELECT id FROM design_types WHERE name = ? LIMIT 1");
                $s->bind_param('s', $name);
                $s->execute();
                $r = $s->get_result()->fetch_assoc();
                if ($r) return (int)$r['id'];

                $ins = $db->prepare("INSERT INTO design_types (name, status) VALUES (?, 'active')");
                $ins->bind_param('s', $name);
                $ins->execute();
                return (int)$db->insert_id;
            };

            $getOrCreateWorkType = function(string $name, int $dtId, ?string $defaultPhase, int $createdBy) use ($db, &$cachedWorkTypes): int {
                $key = strtolower($name) . '_' . $dtId;
                if (isset($cachedWorkTypes[$key])) return $cachedWorkTypes[$key];

                $s = $db->prepare("SELECT id, default_phase_name FROM work_types WHERE work_type_name = ? AND design_type_id = ? LIMIT 1");
                $s->bind_param('si', $name, $dtId);
                $s->execute();
                $r = $s->get_result()->fetch_assoc();
                if ($r) {
                    $existingId = (int)$r['id'];
                    if (empty($r['default_phase_name']) && $defaultPhase) {
                        $upd = $db->prepare("UPDATE work_types SET default_phase_name = ? WHERE id = ?");
                        $upd->bind_param('si', $defaultPhase, $existingId);
                        $upd->execute();
                    }
                    $cachedWorkTypes[$key] = $existingId;
                    return $existingId;
                }

                $ins = $db->prepare("INSERT INTO work_types (work_type_name, design_type_id, default_phase_name, created_by) VALUES (?, ?, ?, ?)");
                $ins->bind_param('sisi', $name, $dtId, $defaultPhase, $createdBy);
                $ins->execute();
                $id = (int)$db->insert_id;
                $cachedWorkTypes[$key] = $id;
                return $id;
            };

            $getPhaseName = function(string $designType, string $workDesc) {
                $dt = strtolower(trim($designType));
                $wd = strtolower(trim($workDesc));
                
                if (strpos($dt, 'struct') !== false) {
                    return 'Structure';
                }
                if (strpos($dt, 'electri') !== false) {
                    return 'Electrical Work';
                }
                if (strpos($dt, 'mechanic') !== false) {
                    return 'EME';
                }
                if (strpos($dt, 'utility') !== false) {
                    return 'Utility Connection & Bill';
                }
                if (strpos($dt, 'paint') !== false) {
                    return 'Paint Work';
                }
                if (strpos($dt, 'tiles') !== false) {
                    return 'General Floor Tiles';
                }
                if (strpos($dt, 'sanitary') !== false || strpos($dt, 'plumb') !== false) {
                    return 'Plumbing & Sanitary';
                }
                if (strpos($dt, 'wood') !== false || strpos($dt, 'metal') !== false) {
                    if (strpos($wd, 'metal') !== false || strpos($wd, 'gate') !== false || strpos($wd, 'grill') !== false) {
                        return 'Metal Work';
                    }
                    if (strpos($wd, 'aluminium') !== false || strpos($wd, 'thai') !== false) {
                        return 'Aluminium Work';
                    }
                    return 'Wood Work';
                }
                if (strpos($dt, 'finish') !== false) {
                    if (strpos($wd, 'brick') !== false) {
                        return 'Brick Work';
                    }
                    if (strpos($wd, 'plaster') !== false) {
                        return 'Plaster Work';
                    }
                    return 'Others Civil Work';
                }
                return 'Others Civil Work';
            };

            $getOrCreatePhase = function(int $projectId, string $phaseName) use ($db, &$cachedPhases): int {
                $key = $projectId . '_' . strtolower($phaseName);
                if (isset($cachedPhases[$key])) return $cachedPhases[$key];

                $s = $db->prepare("SELECT id FROM project_phases WHERE project_id = ? AND phase_name = ? LIMIT 1");
                $s->bind_param('is', $projectId, $phaseName);
                $s->execute();
                $r = $s->get_result()->fetch_assoc();
                if ($r) {
                    $cachedPhases[$key] = (int)$r['id'];
                    return (int)$r['id'];
                }

                $ins = $db->prepare("INSERT INTO project_phases (project_id, phase_name, status) VALUES (?, ?, 'In Progress')");
                $ins->bind_param('is', $projectId, $phaseName);
                $ins->execute();
                $id = (int)$db->insert_id;
                $cachedPhases[$key] = $id;
                return $id;
            };

            $getOrCreatePhaseItem = function(int $phaseId, string $name, int $dtId, int $wtId) use ($db, &$cachedPhaseItems): int {
                $key = $phaseId . '_' . strtolower($name);
                if (isset($cachedPhaseItems[$key])) return $cachedPhaseItems[$key];

                $s = $db->prepare("SELECT id FROM project_phase_items WHERE phase_id = ? AND item_name = ? LIMIT 1");
                $s->bind_param('is', $phaseId, $name);
                $s->execute();
                $r = $s->get_result()->fetch_assoc();
                if ($r) {
                    $cachedPhaseItems[$key] = (int)$r['id'];
                    return (int)$r['id'];
                }

                $ins = $db->prepare("INSERT INTO project_phase_items (phase_id, item_name, design_type_id, work_type_id, status) VALUES (?, ?, ?, ?, 'Not Started')");
                $ins->bind_param('isii', $phaseId, $name, $dtId, $wtId);
                $ins->execute();
                $id = (int)$db->insert_id;
                $cachedPhaseItems[$key] = $id;
                return $id;
            };

            $getOrCreateMaterial = function(string $name, string $unit, int $createdBy) use ($db, &$cachedMaterials): int {
                $key = strtolower($name);
                if (isset($cachedMaterials[$key])) return $cachedMaterials[$key];

                $s = $db->prepare("SELECT id FROM con_materials WHERE name = ? LIMIT 1");
                $s->bind_param('s', $name);
                $s->execute();
                $r = $s->get_result()->fetch_assoc();
                if ($r) {
                    $cachedMaterials[$key] = (int)$r['id'];
                    return (int)$r['id'];
                }

                $catId = null;
                $cs = $db->query("SELECT id FROM con_material_categories LIMIT 1");
                if ($cs && $cr = $cs->fetch_assoc()) {
                    $catId = (int)$cr['id'];
                } else {
                    $db->query("INSERT INTO con_material_categories (name) VALUES ('General Materials')");
                    $catId = (int)$db->insert_id;
                }

                $ins = $db->prepare("INSERT INTO con_materials (category_id, name, unit, created_by) VALUES (?, ?, ?, ?)");
                $ins->bind_param('issi', $catId, $name, $unit, $createdBy);
                $ins->execute();
                $id = (int)$db->insert_id;
                $cachedMaterials[$key] = $id;
                return $id;
            };

            $resolveDrawing = function(int $projectId, ?string $designTypeName) use ($db): ?int {
                $designTypeId = null;
                if ($designTypeName) {
                    $s = $db->prepare("SELECT id FROM design_types WHERE name = ? LIMIT 1");
                    $s->bind_param('s', $designTypeName);
                    $s->execute();
                    $r = $s->get_result()->fetch_assoc();
                    if ($r) $designTypeId = (int)$r['id'];
                }

                if ($designTypeId) {
                    $s = $db->prepare("SELECT id FROM project_drawings WHERE project_id = ? AND design_type_id = ? LIMIT 1");
                    $s->bind_param('ii', $projectId, $designTypeId);
                    $s->execute();
                    $r = $s->get_result()->fetch_assoc();
                    if ($r) return (int)$r['id'];
                }

                $s = $db->prepare("
                    SELECT pd.id 
                    FROM project_drawings pd
                    LEFT JOIN design_types dt ON pd.design_type_id = dt.id
                    WHERE pd.project_id = ? 
                    ORDER BY (LOWER(dt.name) LIKE '%architecture%' OR LOWER(dt.name) LIKE '%architectural%') DESC, pd.id ASC 
                    LIMIT 1
                ");
                $s->bind_param('i', $projectId);
                $s->execute();
                $r = $s->get_result()->fetch_assoc();
                return $r ? (int)$r['id'] : null;
            };

            // Read Selling Floor Area from Budget Summary Sheet (Row 5)
            $totalAreaSqft = 0.0;
            $summarySheet = $spreadsheet->getSheetByName('Budget Summary');
            if ($summarySheet) {
                for ($r = 1; $r <= 10; $r++) {
                    $val = trim($summarySheet->getCell('B' . $r)->getValue() ?? '');
                    if (strpos(strtolower($val), 'selling floor area') !== false || strpos(strtolower($val), 'floor area') !== false) {
                        $totalAreaSqft = (float)$summarySheet->getCell('F' . $r)->getCalculatedValue();
                        break;
                    }
                }
            }

            $drawingId = $resolveDrawing($projectId, 'Structure');
            $status = $approvedById ? 'approved' : 'pending';
            $approvalDate = $approvedById ? date('Y-m-d H:i:s') : null;

            // Insert Budget Header
            $ins = $db->prepare("
                INSERT INTO initial_estimate_budgets
                    (project_id, architecture_id, drawing_id, total_area_sqft,
                     other_costs, other_cost_detail, budget_link,
                     start_date, end_date, status, approved_by, approval_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->bind_param('iiidssssssisi',
                $projectId, $archId, $drawingId, $totalAreaSqft,
                $otherCosts, $otherCostDetail, $budgetLink,
                $startDate, $endDate, $status, $approvedById, $approvalDate, $estimatorId
            );
            $ins->execute();
            $budgetId = (int)$db->insert_id;

            // Insert parsed BOQ items
            $successItems = 0;
            foreach ($parsedItems as $item) {
                $dtId = $getDesignTypeId($item['design_type']);
                $defaultPhase = $getPhaseName($item['design_type'], $item['work_desc']);
                $wtId = $getOrCreateWorkType($item['work_desc'], $dtId, $defaultPhase, $estimatorId);
                $phaseId = $getOrCreatePhase($projectId, $defaultPhase);
                $phaseItemId = $getOrCreatePhaseItem($phaseId, $item['work_desc'], $dtId, $wtId);
                $materialId = $getOrCreateMaterial($item['material_name'], $item['unit'], $estimatorId);

                $floorType = 'Typical_Floor';
                $floorNo = 0;
                $floorStr = strtolower($item['floor']);
                if (strpos($floorStr, 'basement') !== false) {
                    $floorType = 'Basement';
                } elseif (strpos($floorStr, 'ground') !== false) {
                    $floorType = 'Ground_Floor';
                } elseif (strpos($floorStr, 'roof') !== false) {
                    $floorType = 'Roof';
                }

                if (preg_replace('/[^0-9]/', '', $floorStr) !== '') {
                    $floorNo = (int)preg_replace('/[^0-9]/', '', $floorStr);
                }

                $sr = $db->query("SELECT IFNULL(MAX(sl_no), 0) + 1 AS next_sl FROM initial_estimate_items WHERE budget_id = $budgetId");
                $slNo = $sr ? (int)$sr->fetch_assoc()['next_sl'] : 1;

                $insItem = $db->prepare("
                    INSERT INTO initial_estimate_items
                        (budget_id, design_type_id, work_type_id, phase_item_id,
                         sl_no, location_desc, floor_type, floor_no,
                         material_id, material_unit, estimated_qty, unit_cost_approx, remarks, nos)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $insItem->bind_param('iiiisssiisdds',
                    $budgetId, $dtId, $wtId, $phaseItemId,
                    $slNo, $item['work_desc'], $floorType, $floorNo,
                    $materialId, $item['unit'], $item['qty'], $item['rate'], $item['remarks']
                );
                $insItem->execute();
                $successItems++;
            }

            // Insert parsed labors
            $successLabors = 0;
            foreach ($parsedLabors as $labor) {
                $dtId = $getDesignTypeId('Structure');
                $defaultPhase = $getPhaseName('Structure', $labor['contractor']);
                $wtId = $getOrCreateWorkType($labor['contractor'], $dtId, $defaultPhase, $estimatorId);
                $phaseId = $getOrCreatePhase($projectId, $defaultPhase);
                $phaseItemId = $getOrCreatePhaseItem($phaseId, $labor['contractor'], $dtId, $wtId);

                $insLabor = $db->prepare("
                    INSERT INTO initial_estimate_labors
                        (budget_id, design_type_id, work_type_id, phase_item_id,
                         labor_type_name, rate_per_sqft, area_sqft, description, floor_no)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $laborName = $labor['contractor'] . ($labor['bill_type'] ? ' - ' . $labor['bill_type'] : '');
                $totalArea = $labor['sft'] * $labor['floor'];

                $insLabor->bind_param('iiiisddsi',
                    $budgetId, $dtId, $wtId, $phaseItemId,
                    $laborName, $labor['rate'], $totalArea, $labor['remarks'], $labor['floor']
                );
                $insLabor->execute();
                $successLabors++;
            }

            // Sync Totals for the budget header
            $this->model->syncTotals($budgetId);

            $db->commit();
            setSuccess("✅ Excel Import Successful! Mapped and imported: $successItems items and $successLabors labor records.");

        } catch (\Exception $e) {
            $db->rollback();
            setError("❌ Excel Upload Error: " . $e->getMessage());
        }

        header('Location: ' . $GLOBALS['base_url'] . '/con-estimates');
        exit;
    }
}

