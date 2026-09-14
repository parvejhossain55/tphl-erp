<?php
namespace Phase2;

use Controller;
use Con_WorkOrder;
use Con_Contractor;
use ApprovalRequest;

class Con_WorkOrderController extends Controller
{
    protected $db;
    protected $model;
    protected $contractorModel;
    protected $approvalModel;
    protected $userId;
    protected $userRole;

    public function __construct($db)
    {
        $this->db              = $db;
        $this->model           = new Con_WorkOrder($db);
        $this->contractorModel = new Con_Contractor($db);
        $this->approvalModel   = new ApprovalRequest($db);

        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->userId   = (int)($_SESSION['user']['id']      ?? 0);
        $this->userRole = (int)($_SESSION['user']['role_id'] ?? 0);
    }

    // ── Role helpers ──────────────────────────────────────────────────────────

    private function canCreate()
    {
        return in_array($this->userRole, [1, 3, 33, 15, 22]);
    }

    private function canApprove()
    {
        return in_array($this->userRole, [1, 3, 33, 12, 15, 20, 22]);
    }

    private function canMarkComplete()
    {
        return in_array($this->userRole, [1, 3, 33, 12, 15, 20, 22]);
    }

    private function canQcApprove()
    {
        return in_array($this->userRole, [1, 3, 33, 13]);
    }

    private function canDelete()
    {
        return in_array($this->userRole, [1, 3, 33]);
    }

    // =========================================================================
    // LIST
    // =========================================================================
    public function index()
    {
        $currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
        if ($currentPage < 1) $currentPage = 1;
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;

        $totalRecords = $this->model->countAllWithRelations($this->userId, $this->userRole);
        $totalPages = ceil($totalRecords / $perPage);

        $workOrders = $this->model->getPaginatedWithRelations($perPage, $offset, $this->userId, $this->userRole);
        $statusCounts = $this->model->getStatusCounts($this->userId, $this->userRole);

        $this->view('phase2/work-orders/work-order-list', [
            'current_route' => 'con-work-orders',
            'page_title'    => 'Work Orders',
            'workOrders'    => $workOrders,
            'canCreate'     => $this->canCreate(),
            'userRole'      => $this->userRole,
            'currentPage'   => $currentPage,
            'totalPages'    => $totalPages,
            'offset'        => $offset,
            'totalRecords'  => $totalRecords,
            'statusCounts'  => $statusCounts,
        ]);
    }

