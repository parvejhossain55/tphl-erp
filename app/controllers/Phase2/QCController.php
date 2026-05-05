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
        if (in_array($roleId, [1, 3, 22, 23])) return '1=1';
        return "({$alias}.pm_id = {$userId} OR {$alias}.site_engineer_id = {$userId} OR {$alias}.landman_id = {$userId} OR {$alias}.struct_engineer_id = {$userId} OR {$alias}.operation_head_id = {$userId} OR {$alias}.draftsman_id = {$userId} OR {$alias}.architect_id = {$userId} OR {$alias}.estimator_id = {$userId} OR {$alias}.qc_id = {$userId} OR {$alias}.operator_id = {$userId} OR {$alias}.created_by = {$userId})";
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // ── Inspections & Material Tests ──────────────────────────────────
        $inspections   = $this->qcInspectionModel->getAll();
        $materialTests = $this->qcMaterialTestModel->getAll();

        // ── KPI 1: Assigned Projects (only QC-assigned) ──────────────────
        if (in_array($roleId, [1, 3, 22, 23])) {
            $rAssigned = $this->db->query("SELECT COUNT(*) AS c FROM projects WHERE deleted_at IS NULL");
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM projects WHERE qc_id = ? AND deleted_at IS NULL");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $rAssigned = $stmt->get_result();
        }
        $assignedProjects = $rAssigned ? (int)$rAssigned->fetch_assoc()['c'] : 0;

        // ── KPI 2: Pending Targets (awaiting QC approval) ─────────────────
        $rTargetCount = $this->db->query("SELECT COUNT(*) AS c FROM target_fixed WHERE approved_by_qc = 0");
        $pendingTargetsCount = $rTargetCount ? (int)$rTargetCount->fetch_assoc()['c'] : 0;

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
        $rWkCount = $this->db->query("SELECT COUNT(*) AS c FROM weekly_progress_report WHERE approved_by_qc = 0");
        $pendingWeeklyCount = $rWkCount ? (int)$rWkCount->fetch_assoc()['c'] : 0;

        // ── Project Progress (only assigned projects) ─────────────────────
        $rProj = $this->db->query("
            SELECT p.id, p.name, p.project_status,
                   COALESCE(l.name,'') AS location_name,
                   COALESCE(prog.progress_pct,0) AS progress_pct
            FROM projects p
            LEFT JOIN locations l ON l.id = p.location_id
            LEFT JOIN (
                SELECT project_id, AVG(gain_percentage) AS progress_pct
                FROM target_fixed GROUP BY project_id
            ) prog ON prog.project_id = p.id
            WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . "
            ORDER BY p.id DESC LIMIT 6");
        $projects = $rProj ? $rProj->fetch_all(MYSQLI_ASSOC) : [];

        // ── Pending Targets (full rows for table) ─────────────────────────
        $rPendingTargets = $this->db->query("
            SELECT tf.id, tf.target_title, tf.target_percentage, tf.gain_percentage,
                   tf.year, tf.month, tf.week_number, tf.created_at,
                   p.name        AS project_name,
                   u.full_name   AS creator_name,
                   COALESCE(des.name,'') AS creator_designation
            FROM target_fixed tf
            LEFT JOIN projects     p   ON p.id  = tf.project_id
            LEFT JOIN users        u   ON u.id  = tf.user_id
            LEFT JOIN employees    emp ON emp.user_id = u.id
            LEFT JOIN designations des ON des.id = emp.designation_id
            WHERE tf.approved_by_qc = 0
            ORDER BY tf.created_at DESC LIMIT 10");
        $pendingTargets = $rPendingTargets ? $rPendingTargets->fetch_all(MYSQLI_ASSOC) : [];

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
        $rPendingWeeklyQC = $this->db->query("
            SELECT r.id, r.report_date, r.week_no,
                   p.name AS project_name,
                   u.full_name AS created_by_name
            FROM weekly_progress_report r
            LEFT JOIN projects p ON p.id = r.project_id
            LEFT JOIN users u ON u.id = r.created_by
            WHERE r.approved_by_qc = 0
            ORDER BY r.id DESC LIMIT 10");
        $pendingWeeklyReportsQC = $rPendingWeeklyQC ? $rPendingWeeklyQC->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/qc/dashboard', [
            'current_route'          => 'qc-dashboard',
            'page_title'             => 'QC Dashboard',
            'inspections'            => $inspections,
            'materialTests'          => $materialTests,
            'projects'               => $projects,
            'pendingTargets'         => $pendingTargets,
            'pendingGRNsQC'          => $pendingGRNsQC,
            'pendingWeeklyReportsQC' => $pendingWeeklyReportsQC,
            // KPI counts
            'assignedProjects'       => $assignedProjects,
            'pendingTargetsCount'    => $pendingTargetsCount,
            'pendingGRNsCount'       => $pendingGRNsCount,
            'totalInspections'       => $totalInspections,
            'totalMaterialTests'     => $totalMaterialTests,
            'pendingWeeklyCount'     => $pendingWeeklyCount,
        ]);
    }

    private function uploadFile($fileData, $uploadPath = 'uploads/qc/')
    {
        if (empty($fileData['name'])) return null;
        
        $baseDir = __DIR__ . '/../../../../public/' . $uploadPath;
        if (!is_dir($baseDir)) mkdir($baseDir, 0777, true);

        $ext = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $newName = uniqid('qc_') . '_' . time() . '.' . $ext;
        
        if (move_uploaded_file($fileData['tmp_name'], $baseDir . $newName)) {
            return $uploadPath . $newName;
        }
        return null;
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
        // For simplicity, fetch all material receivings
        $receivings = $this->materialReceivingModel->getAll();

        $this->view('phase2/qc/material-test-form', [
            'current_route' => 'qc-material-test-create',
            'page_title' => 'Create Material Test',
            'receivings' => $receivings
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

        $data = [
            'material_receiving_id' => (int)($_POST['material_receiving_id'] ?? 0),
            'test_name' => $_POST['test_name'] ?? '',
            'result_value' => $_POST['result_value'] ?? '',
            'test_status' => $_POST['test_status'] ?? 'fail',
            'lab_report_link' => $_POST['lab_report_link'] ?? '',
            'created_by' => $userId,
            'attachment' => isset($_FILES['test_attachment']) ? $this->uploadFile($_FILES['test_attachment']) : null
        ];

        if (!$data['material_receiving_id']) {
            setError('Material Receiving is required.');
            header("Location: {$GLOBALS['base_url']}/qc-material-test-create");
            exit;
        }

        if ($this->qcMaterialTestModel->create($data)) {
            // Wait for QC Review to generate Return Requests

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
}
