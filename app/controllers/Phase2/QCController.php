<?php
namespace Phase2;

use Controller;
use Project;
use Con_WorkType;
use Con_MaterialReceiving;
use Con_QCChecklist;
use Con_QCInspection;
use Con_QCInspectionItem;
use Con_QCMaterialTest;

class QCController extends Controller
{
    protected $db;
    protected $projectModel;
    protected $workTypeModel;
    protected $materialReceivingModel;
    protected $qcChecklistModel;
    protected $qcInspectionModel;
    protected $qcInspectionItemModel;
    protected $qcMaterialTestModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->projectModel = new Project($this->db);
        require_once __DIR__ . '/../../models/Con_WorkType.php';
        $this->workTypeModel = new \Con_WorkType($this->db);
        require_once __DIR__ . '/../../models/Con_MaterialReceiving.php';
        $this->materialReceivingModel = new \Con_MaterialReceiving($this->db);
        
        $this->qcChecklistModel = new \Con_QCChecklist($this->db);
        $this->qcInspectionModel = new \Con_QCInspection($this->db);
        $this->qcInspectionItemModel = new \Con_QCInspectionItem($this->db);
        $this->qcMaterialTestModel = new \Con_QCMaterialTest($this->db);
    }

    private function getProjectRoleFilter($alias = 'p')
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        $roleId = $_SESSION['user']['role_id'] ?? 0;
        $baseFilter = "{$alias}.under_construction = 1";
        if (in_array($roleId, [1, 3, 13, 20, 22, 24, 25])) return $baseFilter;
        return "{$baseFilter} AND ({$alias}.pm_id = {$userId} OR {$alias}.site_engineer_id = {$userId} OR {$alias}.landman_id = {$userId} OR {$alias}.struct_engineer_id = {$userId} OR {$alias}.operation_head_id = {$userId} OR {$alias}.draftsman_id = {$userId} OR {$alias}.architect_id = {$userId} OR {$alias}.estimator_id = {$userId} OR {$alias}.qc_id = {$userId} OR {$alias}.operator_id = {$userId} OR {$alias}.created_by = {$userId})";
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // ── Inspections ────────────────────────────────────────────────────
        $inspections = $this->qcInspectionModel->getAll();

        // ── GRN Material Tests (linked to challan) ─────────────────────────
        $rGrnTests = $this->db->query("
            SELECT mt.*, p.name AS project_name,
                   u.full_name AS created_by_name
            FROM qc_material_tests mt
            LEFT JOIN projects p ON p.id = mt.project_id
            LEFT JOIN users u ON u.id = mt.created_by
            WHERE mt.material_receiving_id IS NOT NULL
            ORDER BY mt.id DESC LIMIT 15
        ");
        $grnMaterialTests = $rGrnTests ? $rGrnTests->fetch_all(MYSQLI_ASSOC) : [];

        // ── Site Work Tests (Other QC Test — no GRN) ───────────────────────
        $rSiteTests = $this->db->query("SELECT mt.*, p.name AS project_name,
                   wt.work_type_name AS work_type_name,
                   tt.name AS test_type_name,
                   u.full_name AS created_by_name
            FROM qc_material_tests mt
            LEFT JOIN projects p ON p.id = mt.project_id
            LEFT JOIN work_types wt ON wt.id = mt.work_type_id
            LEFT JOIN qc_test_types tt ON tt.id = mt.test_type_id
            LEFT JOIN users u ON u.id = mt.created_by
            WHERE mt.material_receiving_id IS NULL
            ORDER BY mt.id DESC LIMIT 15
        ");
        $siteWorkTests = $rSiteTests ? $rSiteTests->fetch_all(MYSQLI_ASSOC) : [];

        // ── KPI 1: Assigned Projects (only QC-assigned under construction) ──────────────────
        if (in_array($roleId, [1, 3, 15, 22])) {
            $rAssigned = $this->db->query("SELECT COUNT(*) AS c FROM projects WHERE deleted_at IS NULL AND under_construction = 1");
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM projects WHERE qc_id = ? AND deleted_at IS NULL AND under_construction = 1");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $rAssigned = $stmt->get_result();
        }
        $assignedProjects = $rAssigned ? (int)$rAssigned->fetch_assoc()['c'] : 0;

        // ── KPI 2: Purchase Order Pending ─────────────────────────────────
        $rPOCount = $this->db->query("
            SELECT COUNT(*) AS c 
            FROM purchase_orders po
            LEFT JOIN purchase_requests pr ON pr.id = po.request_id
            LEFT JOIN projects p ON p.id = pr.project_id
            WHERE (p.deleted_at IS NULL OR p.id IS NULL) 
            AND (po.status NOT IN ('Fully Received', 'Cancelled') OR po.status IS NULL)
        ");
        $pendingPOCount = $rPOCount ? (int)$rPOCount->fetch_assoc()['c'] : 0;

        // ── KPI 3: Pending GRNs awaiting QC ───────────────────────────────
        $rGRNCount = $this->db->query("SELECT COUNT(*) AS c FROM material_receiving WHERE overall_status = 'Pending QC'");
        $pendingGRNsCount = $rGRNCount ? (int)$rGRNCount->fetch_assoc()['c'] : 0;

        // ── KPI 4: Recent Inspections count ───────────────────────────────
        $rInsCount = $this->db->query("SELECT COUNT(*) AS c FROM qc_inspections");
        $totalInspections = $rInsCount ? (int)$rInsCount->fetch_assoc()['c'] : 0;

        // ── KPI 5: Total Material Tests ───────────────────────────────────
        $rTestCount = $this->db->query("SELECT COUNT(*) AS c FROM qc_material_tests");
        $totalMaterialTests = $rTestCount ? (int)$rTestCount->fetch_assoc()['c'] : 0;

        // ── KPI 6: Pending Weekly Reports awaiting QC ────────────────────
        try {
            $rWkCount = $this->db->query("SELECT COUNT(*) AS c FROM phase_weekly_progress WHERE entry_status = 'Submitted' AND (qc_status = 'Pending' OR qc_status IS NULL)");
            $pendingWeeklyCount = $rWkCount ? (int)$rWkCount->fetch_assoc()['c'] : 0;
        } catch (\Throwable $e) {
            $pendingWeeklyCount = 0;
        }

        // ── Project Progress (only assigned projects) ─────────────────────
        // Migrated: uses phase_monthly_targets.actual_progress weighted by project_phases.weight_percentage
        $rProj = $this->db->query("
            SELECT p.id, p.name, p.project_status,
                   COALESCE(l.name,'') AS location_name,
                   COALESCE(prog.progress_pct,0) AS progress_pct
            FROM projects p
            LEFT JOIN locations l ON l.id = p.location_id
            LEFT JOIN (
                SELECT pp.project_id,
                       SUM((COALESCE(pp.actual_progress,0) / 100) * COALESCE(pp.weight_percentage,0)) AS progress_pct
                FROM project_phases pp
                GROUP BY pp.project_id
            ) prog ON prog.project_id = p.id
            WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . "
            ORDER BY p.id DESC LIMIT 100");
        $projects = $rProj ? $rProj->fetch_all(MYSQLI_ASSOC) : [];

        $projectIds = array_column($projects, 'id');
        $dynamicProgressMap = $this->calculatePhaseBasedProgress($projectIds);
        foreach ($projects as &$pr) {
            $pid = (int)$pr['id'];
            $pr['progress_pct'] = isset($dynamicProgressMap[$pid]) ? (float)$dynamicProgressMap[$pid]['actual'] : 0.0;
        }
        unset($pr);

        // ── Pending Targets (Removed) ─────────────────────────
        $pendingTargets = [];

        // ── Pending GRNs (full rows for table) ───────────────────────────
        $rPendingGRNsQC = $this->db->query("
            SELECT mr.id, mr.receiving_date,
                   p.name AS project_name,
                   u.full_name AS received_by_name
            FROM material_receiving mr
            JOIN purchase_orders po ON po.id = mr.purchase_order_id
            JOIN purchase_requests pr ON pr.id = po.request_id
            LEFT JOIN projects p ON p.id = pr.project_id
            LEFT JOIN users u ON u.id = mr.received_by
            WHERE mr.overall_status = 'Pending QC'
            ORDER BY mr.id DESC LIMIT 10");
        $pendingGRNsQC = $rPendingGRNsQC ? $rPendingGRNsQC->fetch_all(MYSQLI_ASSOC) : [];

        // ── Pending Weekly Reports (full rows for table) ──────────────────
        try {
            $rPendingWeeklyQC = $this->db->query("
                SELECT pwp.id, 
                       pwp.project_id,
                       COALESCE(pwp.submitted_at, pwp.week_end_date) AS report_date, 
                       pwp.week_number AS week_no,
                       p.name AS project_name,
                       u.full_name AS created_by_name,
                       pwp.target_id,
                       pmt.target_month,
                       pp.phase_name
                FROM phase_weekly_progress pwp
                LEFT JOIN projects p ON p.id = pwp.project_id
                LEFT JOIN users u ON u.id = pwp.submitted_by
                LEFT JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
                LEFT JOIN project_phases pp ON pp.id = pmt.phase_id
                WHERE pwp.entry_status = 'Submitted' AND (pwp.qc_status = 'Pending' OR pwp.qc_status IS NULL)
                ORDER BY pwp.submitted_at DESC, pwp.id DESC LIMIT 100");
            $pendingWeeklyReportsQC = $rPendingWeeklyQC ? $rPendingWeeklyQC->fetch_all(MYSQLI_ASSOC) : [];
        } catch (\Throwable $e) {
            $pendingWeeklyReportsQC = [];
        }

        // ── Architecture Engineering ──────────────────────────────────────────
        $rAE = $this->db->query("
            SELECT ae.*, p.name AS project_name, u.full_name AS user_name
            FROM architect_engineering ae
            LEFT JOIN projects p ON p.id = ae.project_id
            LEFT JOIN users u ON u.id = ae.user_id
            WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . "
            ORDER BY ae.id DESC LIMIT 10
        ");
        $architectEngineering = $rAE ? $rAE->fetch_all(MYSQLI_ASSOC) : [];

        // ── Project Drawings ───────────────────────────────────────────────
        $rDrawings = $this->db->query("
            SELECT pd.*, p.name AS project_name, dt.name AS design_type_name
            FROM project_drawings pd
            LEFT JOIN projects p ON p.id = pd.project_id
            LEFT JOIN design_types dt ON dt.id = pd.design_type_id
            WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . "
            ORDER BY pd.id DESC LIMIT 10
        ");
        $draftDrawings = $rDrawings ? $rDrawings->fetch_all(MYSQLI_ASSOC) : [];

        // ── Pending Labor Requisitions (QC needs to approve payment_requested) ──
        $rPendingLaborReq = $this->db->query("
            SELECT lr.id, lr.requisition_no, lr.status, lr.requisition_date,
                   lr.labor_count, lr.per_labor_rate, lr.working_days, lr.total_amount,
                   lr.purpose, lr.labor_type,
                   p.name AS project_name,
                   u.full_name AS requested_by_name
            FROM con_labor_requisitions lr
            LEFT JOIN projects p ON p.id = lr.project_id
            LEFT JOIN users u ON u.id = lr.requested_by
            WHERE lr.status = 'payment_requested'
            ORDER BY lr.created_at DESC LIMIT 20");
        $pendingLaborReqQC = $rPendingLaborReq ? $rPendingLaborReq->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/qc/qc-dashboard', [
            'architectEngineering'   => $architectEngineering,
            'draftDrawings'          => $draftDrawings,
            'current_route'          => 'qc-dashboard',
            'page_title'             => 'QC Dashboard',
            'inspections'            => $inspections,
            'grnMaterialTests'       => $grnMaterialTests,
            'siteWorkTests'          => $siteWorkTests,
            'materialTests'          => array_merge($grnMaterialTests, $siteWorkTests), // backward compat
            'projects'               => $projects,
            'pendingTargets'         => $pendingTargets,
            'pendingGRNsQC'          => $pendingGRNsQC,
            'pendingWeeklyReportsQC' => $pendingWeeklyReportsQC,
            // KPI counts
            'assignedProjects'       => $assignedProjects,
            'pendingPOCount'         => $pendingPOCount,
            'pendingGRNsCount'       => $pendingGRNsCount,
            'totalInspections'       => $totalInspections,
            'totalMaterialTests'     => $totalMaterialTests,
            'pendingWeeklyCount'     => $pendingWeeklyCount,
            'pendingLaborReqQC'      => $pendingLaborReqQC,
        ]);
    }

    // =========================================================================
    // CHECKLIST MANAGEMENT
    // =========================================================================
    public function checklistsIndex()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $selectedWorkTypeId = (int)($_GET['work_type_id'] ?? 0);

        // Get all work types with checklist count
        $rWt = $this->db->query("
            SELECT wt.id, wt.work_type_name,
                   (SELECT COUNT(*) FROM qc_checklists qc WHERE qc.work_type_id = wt.id) AS checklist_count
            FROM work_types wt
            ORDER BY wt.work_type_name ASC
        ");
        $workTypes = $rWt ? $rWt->fetch_all(MYSQLI_ASSOC) : [];

        $selectedWorkType = null;
        $checklists = [];

        if ($selectedWorkTypeId) {
            // Get selected work type
            $stWt = $this->db->prepare("SELECT * FROM work_types WHERE id = ?");
            $stWt->bind_param('i', $selectedWorkTypeId);
            $stWt->execute();
            $selectedWorkType = $stWt->get_result()->fetch_assoc();

            // Get checklists for this work type
            $checklists = $this->qcChecklistModel->getByWorkType($selectedWorkTypeId);
        }

        $this->view('phase2/qc/checklists/index', [
            'current_route'        => 'qc-checklists',
            'page_title'           => 'QC Checklist Management',
            'workTypes'            => $workTypes,
            'selectedWorkTypeId'   => $selectedWorkTypeId,
            'selectedWorkType'     => $selectedWorkType,
            'checklists'           => $checklists,
        ]);
    }

    public function checklistStore()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $workTypeId = (int)($_POST['work_type_id'] ?? 0);
        $itemName   = trim($_POST['checklist_item_name'] ?? '');

        if (!$workTypeId || empty($itemName)) {
            setError('Work type and item name are required.');
            header("Location: {$GLOBALS['base_url']}/qc-checklists?work_type_id={$workTypeId}");
            exit;
        }

        $data = ['work_type_id' => $workTypeId, 'checklist_item_name' => $itemName, 'created_by' => $userId];
        if ($this->qcChecklistModel->create($data)) {
            setSuccess('Checklist item added successfully!');
        } else {
            setError('Failed to add checklist item.');
        }
        header("Location: {$GLOBALS['base_url']}/qc-checklists?work_type_id={$workTypeId}");
        exit;
    }

    public function checklistUpdate()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id         = (int)($_POST['id'] ?? 0);
        $workTypeId = (int)($_POST['work_type_id'] ?? 0);
        $itemName   = trim($_POST['checklist_item_name'] ?? '');

        if (!$id || empty($itemName)) {
            setError('Item name is required.');
            header("Location: {$GLOBALS['base_url']}/qc-checklists?work_type_id={$workTypeId}");
            exit;
        }

        $stmt = $this->db->prepare("UPDATE qc_checklists SET checklist_item_name = ? WHERE id = ?");
        $stmt->bind_param('si', $itemName, $id);
        if ($stmt->execute()) {
            setSuccess('Checklist item updated!');
        } else {
            setError('Update failed.');
        }
        header("Location: {$GLOBALS['base_url']}/qc-checklists?work_type_id={$workTypeId}");
        exit;
    }

    public function checklistDelete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id         = (int)($_POST['id'] ?? 0);
        $workTypeId = (int)($_POST['work_type_id'] ?? 0);

        if ($id) {
            $stmt = $this->db->prepare("DELETE FROM qc_checklists WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute() ? setSuccess('Checklist item deleted.') : setError('Delete failed.');
        }
        header("Location: {$GLOBALS['base_url']}/qc-checklists?work_type_id={$workTypeId}");
        exit;
    }


    private function uploadFile($fileData, $uploadPath = 'uploads/qc/')
    {
        if (empty($fileData['name'])) return null;

        
        $baseDir = __DIR__ . '/../../../public/' . $uploadPath;
        if (!is_dir($baseDir)) mkdir($baseDir, 0777, true);

        $ext = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $newName = uniqid('qc_') . '_' . time() . '.' . $ext;
        
        if (move_uploaded_file($fileData['tmp_name'], $baseDir . $newName)) {
            return $uploadPath . $newName;
        }
        return null;
    }

    public function showInspection($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = (int)$id;

        // Fetch main inspection with joins
        $stmt = $this->db->prepare("
            SELECT qi.*,
                   p.name  AS project_name,
                   wt.work_type_name,
                   u.full_name AS inspector_name
            FROM qc_inspections qi
            LEFT JOIN projects p   ON p.id  = qi.project_id
            LEFT JOIN work_types wt ON wt.id = qi.work_type_id
            LEFT JOIN users u      ON u.id  = qi.inspector_id
            WHERE qi.id = ?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $ins = $stmt->get_result()->fetch_assoc();

        if (!$ins) {
            setError('Inspection not found.');
            header("Location: {$GLOBALS['base_url']}/qc-dashboard");
            exit;
        }

        // Fetch checklist items with checklist name
        $stmtItems = $this->db->prepare("
            SELECT qii.*, qc.checklist_item_name
            FROM qc_inspection_items qii
            LEFT JOIN qc_checklists qc ON qc.id = qii.qc_checklist_id
            WHERE qii.qc_inspection_id = ?
            ORDER BY qii.id ASC
        ");
        $stmtItems->bind_param('i', $id);
        $stmtItems->execute();
        $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

        // Fetch related project drawings via work_type → design_type → project_drawings
        $stmtDrawings = $this->db->prepare("
            SELECT pd.*,
                   dt.name AS design_type_name,
                   u.full_name AS designer_user_name,
                   ab.full_name AS approved_by_name
            FROM project_drawings pd
            LEFT JOIN design_types dt  ON dt.id  = pd.design_type_id
            LEFT JOIN users u          ON u.id   = pd.user_id
            LEFT JOIN users ab         ON ab.id  = pd.approved_by_id
            WHERE pd.project_id = ?
              AND pd.design_type_id = (
                  SELECT design_type_id FROM work_types WHERE id = ? LIMIT 1
              )
            ORDER BY pd.id DESC
        ");
        $stmtDrawings->bind_param('ii', $ins['project_id'], $ins['work_type_id']);
        $stmtDrawings->execute();
        $drawings = $stmtDrawings->get_result()->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/qc/show-inspection', [
            'current_route' => 'qc-dashboard',
            'page_title'    => 'Inspection #' . $id,
            'ins'           => $ins,
            'items'         => $items,
            'drawings'      => $drawings,
        ]);
    }

    public function createInspection()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        if (in_array($roleId, [1, 3])) {
            $projects = $this->projectModel->getAll();
        } else {
            $projects = $this->db->query("SELECT id, name FROM projects WHERE qc_id = $userId AND deleted_at IS NULL ORDER BY name")->fetch_all(MYSQLI_ASSOC);
        }

        $workTypes = $this->workTypeModel->getAll();

        $this->view('phase2/qc/inspection-form', [
            'current_route' => 'qc-inspection-create',
            'page_title' => 'Create QC Inspection',
            'projects' => $projects,
            'workTypes' => $workTypes
        ]);
    }

    public function storeInspection()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        $data = [
            'project_id' => (int)($_POST['project_id'] ?? 0),
            'work_type_id' => (int)($_POST['work_type_id'] ?? 0),
            'inspector_id' => $userId,
            'inspection_date' => $_POST['inspection_date'] ?? date('Y-m-d'),
            'overall_status' => $_POST['overall_status'] ?? 'failed',
            'comments' => $_POST['comments'] ?? '',
            'attachment' => isset($_FILES['overall_attachment']) ? $this->uploadFile($_FILES['overall_attachment']) : null
        ];

        if (!$data['project_id'] || !$data['work_type_id']) {
            setError('Project and Work Type are required.');
            header("Location: {$GLOBALS['base_url']}/qc-inspection-create");
            exit;
        }

        $inspectionId = $this->qcInspectionModel->create($data);
        if ($inspectionId) {
            $checklistIds = $_POST['qc_checklist_id'] ?? [];
            $isPassed = $_POST['is_passed'] ?? [];
            $observationNotes = $_POST['observation_note'] ?? [];

            foreach ($checklistIds as $index => $cId) {
                if (!empty($cId)) {
                    $itemFile = null;
                    if (isset($_FILES['item_attachment']['name'][$index]) && !empty($_FILES['item_attachment']['name'][$index])) {
                        $fData = [
                            'name' => $_FILES['item_attachment']['name'][$index],
                            'tmp_name' => $_FILES['item_attachment']['tmp_name'][$index]
                        ];
                        $itemFile = $this->uploadFile($fData);
                    }
                    $itemData = [
                        'qc_inspection_id' => $inspectionId,
                        'qc_checklist_id' => (int)$cId,
                        'is_passed' => isset($isPassed[$index]) ? 1 : 0,
                        'observation_note' => $observationNotes[$index] ?? '',
                        'attachment' => $itemFile
                    ];
                    $this->qcInspectionItemModel->create($itemData);
                }
            }

            // Logic for NCR (Non-Conformance Report)
            if ($data['overall_status'] === 'failed' || $data['overall_status'] === 'rework_needed') {
                $project = $this->projectModel->find($data['project_id']);
                if ($project && !empty($project['pm_id'])) {
                    $pmId = (int)$project['pm_id'];
                    $title = 'Non-Conformance Report (NCR)';
                    $msg = 'QC Inspection failed for project ' . htmlspecialchars($project['name']) . '. Re-work needed.';
                    $url = $GLOBALS['base_url'] . '/qc-dashboard';
                    
                    $stmt = $this->db->prepare("INSERT INTO head_notifications (user_id, type, title, message, url) VALUES (?, 'ncr', ?, ?, ?)");
                    $stmt->bind_param('isss', $pmId, $title, $msg, $url);
                    $stmt->execute();
                }
            }

            setSuccess('Inspection saved successfully.');
            header("Location: {$GLOBALS['base_url']}/qc-dashboard");
            exit;
        }

        setError('Failed to create Inspection.');
        header("Location: {$GLOBALS['base_url']}/qc-inspection-create");
        exit;
    }

    public function createMaterialTest()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Fetch all material receivings (all statuses, for challan dropdown)
        $receivings = $this->materialReceivingModel->getAll();

        if (in_array($roleId, [1, 3, 15, 22, 13, 24, 25])) {
            $projectsQuery = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND under_construction = 1 ORDER BY name");
        } else {
            // For QC role: show projects assigned to this user as qc_id
            $stmtP = $this->db->prepare("
                SELECT id, name FROM projects
                    OR pm_id = $userId
                    OR site_engineer_id = $userId
                    OR struct_engineer_id = $userId
                    OR operation_head_id = $userId
                  )
                ORDER BY name
            ");
        }
        $projects = $projectsQuery ? $projectsQuery->fetch_all(MYSQLI_ASSOC) : [];

        // If no projects found (qc_id not set on any project), show all projects as fallback
        if (empty($projects)) {
            $fallback = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name");
            $projects = $fallback ? $fallback->fetch_all(MYSQLI_ASSOC) : [];
        }

        $workTypes = $this->workTypeModel->getAll();
        
        require_once __DIR__ . '/../../models/Con_QCTestType.php';
        $testTypeModel = new \Con_QCTestType($this->db);
        $testTypes = $testTypeModel->getAll();

        $this->view('phase2/qc/material-test-form', [
            'current_route' => 'qc-material-test-create',
            'page_title' => 'Create Material Test',
            'receivings' => $receivings,
            'projects' => $projects,
            'workTypes' => $workTypes,
            'testTypes' => $testTypes
        ]);
    }

    public function ajaxGetReceivingItems()
    {
        header('Content-Type: application/json');
        $receivingId = $_GET['receiving_id'] ?? 0;
        
        if (!$receivingId) {
            echo json_encode([]);
            exit;
        }

        $items = $this->materialReceivingModel->getItems($receivingId);
        echo json_encode($items);
        exit;
    }

    public function storeMaterialTest()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        $testSource = $_POST['test_source'] ?? 'grn';
        $materialReceivingId = (int)($_POST['material_receiving_id'] ?? 0);
        $testTypeId = !empty($_POST['test_type_id']) ? (int)$_POST['test_type_id'] : null;

        $testName = '';
        if ($testSource === 'grn') {
            $testName = $_POST['test_name'] ?? 'GRN General Inspection';
        } elseif ($testTypeId) {
            $testNameData = $this->db->query("SELECT name FROM qc_test_types WHERE id = " . $testTypeId)->fetch_assoc();
            if ($testNameData) $testName = $testNameData['name'];
        }

        $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        if ($testSource === 'grn' && $materialReceivingId) {
            $grnData = $this->db->query("
                SELECT pr.project_id 
                FROM material_receiving mr 
                JOIN purchase_orders po ON po.id = mr.purchase_order_id 
                JOIN purchase_requests pr ON pr.id = po.request_id 
                WHERE mr.id = $materialReceivingId
            ")->fetch_assoc();
            
            if ($grnData && !empty($grnData['project_id'])) {
                $projectId = (int)$grnData['project_id'];
            }
        }

        $data = [
            'project_id' => $projectId,
            'work_type_id' => !empty($_POST['work_type_id']) ? (int)$_POST['work_type_id'] : null,
            'test_type_id' => ($testSource == 'other') ? $testTypeId : null,
            'material_receiving_id' => ($testSource == 'grn' && $materialReceivingId) ? $materialReceivingId : null,
            'test_name' => $testName,
            'test_date' => !empty($_POST['test_date']) ? $_POST['test_date'] : null,
            'age_of_sample' => !empty($_POST['age_of_sample']) ? (int)$_POST['age_of_sample'] : null,
            'required_value' => $_POST['required_value'] ?? '',
            'result_value' => $_POST['result_value'] ?? '',
            'test_status' => $_POST['test_status'] ?? 'fail',
            'lab_report_link' => $_POST['lab_report_link'] ?? '',
            'created_by' => $userId,
            'attachment' => isset($_FILES['test_attachment']) && $_FILES['test_attachment']['error'] === 0 ? $this->uploadFile($_FILES['test_attachment']) : null
        ];

        if ($testSource == 'grn' && !$data['material_receiving_id']) {
            setError('Material Receiving is required for GRN tests.');
            header("Location: {$GLOBALS['base_url']}/qc-material-test-create");
            exit;
        } elseif ($testSource == 'other' && (!$data['project_id'] || !$data['work_type_id'])) {
            setError('Project and Work Type are required for Other QC tests.');
            header("Location: {$GLOBALS['base_url']}/qc-material-test-create");
            exit;
        }

        if ($testSource == 'other' && !$testTypeId) {
            setError('Test Type is required for Other QC tests.');
            header("Location: {$GLOBALS['base_url']}/qc-material-test-create");
            exit;
        }

        if ($this->qcMaterialTestModel->create($data)) {
            setSuccess('Material Test logged successfully.');
            header("Location: {$GLOBALS['base_url']}/qc-dashboard");
            exit;
        }

        setError('Failed to log Material Test.');
        header("Location: {$GLOBALS['base_url']}/qc-material-test-create");
        exit;
    }

    // AJAX Endpoint to get checklists by work type
    public function ajaxGetChecklists()
    {
        header('Content-Type: application/json');
        $workTypeId = (int)($_GET['work_type_id'] ?? 0);

        if (!$workTypeId) {
            echo json_encode(['checklists' => []]);
            exit;
        }

        $checklists = $this->qcChecklistModel->getByWorkType($workTypeId);
        echo json_encode(['checklists' => $checklists]);
        exit;
    }

    public function testTypesIndex()
    {
        require_once __DIR__ . '/../../models/Con_QCTestType.php';
        $testTypeModel = new \Con_QCTestType($this->db);
        $testTypes = $testTypeModel->getAll();

        $this->view('phase2/qc/test-types/index', [
            'current_route' => 'qc-test-types',
            'page_title' => 'Manage QC Test Types',
            'testTypes' => $testTypes
        ]);
    }

    public function testTypesStore()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';

        if (empty($name)) {
            setError('Test Type name is required.');
            header("Location: {$GLOBALS['base_url']}/qc-test-types");
            exit;
        }

        require_once __DIR__ . '/../../models/Con_QCTestType.php';
        $testTypeModel = new \Con_QCTestType($this->db);
        if ($testTypeModel->create(['name' => $name, 'description' => $description])) {
            setSuccess('Test Type created successfully.');
        } else {
            setError('Failed to create Test Type.');
        }
        header("Location: {$GLOBALS['base_url']}/qc-test-types");
        exit;
    }

    public function testTypesUpdate()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = (int)($_POST['id'] ?? 0);
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';

        if (!$id || empty($name)) {
            setError('Invalid data for updating Test Type.');
            header("Location: {$GLOBALS['base_url']}/qc-test-types");
            exit;
        }

        require_once __DIR__ . '/../../models/Con_QCTestType.php';
        $testTypeModel = new \Con_QCTestType($this->db);
        if ($testTypeModel->update($id, ['name' => $name, 'description' => $description])) {
            setSuccess('Test Type updated successfully.');
        } else {
            setError('Failed to update Test Type.');
        }
        header("Location: {$GLOBALS['base_url']}/qc-test-types");
        exit;
    }

    public function testTypesDelete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            setError('Invalid Test Type ID.');
            header("Location: {$GLOBALS['base_url']}/qc-test-types");
            exit;
        }

        require_once __DIR__ . '/../../models/Con_QCTestType.php';
        $testTypeModel = new \Con_QCTestType($this->db);
        if ($testTypeModel->delete($id)) {
            setSuccess('Test Type deleted successfully.');
        } else {
            setError('Failed to delete Test Type.');
        }
        header("Location: {$GLOBALS['base_url']}/qc-test-types");
        exit;
    }

    private function calculatePhaseBasedProgress(array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        $idsList = implode(',', array_map('intval', $projectIds));

        // Fetch received quantities by unit for these projects to display physical quantity usage
        $receivedQtyMap = [];
        $receivedQtyRes = $this->db->query("
            SELECT pr.project_id, m.unit, COALESCE(SUM(mri.received_quantity), 0) AS total_received
            FROM material_receiving_items mri
            INNER JOIN material_receiving mr ON mr.id = mri.receiving_id
            INNER JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
            INNER JOIN con_materials m ON m.id = poi.material_id
            INNER JOIN purchase_orders po ON po.id = mr.purchase_order_id
            INNER JOIN purchase_requests pr ON pr.id = po.request_id
            WHERE mr.overall_status = 'Accepted' AND pr.project_id IN ($idsList)
            GROUP BY pr.project_id, m.unit
        ");
        if ($receivedQtyRes) {
            while ($row = $receivedQtyRes->fetch_assoc()) {
                $pid = (int)$row['project_id'];
                $u = strtolower(trim($row['unit'] ?? ''));
                $receivedQtyMap[$pid][$u] = (float)$row['total_received'];
            }
        }

        // 1. Fetch weight_percentage for the phases of these projects
        $phasesRes = $this->db->query("
            SELECT id, project_id, phase_name, weight_percentage, phase_order
            FROM project_phases
            WHERE project_id IN ($idsList)
            ORDER BY project_id, phase_order ASC
        ");
        $phases = $phasesRes ? $phasesRes->fetch_all(MYSQLI_ASSOC) : [];

        // Group phases by project
        $projectPhases = [];
        foreach ($phases as $phase) {
            $projectPhases[(int)$phase['project_id']][] = $phase;
        }

        // 2. Fetch all phase items (work items)
        $itemsRes = $this->db->query("
            SELECT ppi.id AS item_id, ppi.phase_id, ppi.item_name, ppi.item_order, ppi.weight_pct AS item_weight,
                   pp.project_id
            FROM project_phase_items ppi
            INNER JOIN project_phases pp ON pp.id = ppi.phase_id
            WHERE pp.project_id IN ($idsList)
            ORDER BY ppi.phase_id, ppi.item_order ASC
        ");
        $items = $itemsRes ? $itemsRes->fetch_all(MYSQLI_ASSOC) : [];

        $phaseItems = [];
        foreach ($items as $item) {
            $phaseItems[(int)$item['phase_id']][] = $item;
        }

        // 3. Fetch all BOQ details for these projects' phases
        $dtRes = $this->db->query("
            SELECT ppi.phase_id, ppi.id AS phase_item_id, ppid.id AS detail_id,
                   ppid.work_type_id, ppid.total_boq_qty, ppid.weight_pct AS detail_weight,
                   pp.project_id, ppid.unit
            FROM project_phase_items ppi
            INNER JOIN project_phase_item_details ppid ON ppid.phase_item_id = ppi.id
            INNER JOIN project_phases pp ON pp.id = ppi.phase_id
            WHERE pp.project_id IN ($idsList)
        ");
        $details = $dtRes ? $dtRes->fetch_all(MYSQLI_ASSOC) : [];

        // Group details by project_id and phase_id
        $projectPhaseDetails = [];
        $itemDetails = [];
        foreach ($details as $d) {
            $pid = (int)$d['project_id'];
            $phaseId = (int)$d['phase_id'];
            $itemId = (int)$d['phase_item_id'];
            $projectPhaseDetails[$pid][$phaseId][] = $d;
            $itemDetails[$itemId][] = $d;
        }

        // 4. Fetch target months for phases
        $targetMonthsRes = $this->db->query("
            SELECT DISTINCT phase_id, target_month
            FROM phase_monthly_targets
            WHERE project_id IN ($idsList)
        ");
        $phaseTargetMonths = [];
        if ($targetMonthsRes) {
            while ($row = $targetMonthsRes->fetch_assoc()) {
                $phaseTargetMonths[(int)$row['phase_id']] = $row['target_month'];
            }
        }

        // 5. Fetch planned quantities from monthly targets
        $planRes = $this->db->query("
            SELECT pmti.phase_item_id, pmti.work_type_id, pmti.target_mode, pmt.phase_id,
                   SUM(CASE WHEN pmti.target_mode = 'pct' THEN pmti.monthly_target_pct ELSE pmti.monthly_target_qty END) AS total_target_qty,
                   pmt.project_id
            FROM phase_monthly_target_items pmti
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            WHERE pmt.project_id IN ($idsList)
            GROUP BY pmt.project_id, pmt.phase_id, pmti.phase_item_id, pmti.work_type_id, pmti.target_mode
        ");
        $plannedQuantities = $planRes ? $planRes->fetch_all(MYSQLI_ASSOC) : [];

        // 6. Fetch actual completed quantities
        // 6a. Approved weekly progress items
        $weeklyActRes = $this->db->query("
            SELECT pmti.phase_item_id, pmti.work_type_id, pmt.project_id,
                   SUM(CASE WHEN pmti.target_mode = 'pct' THEN pwpi.achieved_pct ELSE pwpi.achieved_qty END) AS total_actual_qty
            FROM phase_weekly_progress_items pwpi
            INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
            INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            WHERE pmt.project_id IN ($idsList)
              AND pwp.qc_status = 'Approved'
              AND pwp.pm_status = 'Approved'
            GROUP BY pmt.project_id, pmti.phase_item_id, pmti.work_type_id
        ");
        $weeklyActuals = $weeklyActRes ? $weeklyActRes->fetch_all(MYSQLI_ASSOC) : [];

        // 6b. Approved daily logs for pending/draft weeks (to avoid double counting, but capture intermediate work)
        $dailyActRes = $this->db->query("
            SELECT pmti.phase_item_id, pmti.work_type_id, dpl.project_id,
                   SUM(CASE WHEN pmti.target_mode = 'pct' THEN dwil.pct_done_today ELSE dwil.qty_done_today END) AS total_actual_qty
            FROM daily_work_item_logs dwil
            JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
            INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
            LEFT JOIN phase_monthly_target_items pmti ON pmti.id = dwil.target_item_id
            WHERE dpl.project_id IN ($idsList)
              AND dpl.qc_status = 'Approved'
              AND dpl.pm_status = 'Approved'
              AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
            GROUP BY dpl.project_id, pmti.phase_item_id, pmti.work_type_id
        ");
        $dailyActuals = $dailyActRes ? $dailyActRes->fetch_all(MYSQLI_ASSOC) : [];

        // Map data in PHP
        $targetsMap = [];
        $phaseTargetsList = [];
        foreach ($plannedQuantities as $q) {
            $pid = (int)$q['project_id'];
            $phaseId = (int)$q['phase_id'];
            $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
            $targetsMap[$pid][$key] = (float)$q['total_target_qty'];
            $phaseTargetsList[$pid][$phaseId][] = $q;
        }

        $actualsMap = [];
        foreach ($weeklyActuals as $q) {
            $pid = (int)$q['project_id'];
            $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
            $actualsMap[$pid][$key] = ($actualsMap[$pid][$key] ?? 0.0) + (float)$q['total_actual_qty'];
        }
        foreach ($dailyActuals as $q) {
            $pid = (int)$q['project_id'];
            if ($q['phase_item_id'] !== null) {
                $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
                $actualsMap[$pid][$key] = ($actualsMap[$pid][$key] ?? 0.0) + (float)$q['total_actual_qty'];
            }
        }

        // Synthesize detail rows for any planned targets not in project_phase_item_details
        foreach ($projectIds as $pid) {
            $phasesList = $projectPhases[$pid] ?? [];
            foreach ($phasesList as $phase) {
                $phaseId = (int)$phase['id'];
                $phaseDetails = $projectPhaseDetails[$pid][$phaseId] ?? [];
                
                $existingDetailKeys = [];
                foreach ($phaseDetails as $d) {
                    $existingDetailKeys[$d['phase_item_id'] . '_' . $d['work_type_id']] = true;
                }
                
                if (!empty($phaseTargetsList[$pid][$phaseId])) {
                    foreach ($phaseTargetsList[$pid][$phaseId] as $target) {
                        $key = $target['phase_item_id'] . '_' . $target['work_type_id'];
                        if (!isset($existingDetailKeys[$key])) {
                            $subItemWeight = 0.0;
                            $itemId = (int)$target['phase_item_id'];
                            if (!empty($phaseItems[$phaseId])) {
                                foreach ($phaseItems[$phaseId] as $pi) {
                                    if ((int)$pi['item_id'] === $itemId) {
                                        $subItemWeight = (float)$pi['item_weight'];
                                        break;
                                    }
                                }
                            }
                            
                            $synthesizedDetail = [
                                'phase_id' => $phaseId,
                                'phase_item_id' => $itemId,
                                'detail_id' => null,
                                'work_type_id' => $target['work_type_id'],
                                'total_boq_qty' => ($target['target_mode'] === 'pct') ? 100.0 : 0.0,
                                'detail_weight' => $subItemWeight,
                                'project_id' => $pid,
                                'unit' => ($target['target_mode'] === 'pct') ? '%' : ''
                            ];
                            
                            $phaseDetails[] = $synthesizedDetail;
                            $projectPhaseDetails[$pid][$phaseId] = $phaseDetails;
                            $itemDetails[$itemId][] = $synthesizedDetail;
                        }
                    }
                }
            }
        }

        $projectProgress = [];
        foreach ($projectIds as $pid) {
            $phasesList = $projectPhases[$pid] ?? [];
            $overallActual = 0.0;
            $overallPlanned = 0.0;
            $phasesData = [];

            foreach ($phasesList as $phase) {
                $phaseId = (int)$phase['id'];
                $phaseWeight = (float)$phase['weight_percentage'];

                $phaseDetails = $projectPhaseDetails[$pid][$phaseId] ?? [];
                $phasePlannedProgressWeighted = 0.0;
                $phaseActualProgressWeighted = 0.0;

                $totalBOQInPhase = 0.0;
                $totalApprovedInPhase = 0.0;
                $totalTargetInPhase = 0.0;

                foreach ($phaseDetails as $d) {
                    $boqQty  = (float)$d['total_boq_qty'];
                    $weight  = (float)$d['detail_weight'];
                    $key = $d['phase_item_id'] . '_' . $d['work_type_id'];

                    $targetQty = $targetsMap[$pid][$key] ?? 0.0;
                    $actualQty = $actualsMap[$pid][$key] ?? 0.0;

                    if ($boqQty > 0) {
                        // Planned
                        $plannedRatio = min(1.0, $targetQty / $boqQty);
                        $phasePlannedProgressWeighted += $plannedRatio * $weight;

                        // Actual
                        $actualRatio = min(1.0, $actualQty / $boqQty);
                        $phaseActualProgressWeighted += $actualRatio * $weight;
                    }

                    $totalBOQInPhase += $boqQty;
                    $totalApprovedInPhase += $actualQty;
                    $totalTargetInPhase += $targetQty;
                }

                // Volume-based Phase internal progress (e.g. 0.4% complete)
                $phaseActualVolumePct = $totalBOQInPhase > 0 ? ($totalApprovedInPhase / $totalBOQInPhase) * 100 : 0.0;
                $phasePlannedVolumePct = $totalBOQInPhase > 0 ? ($totalTargetInPhase / $totalBOQInPhase) * 100 : 0.0;

                // Project contribution (volume-based)
                $phaseContribution = $phaseActualVolumePct * ($phaseWeight / 100);

                // Add phase items
                $itemsData = [];
                $itemsInPhase = $phaseItems[$phaseId] ?? [];
                foreach ($itemsInPhase as $item) {
                    $itemId = (int)$item['item_id'];
                    $itemWeight = (float)$item['item_weight'];

                    $detailsOfItem = $itemDetails[$itemId] ?? [];
                    $itemPlannedProgressWeighted = 0.0;
                    $itemActualProgressWeighted = 0.0;

                    $qtyTexts = [];
                    foreach ($detailsOfItem as $d) {
                        $boqQty  = (float)$d['total_boq_qty'];
                        $weight  = (float)$d['detail_weight'];
                        $key = $d['phase_item_id'] . '_' . $d['work_type_id'];

                        $targetQty = $targetsMap[$pid][$key] ?? 0.0;
                        $actualQty = $actualsMap[$pid][$key] ?? 0.0;

                        if ($boqQty > 0) {
                            $plannedRatio = min(1.0, $targetQty / $boqQty);
                            $itemPlannedProgressWeighted += $plannedRatio * $weight;

                            $actualRatio = min(1.0, $actualQty / $boqQty);
                            $itemActualProgressWeighted += $actualRatio * $weight;
                        }

                        $u = trim($d['unit'] ?? '');
                        if ($boqQty > 0 && $u !== '') {
                            $uLower = strtolower($u);
                            $recQty = $receivedQtyMap[$pid][$uLower] ?? 0.0;
                            $qtyPct = round(($recQty / $boqQty) * 100, 1);
                            $qtyTexts[] = "(" . number_format($recQty) . " {$u} / " . number_format($boqQty) . " {$u} used) - {$qtyPct}% Qty Used";
                        }
                    }
                    $qtyUsageText = implode(', ', $qtyTexts);

                    $itemPlannedPct = $itemWeight > 0 ? ($itemPlannedProgressWeighted / $itemWeight) * 100 : 0.0;
                    $itemActualPct = $itemWeight > 0 ? ($itemActualProgressWeighted / $itemWeight) * 100 : 0.0;

                    $itemsData[] = [
                        'item_id' => $itemId,
                        'item_name' => $item['item_name'],
                        'item_order' => $item['item_order'],
                        'item_weight' => $itemWeight,
                        'item_planned_pct' => $itemPlannedPct,
                        'item_actual_pct' => $itemActualPct,
                        'qty_usage_text' => $qtyUsageText,
                    ];
                }

                $overallActual += $phaseActualProgressWeighted * ($phaseWeight / 100);
                $overallPlanned += $phasePlannedProgressWeighted * ($phaseWeight / 100);

                $phasesData[] = [
                    'phase_id' => $phaseId,
                    'phase_name' => $phase['phase_name'],
                    'phase_order' => $phase['phase_order'],
                    'phase_weight' => $phaseWeight,
                    'phase_actual_pct' => $phaseActualVolumePct,
                    'phase_planned_pct' => $phasePlannedVolumePct,
                    'phase_actual_weighted_pct' => $phaseActualProgressWeighted,
                    'phase_planned_weighted_pct' => $phasePlannedProgressWeighted,
                    'phase_contribution' => $phaseContribution,
                    'total_approved_qty' => $totalApprovedInPhase,
                    'total_boq' => $totalBOQInPhase,
                    'target_month' => $phaseTargetMonths[$phaseId] ?? null,
                    'items' => $itemsData,
                ];
            }

            $projectProgress[$pid] = [
                'actual'  => round($overallActual, 4),
                'planned' => round($overallPlanned, 4),
                'phases'  => $phasesData,
            ];
        }

        return $projectProgress;
    }
}