    // =========================================================================
    // CREATE FORM
    // =========================================================================
    public function create()
    {
        if (!$this->canCreate()) {
            setError('You do not have permission to create Work Orders.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-orders");
            exit;
        }

        $projects    = $this->getProjects();
        $contractors = $this->contractorModel->getActiveContractors();
        $workTypes   = $this->db->query("SELECT id, work_type_name FROM work_types ORDER BY work_type_name")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/work-orders/create-work-order', [
            'current_route' => 'con-work-orders',
            'page_title'    => 'New Work Order',
            'projects'      => $projects,
            'contractors'   => $contractors,
            'workTypes'     => $workTypes,
            'workPlans'     => [],   // loaded via AJAX on project select
            'targets'       => [],   // loaded via AJAX on work plan select
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================
    public function store()
    {
        if (!$this->canCreate()) {
            setError('Permission denied.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-orders");
            exit;
        }

        $unitRate  = (float)($_POST['unit_rate']  ?? 0);
        $quantity  = (float)($_POST['quantity']   ?? 1);
        $advPct    = (float)($_POST['advance_percent']   ?? 0);
        $runPct    = (float)($_POST['running_percent']   ?? 70);
        $retPct    = (float)($_POST['retention_percent'] ?? 10);
        $total     = $unitRate * $quantity;

        $data = [
            'project_id'        => (int)($_POST['project_id']    ?? 0),
            'contractor_id'     => (int)($_POST['contractor_id'] ?? 0),
            'design_id'         => !empty($_POST['design_id'])    ? (int)$_POST['design_id']    : null,
            'work_type_id'      => !empty($_POST['work_type_id']) ? (int)$_POST['work_type_id'] : null,
            'boq_id'            => !empty($_POST['boq_id'])       ? (int)$_POST['boq_id']       : null,
            'title'             => trim($_POST['title']           ?? ''),
            'description'       => trim($_POST['description']     ?? ''),
            'scope_of_work'     => trim($_POST['scope_of_work']   ?? ''),
            'notes'             => trim($_POST['notes']           ?? ''),
            'unit'              => trim($_POST['unit']            ?? ''),
            'document_link'     => trim($_POST['document_link']   ?? '') ?: null,
            'work_items'        => null, // deprecated JSON — no longer written
            'unit_rate'         => $unitRate,
            'quantity'          => $quantity,
            'total_amount'      => $total,
            'start_date'        => $_POST['start_date'] ?? null,
            'end_date'          => $_POST['end_date']   ?? null,
            'advance_percent'   => $advPct,
            'running_percent'   => $runPct,
            'retention_percent' => $retPct,
            'advance_amount'    => round($total * $advPct / 100, 2),
            'retention_amount'  => round($total * $retPct / 100, 2),
            'created_by'        => $this->userId,
        ];

        if (empty($data['project_id']) || empty($data['contractor_id']) || empty($data['title'])) {
            setError('Project, Contractor and Title are required.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-order-create");
            exit;
        }

        $id = $this->model->create($data);
        if ($id) {
            $this->model->initApprovals($id);
            // ── Save rate schedule items to DB ───────────────────────────────
            $rawItems = $_POST['wi_items'] ?? [];
            if (!empty($rawItems) && is_array($rawItems)) {
                $items = [];
                foreach ($rawItems as $i => $it) {
                    $desc = trim($it['description'] ?? '');
                    if ($desc === '') continue;
                    $qty  = (float)($it['quantity']    ?? 0);
                    $rate = (float)($it['rate']        ?? 0);
                    $items[] = [
                        'sl_no'       => trim($it['sl_no']      ?? ($i + 1)),
                        'phase_item_id' => !empty($it['phase_item_id']) ? (int)$it['phase_item_id'] : null,
                        'work_type_id' => !empty($it['work_type_id']) ? (int)$it['work_type_id'] : null,
                        'description' => $desc,
                        'location'    => trim($it['location']   ?? ''),
                        'unit'        => trim($it['unit']       ?? 'sqft'),
                        'quantity'    => $qty,
                        'rate'        => $rate,
                        'sort_order'  => $i,
                    ];
                }
                $this->model->addItems($id, $items);
            }

            // Sync total from items
            $this->model->syncTotal($id);

            // ── Approval request ─────────────────────────────────────────────
            $operator = $this->contractorModel->find($data['contractor_id']);
            $project  = $this->db->query("SELECT name FROM projects WHERE id = " . (int)$data['project_id'])->fetch_assoc();

            // Get updated total after sync
            $woFresh = $this->model->find($id);
            $freshTotal = (float)($woFresh['total_amount'] ?? $total);

            $this->approvalModel->create([
                'department'    => 'construction',
                'request_type'  => 'work_order',
                'title'         => 'Work Order — ' . ($data['title'] ?? '') . ' | ' . ($operator['name'] ?? '') . ' | ' . ($project['name'] ?? ''),
                'source_table'  => 'con_work_orders',
                'source_id'     => $id,
                'requested_days'=> 0,
                'requested_by'  => $this->userId,
                'approve_role'  => 'management',
                'meta_data'     => null,
                'request_amount'=> $freshTotal,
            ]);

            setSuccess('Work Order created and sent for approval successfully!');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
        } else {
            setError('Failed to create Work Order.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-order-create");
        }
        exit;
    }

    // =========================================================================
    // SHOW
    // =========================================================================
    public function show($id)
    {
        $wo = $this->model->findWithRelations($id);
        if (!$wo) {
            setError('Work Order not found.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-orders");
            exit;
        }

        $woItems             = $this->model->getItemsByWorkOrder($id);
        $linkedBills         = $this->model->getLinkedBills($id);
        $linkedWeeklyReports = $this->model->getLinkedWeeklyReports($id);
        $updateLogs          = $this->model->getUpdateLogs($id);
        $approvals           = $this->model->getApprovals($id);

        $this->view('phase2/work-orders/show-work-order', [
            'current_route'       => 'con-work-orders',
            'page_title'          => 'Work Order — ' . $wo['wo_number'],
            'wo'                  => $wo,
            'woItems'             => $woItems,
            'linkedBills'         => $linkedBills,
            'linkedWeeklyReports' => $linkedWeeklyReports,
            'updateLogs'          => $updateLogs,
            'approvals'           => $approvals,
            'canApprove'          => $this->canApprove(),
            'canMarkComplete'     => $this->canMarkComplete(),
            'canQcApprove'        => $this->canQcApprove(),
            'canDelete'           => $this->canDelete(),
            'userRole'            => $this->userRole,
        ]);
    }

    // =========================================================================
    // EDIT FORM
    // =========================================================================
    public function edit($id)
    {
        $wo = $this->model->find($id);
        if (!$wo || !in_array($wo['status'], ['draft', 'rejected'])) {
            setError('Only draft or rejected Work Orders can be edited.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
            exit;
        }

        if (!$this->canCreate()) {
            setError('Permission denied.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
            exit;
        }

        $projects    = $this->getProjects();
        $contractors = $this->contractorModel->getActiveContractors();
        $workTypes   = $this->db->query("SELECT id, work_type_name FROM work_types ORDER BY work_type_name")->fetch_all(MYSQLI_ASSOC);

        // work_plan_id removed from Work Orders

        $woItems     = $this->model->getItemsByWorkOrder((int)$id);

        $this->view('phase2/work-orders/edit-work-order', [
            'current_route' => 'con-work-orders',
            'page_title'    => 'Edit Work Order — ' . ($wo['wo_number'] ?? ''),
            'wo'            => $wo,
            'woItems'       => $woItems,
            'projects'      => $projects,
            'contractors'   => $contractors,
            'workTypes'     => $workTypes,
        ]);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================
    public function update($id)
    {
        $wo = $this->model->find($id);
        if (!$wo || !in_array($wo['status'], ['draft', 'rejected'])) {
            setError('Only draft or rejected Work Orders can be updated.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
            exit;
        }

        $unitRate = (float)($_POST['unit_rate']  ?? 0);
        $quantity = (float)($_POST['quantity']   ?? 1);
        $advPct   = (float)($_POST['advance_percent']   ?? 0);
        $runPct   = (float)($_POST['running_percent']   ?? 70);
        $retPct   = (float)($_POST['retention_percent'] ?? 10);
        $total    = $unitRate * $quantity;

        $data = [
            'project_id'        => (int)($_POST['project_id']    ?? 0),
            'contractor_id'     => (int)($_POST['contractor_id'] ?? 0),
            'design_id'         => !empty($_POST['design_id'])    ? (int)$_POST['design_id']    : null,
            'work_type_id'      => !empty($_POST['work_type_id']) ? (int)$_POST['work_type_id'] : null,
            'boq_id'            => !empty($_POST['boq_id'])       ? (int)$_POST['boq_id']       : null,
            'title'             => trim($_POST['title']         ?? ''),
            'description'       => trim($_POST['description']   ?? ''),
            'scope_of_work'     => trim($_POST['scope_of_work'] ?? ''),
            'notes'             => trim($_POST['notes']         ?? ''),
            'unit'              => trim($_POST['unit']          ?? ''),
            'document_link'     => trim($_POST['document_link'] ?? '') ?: null,
            'work_items'        => null, // deprecated JSON
            'unit_rate'         => $unitRate,
            'quantity'          => $quantity,
            'total_amount'      => $total,
            'start_date'        => $_POST['start_date'] ?? null,
            'end_date'          => $_POST['end_date']   ?? null,
            'advance_percent'   => $advPct,
            'running_percent'   => $runPct,
            'retention_percent' => $retPct,
            'advance_amount'    => round($total * $advPct / 100, 2),
            'retention_amount'  => round($total * $retPct / 100, 2),
        ];

        if ($this->model->update($id, $data)) {
            // ── Replace items in DB ──────────────────────────────────────────
            $rawItems = $_POST['wi_items'] ?? [];
            if (is_array($rawItems)) {
                $items = [];
                foreach ($rawItems as $i => $it) {
                    $desc = trim($it['description'] ?? '');
                    if ($desc === '') continue;
                    $items[] = [
                        'sl_no'       => trim($it['sl_no']      ?? ($i + 1)),
                        'phase_item_id' => !empty($it['phase_item_id']) ? (int)$it['phase_item_id'] : null,
                        'work_type_id' => !empty($it['work_type_id']) ? (int)$it['work_type_id'] : null,
                        'description' => $desc,
                        'location'    => trim($it['location']   ?? ''),
                        'unit'        => trim($it['unit']       ?? 'sqft'),
                        'quantity'    => (float)($it['quantity'] ?? 0),
                        'rate'        => (float)($it['rate']    ?? 0),
                        'sort_order'  => $i,
                    ];
                }
                $this->model->replaceItems($id, $items);
            }
            $this->model->syncTotal($id);
            $this->model->initApprovals($id); // reset sequential approval queue

            // ── Save update history log ──────────────────────────────────────
            $updateDate  = !empty($_POST['update_date'])  ? $_POST['update_date']  : date('Y-m-d');
            $updateNotes = trim($_POST['update_notes'] ?? '');
            $this->model->addUpdateLog((int)$id, $updateDate, $updateNotes, $this->userId);

            setSuccess('Work Order updated successfully.');
        } else {
            setError('Update failed.');
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
        exit;
    }

    // =========================================================================
    // APPROVE
    // =========================================================================
    public function approve($id)
    {
        if (!$this->canApprove()) {
            setError('You are not authorized to approve Work Orders.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
            exit;
        }

        $actingRole = 0;
        if ($this->userRole === 12 || $this->userRole === 20) {
            $actingRole = ($this->userRole === 20) ? 20 : 12;
        } elseif ($this->userRole === 15 || $this->userRole === 22) {
            $actingRole = ($this->userRole === 22) ? 22 : 15;
        } elseif (in_array($this->userRole, [1, 3, 33])) {
            $actingRole = 3;
        }

        $note = trim($_POST['approval_note'] ?? 'Approved');

        if ($this->model->updateApproval($id, $actingRole, $this->userId, 'approved', $note)) {
            $this->syncApprovalRequest($id, 'approved');
            setSuccess('Work Order approved successfully!');
        } else {
            setError('Approval failed.');
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
        exit;
    }

    // =========================================================================
    // REJECT
    // =========================================================================
    public function reject($id)
    {
        if (!$this->canApprove()) {
            setError('You are not authorized to reject Work Orders.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
            exit;
        }

        $reason = trim($_POST['rejection_reason'] ?? '');
        $actingRole = 0;
        if ($this->userRole === 12 || $this->userRole === 20) {
            $actingRole = ($this->userRole === 20) ? 20 : 12;
        } elseif ($this->userRole === 15 || $this->userRole === 22) {
            $actingRole = ($this->userRole === 22) ? 22 : 15;
        } elseif (in_array($this->userRole, [1, 3, 33])) {
            $actingRole = 3;
        }

        if ($this->model->updateApproval($id, $actingRole, $this->userId, 'rejected', $reason)) {
            $this->syncApprovalRequest($id, 'rejected');
            setSuccess('Work Order has been rejected.');
        } else {
            setError('Rejection failed.');
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
        exit;
    }

    // =========================================================================
    // SYNC APPROVAL REQUEST
    // =========================================================================
    private function syncApprovalRequest($sourceId, $newStatus)
    {
        $arStatus = ($newStatus === 'approved') ? 'approved' : 'rejected';

        $stmt = $this->db->prepare(
            "UPDATE approval_requests
             SET status = ?, approved_by = ?, approved_at = NOW()
             WHERE source_table = 'con_work_orders' AND source_id = ? AND status = 'pending'"
        );
        $stmt->bind_param('sii', $arStatus, $this->userId, $sourceId);
        $stmt->execute();
    }

    // =========================================================================
    // MARK COMPLETE
    // =========================================================================
    public function markComplete($id)
    {
        if (!$this->canMarkComplete()) {
            setError('You are not authorized to mark Work Orders as complete.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
            exit;
        }

        if ($this->model->markComplete($id, $this->userId)) {
            setSuccess('Work Order marked as complete. Awaiting QC approval.');
        } else {
            setError('Cannot mark complete. Work Order must be approved/active.');
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
        exit;
    }

    // =========================================================================
    // QC APPROVE → Closed
    // =========================================================================
    public function qcApprove($id)
    {
        if (!$this->canQcApprove()) {
            setError('Only QC can approve completed work.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
            exit;
        }

        if ($this->model->qcApprove($id, $this->userId)) {
            setSuccess('QC approved! Work Order is now closed. Contractor Bill can now be created.');
        } else {
            setError('QC approval failed. Work must be marked complete first.');
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
        exit;
    }

    // =========================================================================
    // DELETE
    // =========================================================================
    public function delete()
    {
        if (!$this->canDelete()) {
            setError('Permission denied.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-orders");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($this->model->delete($id)) {
            setSuccess('Work Order deleted.');
        } else {
            setError('Cannot delete. Only draft Work Orders can be deleted.');
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-work-orders");
        exit;
    }

    // =========================================================================
    // PRINT VIEW (standalone — no sidebar/header)
    // =========================================================================
    public function printView($id)
    {
        $wo = $this->model->findWithRelations($id);
        if (!$wo) {
            die('Work Order not found.');
        }

        $woItems             = $this->model->getItemsByWorkOrder($id);
        $linkedBills         = $this->model->getLinkedBills($id);
        $linkedWeeklyReports = $this->model->getLinkedWeeklyReports($id);

        $base_url   = $GLOBALS['base_url'];
        $page_title = 'Work Order — ' . ($wo['wo_number'] ?? 'Print');

        require_once dirname(__DIR__, 2) . '/views/phase2/work-orders/print-work-order.php';
        exit;
    }

    // =========================================================================
    // AJAX — Get WOs by Project (for contractor bill dropdown)
    // =========================================================================
    public function ajaxGetByProject()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$projectId) { echo json_encode(['work_orders' => []]); exit; }

        $wos = $this->model->getByProject($projectId);
        echo json_encode(['work_orders' => $wos]);
        exit;
    }

    // =========================================================================
    // AJAX — Get Work Order details and items
    // GET /con-ajax-wo-details?id=N
    // =========================================================================
    public function ajaxGetDetails()
    {
        header('Content-Type: application/json');
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }

        $wo = $this->model->findWithRelations($id);
        if (!$wo) {
            echo json_encode(['success' => false, 'message' => 'Work Order not found']);
            exit;
        }

        $items = $this->model->getItemsByWorkOrder($id);
        echo json_encode([
            'success' => true,
            'wo'    => $wo,
            'items' => $items,
        ]);
        exit;
    }

    // =========================================================================
    // AJAX — Check if existing WO exists for project + design_id
    // GET /con-ajax-wo-check?project_id=N&design_id=M
    // Returns JSON { found, wo, items }
    // =========================================================================
    public function ajaxCheckExistingWO()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);
        $designId  = (int)($_GET['design_id']  ?? 0);

        if (!$projectId || !$designId) {
            echo json_encode(['found' => false]);
            exit;
        }

        $wo = $this->model->findByProjectAndDesign($projectId, $designId);
        if (!$wo) {
            echo json_encode(['found' => false]);
            exit;
        }

        $items = $this->model->getItemsByWorkOrder((int)$wo['id']);
        echo json_encode([
            'found' => true,
            'wo'    => [
                'id'              => $wo['id'],
                'wo_number'       => $wo['wo_number'],
                'status'          => $wo['status'],
                'contractor_name' => $wo['contractor_name'] ?? '',
                'title'           => $wo['title'] ?? '',
                'total_amount'    => $wo['total_amount'] ?? 0,
            ],
            'items' => $items,
        ]);
        exit;
    }

    // =========================================================================
    // (ajaxGetWorkPlans removed, no longer needed in Work Order)
    // =========================================================================
    // AJAX — Get Drawings by Project
    // =========================================================================
    public function ajaxGetDrawings()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$projectId) { echo json_encode(['drawings' => []]); exit; }

        $r = $this->db->query("
            SELECT pd.id, pd.design_type_id, dt.name AS design_type_name
            FROM project_drawings pd
            INNER JOIN design_types dt ON dt.id = pd.design_type_id
            WHERE pd.project_id = {$projectId}
              AND dt.name IS NOT NULL
            ORDER BY dt.name ASC
        ");
        $drawings = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        echo json_encode(['drawings' => $drawings]);
        exit;
    }

    // =========================================================================
    // AJAX — Get Work Types by design_id (project_drawing id)
    // GET /con-ajax-wo-work-types?design_id=N
    // Returns work_types filtered by the drawing's design_type_id
    // =========================================================================
    public function ajaxGetWorkTypesByDesign()
    {
        header('Content-Type: application/json');
        $designId = (int)($_GET['design_id'] ?? 0);
        if (!$designId) {
            echo json_encode(['work_types' => []]);
            exit;
        }

        // Get design_type_id and phase info from project_drawings
        $dr = $this->db->query(
            "SELECT pd.design_type_id, pd.phase_id, pp.phase_name, pp.weight_percentage 
             FROM project_drawings pd 
             LEFT JOIN project_phases pp ON pp.id = pd.phase_id
             WHERE pd.id = {$designId} LIMIT 1"
        );
        if (!$dr || !$dr->num_rows) {
            echo json_encode(['work_types' => []]);
            exit;
        }
        
        $row = $dr->fetch_assoc();
        $designTypeId = (int)$row['design_type_id'];
        $phaseId = (int)$row['phase_id'];
        
        $phaseInfo = [
            'phase_name' => $row['phase_name'] ?? 'N/A',
            'weight_percentage' => $row['weight_percentage'] ?? '0.00'
        ];

        // Get all phase items for this phase from project_phase_items
        $phaseItems = [];
        if ($phaseId > 0) {
            $piRes = $this->db->query(
                "SELECT id, item_name, weight_pct, item_order 
                 FROM project_phase_items 
                 WHERE phase_id = {$phaseId} 
                 ORDER BY item_order ASC, id ASC"
            );
            if ($piRes) {
                $phaseItems = $piRes->fetch_all(MYSQLI_ASSOC);
            }
        }

        // Get all work_types matching this phase first, then fall back to design_type_id
        $escPhaseName = $this->db->real_escape_string($row['phase_name'] ?? '');
        $workTypes = [];

        if (!empty($escPhaseName) && $escPhaseName !== 'N/A') {
            $r = $this->db->query(
                "SELECT id, work_type_name, description
                 FROM work_types
                 WHERE default_phase_name = '{$escPhaseName}'
                    OR default_phase_name LIKE '{$escPhaseName}%'
                    OR '{$escPhaseName}' LIKE CONCAT(default_phase_name, '%')
                 ORDER BY id ASC"
            );
            $workTypes = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        }

        if (empty($workTypes)) {
            $r = $this->db->query(
                "SELECT id, work_type_name, description
                 FROM work_types
                 WHERE design_type_id = {$designTypeId}
                 ORDER BY id ASC"
            );
            $workTypes = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        }
        
        echo json_encode([
            'work_types' => $workTypes, 
            'design_type_id' => $designTypeId,
            'phase_info' => $phaseInfo,
            'phase_items' => $phaseItems
        ]);
        exit;
    }

    // =========================================================================
    // (ajaxGetTargets removed, no longer needed in Work Order)
    // =========================================================================
    // HELPER — Get projects filtered by role (mirrors getProjectRoleFilter in dashboard)
    // =========================================================================
    private function getProjects()
    {
        $uid = $this->userId;
        $rid = $this->userRole;

        // Admin, Director, PM Head, Management Viewer, SEAL CEO → see ALL projects
        if (in_array($rid, [1, 3, 33, 34, 35, 22])) {
            $filter = "1=1";
        } else {
            // All other roles → only their assigned projects
            $filter = "(p.pm_id = $uid
                     OR p.site_engineer_id    = $uid
                     OR p.landman_id          = $uid
                     OR p.struct_engineer_id  = $uid
                     OR p.operation_head_id   = $uid
                     OR p.draftsman_id        = $uid
                     OR p.architect_id        = $uid
                     OR p.estimator_id        = $uid
                     OR p.qc_id              = $uid
                     OR p.operator_id        = $uid
                     OR p.created_by         = $uid)";
        }

        $r = $this->db->query(
            "SELECT p.id, p.name, p.project_status
             FROM projects p
             WHERE p.deleted_at IS NULL AND {$filter}
             ORDER BY p.name ASC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    // =========================================================================
    // HELPER — AJAX Work Plan filter: also role-aware
    // =========================================================================
    private function getProjectRoleFilter(string $alias = 'p'): string
    {
        $uid = $this->userId;
        $rid = $this->userRole;
        if (in_array($rid, [1, 3, 33, 22])) return '1=1';
        return "({$alias}.pm_id = $uid
              OR {$alias}.site_engineer_id   = $uid
              OR {$alias}.landman_id         = $uid
              OR {$alias}.struct_engineer_id = $uid
              OR {$alias}.operation_head_id  = $uid
              OR {$alias}.draftsman_id       = $uid
              OR {$alias}.architect_id       = $uid
              OR {$alias}.estimator_id       = $uid
              OR {$alias}.qc_id             = $uid
              OR {$alias}.operator_id       = $uid
              OR {$alias}.created_by        = $uid)";
    }
}
