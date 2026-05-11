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
        return in_array($this->userRole, [1, 3, 15, 22]);
    }

    private function canApprove()
    {
        return in_array($this->userRole, [1, 3, 22]);
    }

    private function canMarkComplete()
    {
        return in_array($this->userRole, [1, 3, 15, 22]);
    }

    private function canQcApprove()
    {
        return in_array($this->userRole, [1, 3, 13]);
    }

    private function canDelete()
    {
        return in_array($this->userRole, [1, 3]);
    }

    // =========================================================================
    // LIST
    // =========================================================================
    public function index()
    {
        $workOrders = $this->model->getAllWithRelations($this->userId, $this->userRole);

        $this->view('phase2/work-orders/work-order-list', [
            'current_route' => 'con-work-orders',
            'page_title'    => 'Work Orders',
            'workOrders'    => $workOrders,
            'canCreate'     => $this->canCreate(),
            'userRole'      => $this->userRole,
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
        $quantity  = (float)($_POST['quantity']   ?? 0);
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
            'work_plan_id'      => !empty($_POST['work_plan_id']) ? (int)$_POST['work_plan_id'] : null,
            'created_by'        => $this->userId,
        ];

        if (empty($data['project_id']) || empty($data['contractor_id']) || empty($data['title'])) {
            setError('Project, Contractor and Title are required.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-order-create");
            exit;
        }

        $id = $this->model->create($data);
        if ($id) {
            $operator = $this->contractorModel->find($data['contractor_id']);
            $project  = $this->db->query("SELECT name FROM projects WHERE id = " . (int)$data['project_id'])->fetch_assoc();

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
                'request_amount'=> $data['total_amount'],
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

        $linkedBills         = $this->model->getLinkedBills($id);
        $linkedWeeklyReports = $this->model->getLinkedWeeklyReports($id);

        $this->view('phase2/work-orders/show-work-order', [
            'current_route'       => 'con-work-orders',
            'page_title'          => 'Work Order — ' . $wo['wo_number'],
            'wo'                  => $wo,
            'linkedBills'         => $linkedBills,
            'linkedWeeklyReports' => $linkedWeeklyReports,
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
        if (!$wo || $wo['status'] !== 'draft') {
            setError('Only draft Work Orders can be edited.');
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

        // Pre-load work plans for the selected project
        $workPlans = [];
        if (!empty($wo['work_plan_id'])) {
            $workPlans = $this->model->getWorkPlansByProject($wo['project_id']);
        }

        $this->view('phase2/work-orders/edit-work-order', [
            'current_route' => 'con-work-orders',
            'page_title'    => 'Edit Work Order',
            'wo'            => $wo,
            'projects'      => $projects,
            'contractors'   => $contractors,
            'workTypes'     => $workTypes,
            'workPlans'     => $workPlans,
        ]);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================
    public function update($id)
    {
        $wo = $this->model->find($id);
        if (!$wo || $wo['status'] !== 'draft') {
            setError('Only draft Work Orders can be updated.');
            header("Location: " . $GLOBALS['base_url'] . "/con-work-order/{$id}");
            exit;
        }

        $unitRate = (float)($_POST['unit_rate']  ?? 0);
        $quantity = (float)($_POST['quantity']   ?? 0);
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
            'work_plan_id'      => !empty($_POST['work_plan_id']) ? (int)$_POST['work_plan_id'] : null,
        ];

        if ($this->model->update($id, $data)) {
            setSuccess('Work Order updated.');
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

        if ($this->model->approve($id, $this->userId)) {
            $this->syncApprovalRequest($id, 'approved');
            setSuccess('Work Order approved and is now active!');
        } else {
            setError('Approval failed. Work Order must be in draft status.');
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
        if ($this->model->reject($id, $this->userId, $reason)) {
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
    // AJAX — Get Work Plans by Project (for cascading dropdown)
    // =========================================================================
    public function ajaxGetWorkPlans()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$projectId) { echo json_encode(['work_plans' => []]); exit; }

        $plans = $this->model->getWorkPlansByProject($projectId);
        echo json_encode(['work_plans' => $plans]);
        exit;
    }

    // =========================================================================
    // AJAX — Get Drawings by Project
    // =========================================================================
    public function ajaxGetDrawings()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$projectId) { echo json_encode(['drawings' => []]); exit; }

        $r = $this->db->query("
            SELECT pd.id, dt.name AS design_type_name
            FROM project_drawings pd
            LEFT JOIN design_types dt ON dt.id = pd.design_type_id
            WHERE pd.project_id = {$projectId}
            ORDER BY dt.name ASC
        ");
        $drawings = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
        echo json_encode(['drawings' => $drawings]);
        exit;
    }

    // =========================================================================
    // AJAX — Get Targets by Work Plan (for cascading dropdown)
    // =========================================================================
    public function ajaxGetTargets()
    {
        header('Content-Type: application/json');
        $planId = (int)($_GET['work_plan_id'] ?? 0);
        if (!$planId) { echo json_encode(['targets' => []]); exit; }

        $targets = $this->model->getTargetsByWorkPlan($planId);
        echo json_encode(['targets' => $targets]);
        exit;
    }

    // =========================================================================
    // HELPER — Get projects filtered by role (mirrors getProjectRoleFilter in dashboard)
    // =========================================================================
    private function getProjects()
    {
        $uid = $this->userId;
        $rid = $this->userRole;

        // Admin, Director, PM Head → see ALL projects
        if (in_array($rid, [1, 3, 22])) {
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
        if (in_array($rid, [1, 3, 22])) return '1=1';
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
