<?php
namespace Phase2;

use Controller;
use Con_LaborRequisition;

class Con_LaborRequisitionController extends Controller
{
    protected $db;
    protected $model;
    protected $userId;
    protected $userRole;

    public function __construct($db)
    {
        $this->db    = $db;
        $this->model = new Con_LaborRequisition($db);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->userId   = (int)($_SESSION['user']['id']      ?? 0);
        $this->userRole = (int)($_SESSION['user']['role_id'] ?? 0);
    }

    // Role helpers
    // 12 = Site Engineer, 15 = Project Manager, 22 = Project Manager Head
    // 24 = QC & Internal Audit Head, 3 = Management, 6/30 = Accounts, 1 = Admin
    private function isSiteEngineer() { return in_array($this->userRole, [12, 1]); }
    private function isPM()           { return in_array($this->userRole, [15, 22, 1]); }
    private function isQC()           { return in_array($this->userRole, [24, 1]); }
    private function isManagement()   { return in_array($this->userRole, [3, 1]);  }
    private function isAccounts()     { return in_array($this->userRole, [6, 30, 1, 3]);  }

    // Checks whether current user can approve management step (Roles 1, 3, 20, 22 or Admin/Operation/Management/PM Head)
    private function canApproveManagement($amount = 0)
    {
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        if (in_array($this->userRole, [33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true)) {
            return false;
        }
        if (in_array($this->userRole, [1, 3, 20, 22])) {
            return true;
        }
        return (
            str_contains($roleName, 'admin')
            || str_contains($roleName, 'management')
            || str_contains($roleName, 'operation')
            || str_contains($roleName, 'pm head')
        );
    }

    // =========================================================================
    // INDEX
    // =========================================================================
    public function index()
    {
        // Site Engineer (role 12) only sees their own requisitions; PM / PM Head / QC / Mgmt / Accounts see all
        $requestedBy = null;
        if ($this->userRole === 12) {
            $requestedBy = $this->userId;
        }

        $status    = $_GET['status']     ?? null;
        $projectId = $_GET['project_id'] ?? null;

        $requisitions = $this->model->getAll($projectId ?: null, $status ?: null, $requestedBy);

        // SE sees only their assigned projects and common projects in filter dropdown; others see all
        if ($this->userRole === 12) {
            $rPrj = $this->db->query(
                "SELECT id, name FROM projects WHERE deleted_at IS NULL AND (under_construction = 1 OR is_common = 1) AND (site_engineer_id = {$this->userId} OR is_common = 1) ORDER BY name"
            );
        } else {
            $rPrj = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND (under_construction = 1 OR is_common = 1) ORDER BY name");
        }
        $projects = $rPrj ? $rPrj->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/labor_requisitions/index', [
            'current_route' => 'con-labor-requisitions',
            'page_title'    => 'Labor Requisitions',
            'requisitions'  => $requisitions,
            'projects'      => $projects,
            'userRole'      => $this->userRole,
            'userId'        => $this->userId,
        ]);
    }

    // =========================================================================
    // CREATE FORM
    // =========================================================================
    public function create()
    {
        if (!$this->isSiteEngineer() && !$this->isPM()) {
            $_SESSION['error'] = 'Access denied. You do not have permission to create a labor requisition.';
            header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisitions');
            exit;
        }

        // SE sees only their assigned projects and common projects; PM/PM Head/Mgmt/Admin see all
        if ($this->userRole === 12) {
            $rProjects = $this->db->query(
                "SELECT id, name FROM projects WHERE deleted_at IS NULL AND (under_construction = 1 OR is_common = 1) AND (site_engineer_id = {$this->userId} OR is_common = 1) ORDER BY name"
            );
        } else {
            $rProjects = $this->db->query(
                "SELECT id, name FROM projects WHERE deleted_at IS NULL AND (under_construction = 1 OR is_common = 1) ORDER BY name"
            );
        }
        $projects = $rProjects ? $rProjects->fetch_all(MYSQLI_ASSOC) : [];

        $rPhases = $this->db->query("SELECT id, phase_name FROM project_phases ORDER BY id");
        $phases  = $rPhases ? $rPhases->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/labor_requisitions/create', [
            'current_route' => 'con-labor-requisitions',
            'page_title'    => 'New Labor Requisition',
            'projects'      => $projects,
            'phases'        => $phases,
            'userRole'      => $this->userRole,
            'coaList'       => getCOAExpenseList($this->db),
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================
    public function store()
    {
        if (!$this->isSiteEngineer() && !$this->isPM()) {
            $_SESSION['error'] = 'Access denied. You do not have permission to submit a labor requisition.';
            header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisitions');
            exit;
        }

        $projectId  = (int)($_POST['project_id'] ?? 0);
        $purpose    = trim($_POST['purpose'] ?? '');
        $laborCount = (int)($_POST['labor_count'] ?? 0);
        $rate       = (float)($_POST['per_labor_rate'] ?? 0);
        $days       = (float)($_POST['working_days'] ?? 0);

        if ($projectId <= 0) {
            $_SESSION['error'] = 'Please select a valid project.';
            header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisition-create');
            exit;
        }

        if (empty($purpose)) {
            $_SESSION['error'] = 'Please provide a purpose or work description.';
            header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisition-create');
            exit;
        }

        if ($laborCount <= 0) {
            $_SESSION['error'] = 'Number of laborers must be at least 1.';
            header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisition-create');
            exit;
        }

        if ($rate < 0) {
            $_SESSION['error'] = 'Rate per labor cannot be negative.';
            header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisition-create');
            exit;
        }

        if ($days <= 0) {
            $_SESSION['error'] = 'Working days must be greater than 0.';
            header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisition-create');
            exit;
        }

        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            $uploadDirAbsolute = dirname(dirname(dirname(__DIR__))) . '/public/uploads/labor_requisitions/';
            if (!is_dir($uploadDirAbsolute)) {
                mkdir($uploadDirAbsolute, 0777, true);
            }
            $ext  = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
            if (in_array($ext, $allowedExts)) {
                $name = time() . '_' . $this->userId . '.' . $ext;
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDirAbsolute . $name)) {
                    $attachment = 'public/uploads/labor_requisitions/' . $name;
                }
            }
        }

        $account_id = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null;

        $data = [
            'project_id'       => $projectId,
            'phase_id'         => (int)($_POST['phase_id'] ?? 0) ?: null,
            'phase_target_id'  => (int)($_POST['phase_target_id'] ?? 0) ?: null,
            'requested_by'     => $this->userId,
            'requisition_date' => !empty($_POST['requisition_date']) ? $_POST['requisition_date'] : date('Y-m-d'),
            'work_date_from'   => !empty($_POST['work_date_from']) ? $_POST['work_date_from'] : null,
            'work_date_to'     => !empty($_POST['work_date_to'])   ? $_POST['work_date_to']   : null,
            'labor_type'       => $_POST['labor_type']     ?? 'General',
            'labor_count'      => $laborCount,
            'per_labor_rate'   => $rate,
            'working_days'     => $days,
            'purpose'          => $purpose,
            'remarks'          => trim($_POST['remarks'] ?? ''),
            'attachment'       => $attachment,
            'created_by'       => $this->userId,
            'account_id'       => $account_id,
        ];

        $id = $this->model->create($data);

        if (!$id || (int)$id <= 0) {
            $errMsg = $this->model->getLastError() ?: 'Failed to create labor requisition. Please try again.';
            $_SESSION['error'] = '❌ ' . $errMsg;
            header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisition-create');
            exit;
        }

        $_SESSION['success'] = '✅ Labor Requisition submitted successfully.';
        header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisition/' . $id);
        exit;
    }

    // =========================================================================
    // SHOW
    // =========================================================================
    public function show($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'Invalid requisition ID.';
            header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisitions');
            exit;
        }

        $req = $this->model->find($id);
        if (!$req) {
            $_SESSION['error'] = 'Requisition not found.';
            header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisitions');
            exit;
        }


        $paymentLogs = $this->model->getPaymentLogs((int)$id);

        $this->view('phase2/labor_requisitions/show', [
            'current_route' => 'con-labor-requisitions',
            'page_title'    => $req['requisition_no'] . ' — Labor Requisition',
            'req'           => $req,
            'paymentLogs'   => $paymentLogs,
            'userRole'      => $this->userRole,
            'userId'        => $this->userId,
            'isPM'          => $this->isPM(),
            'isQC'          => $this->isQC(),
            'isManagement'         => ($this->isManagement() || $this->userRole === 20),
            'canApproveManagement' => $this->canApproveManagement((float)($req['qc_approved_amount'] ?? $req['pm_verified_amount'] ?? $req['total_amount'])),
            'isAccounts'           => $this->isAccounts(),
            'isSE'                 => $this->isSiteEngineer(),
        ]);
    }

    // =========================================================================
    // PM APPROVE / REJECT
    // =========================================================================
    public function pmApprove($id)
    {
        if (!$this->isPM()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }
        $note = trim($_POST['pm_note'] ?? '');
        $this->model->pmApprove((int)$id, $this->userId, $note);
        $_SESSION['success'] = 'Labor Requisition approved by Project Manager.';
        $this->back($id);
    }

    public function pmReject($id)
    {
        if (!$this->isPM()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }
        $reason = trim($_POST['rejection_reason'] ?? 'Rejected by PM');
        $this->model->pmReject((int)$id, $this->userId, $reason);
        $_SESSION['error'] = 'Labor Requisition rejected.';
        $this->back($id);
    }

    // =========================================================================
    // MARK WORK COMPLETE + REQUEST PAYMENT
    // =========================================================================
    public function markWorkComplete($id)
    {
        $this->model->markWorkComplete((int)$id, $this->userId);
        $this->syncToApprovalRequests((int)$id);
        $_SESSION['success'] = 'Work marked as completed. Payment approval request submitted to Management.';
        $this->back($id);
    }

    public function requestPayment($id)
    {
        $this->model->requestPayment((int)$id, $this->userId);
        $this->syncToApprovalRequests((int)$id);
        $_SESSION['success'] = 'Payment request submitted successfully.';
        $this->back($id);
    }

    // =========================================================================
    // QC APPROVE / REJECT (Strictly Role 24 & Super Admin)
    // =========================================================================
    public function qcApprove($id)
    {
        if (!$this->isQC()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }
        $req = $this->model->find((int)$id);
        if (!$req) { $_SESSION['error'] = 'Requisition not found.'; $this->back($id); }

        $qcAmount = isset($_POST['qc_approved_amount']) && $_POST['qc_approved_amount'] !== '' 
            ? (float)$_POST['qc_approved_amount'] 
            : (float)$req['total_amount'];

        $note = trim($_POST['qc_note'] ?? '');
        $this->model->qcApprove((int)$id, $this->userId, $qcAmount, $note);
        $this->syncToApprovalRequests((int)$id);
        $_SESSION['success'] = 'QC & Internal Audit Head approved amount ৳' . number_format($qcAmount, 2) . ' and forwarded to Management for payment authorization.';
        $this->back($id);
    }

    public function qcReject($id)
    {
        if (!$this->isQC()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }
        $reason = trim($_POST['rejection_reason'] ?? 'Rejected by QC & Internal Audit');
        $this->model->qcReject((int)$id, $this->userId, $reason);
        $_SESSION['error'] = 'Payment request rejected by QC & Internal Audit.';
        $this->back($id);
    }

    // =========================================================================
    // PM PAYMENT APPROVE / REJECT (Project Manager / PM Head: Role 15, 22, 1)
    // =========================================================================
    public function pmPaymentApprove($id)
    {
        if (!$this->isPM()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }
        $req = $this->model->find((int)$id);
        if (!$req) { $_SESSION['error'] = 'Requisition not found.'; $this->back($id); }

        $defaultAmount = (float)($req['qc_approved_amount'] ?? $req['total_amount']);
        $pmAmount = isset($_POST['pm_verified_amount']) && $_POST['pm_verified_amount'] !== '' 
            ? (float)$_POST['pm_verified_amount'] 
            : $defaultAmount;

        $note = trim($_POST['pm_payment_note'] ?? '');
        $this->model->pmPaymentApprove((int)$id, $this->userId, $pmAmount, $note);
        $this->syncToApprovalRequests((int)$id);
        $_SESSION['success'] = 'PM verified amount ৳' . number_format($pmAmount, 2) . ' and forwarded to Management for payment approval.';
        $this->back($id);
    }

    public function pmPaymentReject($id)
    {
        if (!$this->isPM()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }
        $reason = trim($_POST['rejection_reason'] ?? 'Rejected by PM');
        $this->model->pmPaymentReject((int)$id, $this->userId, $reason);
        $_SESSION['error'] = 'Rejected by PM.';
        $this->back($id);
    }

    // =========================================================================
    // MANAGEMENT APPROVE / REJECT (Supports Management + Role 20)
    // =========================================================================
    public function managementApprove($id)
    {
        $req = $this->model->find((int)$id);
        if (!$req) { $_SESSION['error'] = 'Requisition not found.'; $this->back($id); }

        if (!in_array($req['status'], ['qc_approved', 'pm_payment_approved'])) {
            $_SESSION['error'] = 'Requisition must be approved by QC & Internal Audit before Management approval.';
            $this->back($id);
        }

        $baseAmount = (float)($req['qc_approved_amount'] ?? $req['pm_verified_amount'] ?? $req['total_amount']);
        if (!$this->canApproveManagement($baseAmount)) {
            $_SESSION['error'] = 'Access denied. You are not authorized to approve this requisition.';
            $this->back($id);
        }

        $approvedAmount = (float)($_POST['approved_amount'] ?? 0);
        $note = trim($_POST['management_note'] ?? '');
        $finalApprovedAmount = $approvedAmount > 0 ? $approvedAmount : $baseAmount;
        $isOver1Lac = ($finalApprovedAmount > 10000);
        $isOpHeadUser = (in_array((int)$this->userRole, [20, 32], true) || str_contains(strtolower($_SESSION['user']['role_name'] ?? ''), 'operation head'));
        $isMgmtUser = in_array((int)$this->userRole, [1, 3], true);

        // Fetch approval request record
        $arCheck = $this->db->query("SELECT id, op_head_status, accounts_head_status FROM approval_requests WHERE source_table = 'con_labor_requisitions' AND source_id = " . (int)$id . " ORDER BY id DESC LIMIT 1");
        $arRow = $arCheck ? $arCheck->fetch_assoc() : null;
        $opHeadStatus = strtolower($arRow['op_head_status'] ?? 'pending');
        $ahStatus = strtolower($arRow['accounts_head_status'] ?? 'pending');

        if ($isOver1Lac) {
            if ($isOpHeadUser && !$isMgmtUser) {
                $arId = (int)($arRow['id'] ?? 0);
                $escNote = $this->db->real_escape_string($note ?: 'Verified and approved by Admin & Operation Head');
                if (!class_exists('ApprovalRequest')) {
                    require_once __DIR__ . '/../../models/ApprovalRequest.php';
                }
                $approvalModel = new \ApprovalRequest($this->db);
                if ($arId > 0) {
                    $approvalModel->approveOperationHead($arId, $this->userId, $escNote);
                } else {
                    $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$this->userId}, op_head_at = NOW(), op_head_note = '{$escNote}', approve_role = 'accounts_head' WHERE source_table = 'con_labor_requisitions' AND source_id = " . (int)$id);
                }
                $_SESSION['success'] = "Labor requisition verified & approved by Admin & Operation Head (Role 20). Forwarded to Accounts Head (Role 30) for payment verification.";
                $this->back($id);
            }

            if ($isMgmtUser) {
                if ($opHeadStatus !== 'approved') {
                    $_SESSION['error'] = "Access Denied: Labor requisitions exceeding ৳10,000 must be approved by Admin & Operation Head (Role 20) first.";
                    $this->back($id);
                }
                if ($ahStatus !== 'approved') {
                    $_SESSION['error'] = "Access Denied: Labor requisitions exceeding ৳10,000 must be verified & approved by Accounts Head (Role 30) before Management can approve.";
                    $this->back($id);
                }
            }
        }

        if ($this->model->managementApprove((int)$id, $this->userId, $finalApprovedAmount, $note)) {
            $this->syncToApprovalRequests((int)$id, 'approved', $finalApprovedAmount, $note);
            $this->db->query("UPDATE approval_requests SET op_head_status = 'approved' WHERE source_table = 'con_labor_requisitions' AND source_id = " . (int)$id);
            $_SESSION['success'] = 'Labor requisition approved for ৳' . number_format($finalApprovedAmount, 2) . '. Ready for payment by Accounts.';
        } else {
            $_SESSION['error'] = 'Failed to approve labor requisition.';
        }
        $this->back($id);
    }

    public function managementReject($id)
    {
        $req = $this->model->find((int)$id);
        if (!$req) { $_SESSION['error'] = 'Requisition not found.'; $this->back($id); }

        if (!in_array($req['status'], ['qc_approved', 'pm_payment_approved'])) {
            $_SESSION['error'] = 'Requisition must be approved by QC & Internal Audit before Management rejection.';
            $this->back($id);
        }

        $baseAmount = (float)($req['qc_approved_amount'] ?? $req['pm_verified_amount'] ?? $req['total_amount']);
        if (!$this->canApproveManagement($baseAmount)) {
            $_SESSION['error'] = 'Access denied. You are not authorized to reject this requisition.';
            $this->back($id);
        }

        $reason = trim($_POST['rejection_reason'] ?? $_POST['remarks'] ?? '');
        if (empty($reason)) {
            $_SESSION['error'] = 'Rejection reason is required.';
            $this->back($id);
        }

        if ($this->model->managementReject((int)$id, $this->userId, $reason)) {
            $this->syncToApprovalRequests((int)$id, 'rejected', null, $reason);
            $_SESSION['error'] = 'Labor requisition has been rejected.';
        } else {
            $_SESSION['error'] = 'Failed to reject labor requisition.';
        }
        $this->back($id);
    }

    /**
     * Sync labor requisition to central approval_requests table
     */
    private function syncToApprovalRequests($id, $action = 'pending', $approvedAmount = null, $note = null)
    {
        try {
            $req = $this->model->find((int)$id);
            if (!$req) return;

            $existing = $this->db->query("SELECT id, status FROM approval_requests WHERE source_table = 'con_labor_requisitions' AND source_id = " . (int)$id . " LIMIT 1")->fetch_assoc();

            $projectName = !empty($req['project_name']) ? $req['project_name'] : 'Project #' . $req['project_id'];
            $title = "Labor Bill — " . $req['requisition_no'] . " | " . $projectName . " (" . $req['labor_count'] . " " . $req['labor_type'] . " Labors)";
            $requestedAmount = (float)$req['total_amount'];
            $lrStatus = $req['status'] ?? 'pending';
            $qcAmount = (!empty($req['qc_approved_amount']) && (float)$req['qc_approved_amount'] > 0) ? (float)$req['qc_approved_amount'] : null;
            $appAmount = (!empty($req['approved_amount']) && (float)$req['approved_amount'] > 0) ? (float)$req['approved_amount'] : $qcAmount;
            $metaData = json_encode([
                'requisition_no' => $req['requisition_no'],
                'labor_count'    => $req['labor_count'],
                'labor_type'     => $req['labor_type'],
                'working_days'   => $req['working_days'],
                'per_labor_rate' => $req['per_labor_rate'],
                'purpose'        => $req['purpose'],
            ]);

            if ($existing) {
                if ($action === 'paid') {
                    $stmt = $this->db->prepare("UPDATE approval_requests SET status = 'approved', approve_role = 'completed', approved_amount = COALESCE(?, approved_amount), updated_at = NOW() WHERE id = ?");
                    $appAmt = ($approvedAmount !== null && $approvedAmount > 0) ? (float)$approvedAmount : ($appAmount ?: $requestedAmount);
                    $stmt->bind_param('di', $appAmt, $existing['id']);
                    $stmt->execute();
                } elseif ($action === 'partially_paid') {
                    $stmt = $this->db->prepare("UPDATE approval_requests SET status = 'partially_paid', approve_role = 'accounts', approved_amount = COALESCE(?, approved_amount), updated_at = NOW() WHERE id = ?");
                    $appAmt = ($approvedAmount !== null && $approvedAmount > 0) ? (float)$approvedAmount : ($appAmount ?: $requestedAmount);
                    $stmt->bind_param('di', $appAmt, $existing['id']);
                    $stmt->execute();
                } elseif ($action === 'approved') {
                    $isAhAppr = (!empty($existing['accounts_head_status']) && strtolower($existing['accounts_head_status']) === 'approved');
                    $nextRoleLr = $isAhAppr ? 'accounts' : 'accounts_head';
                    $stmt = $this->db->prepare("UPDATE approval_requests SET status = 'approved', approve_role = ?, approved_amount = ?, approved_by = ?, approved_at = NOW(), approval_note = ?, updated_at = NOW() WHERE id = ?");
                    $appAmt = ($approvedAmount !== null && $approvedAmount > 0) ? (float)$approvedAmount : ($appAmount ?: $requestedAmount);
                    $stmt->bind_param('sdisi', $nextRoleLr, $appAmt, $this->userId, $note, $existing['id']);
                    $stmt->execute();
                } elseif ($action === 'rejected') {
                    $stmt = $this->db->prepare("UPDATE approval_requests SET status = 'rejected', approve_role = 'management', approved_by = ?, approved_at = NOW(), approval_note = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param('isi', $this->userId, $note, $existing['id']);
                    $stmt->execute();
                } else {
                    $targetRole = 'management';
                    if ($lrStatus === 'pending') {
                        $targetRole = 'pm';
                    } elseif ($lrStatus === 'payment_requested') {
                        $targetRole = 'qc';
                    } elseif (in_array($lrStatus, ['qc_approved', 'pm_payment_approved'])) {
                        $targetRole = 'management';
                    }
                    $stmt = $this->db->prepare("UPDATE approval_requests SET status = 'pending', request_amount = ?, approved_amount = ?, approve_role = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param('ddsi', $requestedAmount, $appAmount, $targetRole, $existing['id']);
                    $stmt->execute();
                }
            } else {
                $status = ($action === 'approved' || $action === 'paid') ? 'approved' : (($action === 'partially_paid') ? 'partially_paid' : (($action === 'rejected') ? 'rejected' : 'pending'));
                $appRole = ($action === 'paid') ? 'completed' : (($action === 'approved' || $action === 'partially_paid') ? 'accounts' : ($lrStatus === 'payment_requested' ? 'qc' : ($lrStatus === 'pending' ? 'pm' : 'management')));
                $appAmt = ($status === 'approved' || $status === 'partially_paid') ? ($approvedAmount ?: $appAmount) : $appAmount;
                $appBy  = ($status !== 'pending') ? $this->userId : null;
                $appAt  = ($status !== 'pending') ? date('Y-m-d H:i:s') : null;

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES ('Construction', 'Labor Bill by SE', ?, 'con_labor_requisitions', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                if ($stmt) {
                    $reqBy = (int)$req['requested_by'];
                    $stmt->bind_param("siddississs", $title, $id, $requestedAmount, $appAmt, $reqBy, $appRole, $status, $appBy, $appAt, $note, $metaData);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        } catch (\Throwable $e) {
            error_log("Error syncing labor requisition #{$id} to approval_requests: " . $e->getMessage());
        }
    }

    // =========================================================================
    // ACCOUNTS MARK PAID (supports partial payment + proof image)
    // =========================================================================
    public function markPaid($id)
    {
        if (!$this->isAccounts()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }

        $req = $this->model->find((int)$id);
        if (!$req) { $_SESSION['error'] = 'Requisition not found.'; $this->back($id); }

        // STRICT GUARD: Must be approved by Accounts Head (Role 30 - Md. Mamunoor Rashid) before payment disbursement
        require_once __DIR__ . '/../../models/ApprovalRequest.php';
        $ahCheck = \ApprovalRequest::getAccountsHeadApprovalInfo($this->db, 'con_labor_requisitions', (int)$id);
        if ($ahCheck['has_request'] && !$ahCheck['is_accounts_head_approved']) {
            $_SESSION['error'] = 'Payment cannot be processed! Accounts Head approval (Md. Mamunoor Rashid [1309]) is required before payment disbursement.';
            $this->back($id);
        }

        $approvedAmount = (float)($req['approved_amount'] ?? $req['pm_verified_amount'] ?? $req['qc_approved_amount'] ?? $req['total_amount']);
        $totalPaid = (float)($req['total_paid'] ?? 0);
        $dueAmount = max(0, $approvedAmount - $totalPaid);
        $paidAmount = (float)($_POST['paid_amount'] ?? 0);

        if ($paidAmount <= 0) {
            $_SESSION['error'] = 'Please enter a valid payment amount greater than 0.';
            $this->back($id);
        }

        if ($paidAmount > $dueAmount + 0.01) {
            $_SESSION['error'] = 'Payment amount (৳' . number_format($paidAmount, 2) . ') cannot exceed the remaining due amount of ৳' . number_format($dueAmount, 2) . '.';
            $this->back($id);
        }

        // Handle proof image upload
        $proofImage = null;
        if (!empty($_FILES['proof_image']['name'])) {
            $uploadDirRelative = 'public/uploads/labor_payment_proofs/';
            $uploadDirAbsolute = dirname(dirname(dirname(__DIR__))) . '/' . $uploadDirRelative;
            if (!is_dir($uploadDirAbsolute)) mkdir($uploadDirAbsolute, 0755, true);
            $ext  = strtolower(pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','pdf'];
            if (in_array($ext, $allowed)) {
                $name = 'PAY-' . (int)$id . '-' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $uploadDirAbsolute . $name)) {
                    $proofImage = $uploadDirRelative . $name;
                }
            }
        }

        $data = [
            'paid_amount'       => $paidAmount,
            'paid_by'           => $this->userId,
            'payment_date'      => $_POST['payment_date'],
            'payment_method'    => $_POST['payment_method'] ?? 'Cash',
            'payment_reference' => trim($_POST['payment_reference'] ?? ''),
            'payment_note'      => trim($_POST['payment_note'] ?? ''),
            'proof_image'       => $proofImage,
        ];

        $result = $this->model->markPaid((int)$id, $data);

        $newDue = max(0, $dueAmount - $paidAmount);
        if ($result === 'paid') {
            $this->syncToApprovalRequests((int)$id, 'paid', $approvedAmount, $data['payment_note'] ?? '');
            $_SESSION['success'] = '✅ Full payment of ৳' . number_format($paidAmount, 2) . ' disbursed! Due amount: ৳ 0.00. Requisition is now marked as Paid.';
        } elseif ($result === 'partially_paid') {
            $this->syncToApprovalRequests((int)$id, 'partially_paid', $approvedAmount, $data['payment_note'] ?? '');
            $_SESSION['success'] = '⚠️ Partial payment of ৳' . number_format($paidAmount, 2) . ' disbursed. Remaining due amount: ৳ ' . number_format($newDue, 2) . '.';
        } else {
            $_SESSION['error'] = 'Payment processing failed. Please try again.';
        }
        $this->back($id);
    }

    // =========================================================================
    // MANAGEMENT: APPROVE REMAINING (for partially_paid)
    // =========================================================================
    public function mgmtApproveRemaining($id)
    {
        if (!$this->isManagement()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }
        $note = trim($_POST['management_note'] ?? '');
        $this->model->managementApproveRemaining((int)$id, $this->userId, $note);
        $_SESSION['success'] = 'Management approved remaining balance. Accounts can now process payment.';
        $this->back($id);
    }

    // =========================================================================
    // HELPER
    // =========================================================================
    private function back($id)
    {
        header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisition/' . $id);
        exit;
    }

    // =========================================================================
    // SE: CONFIRM LABOUR DISBURSEMENT
    // =========================================================================
    public function markLaborDisbursed($id)
    {
        if (!$this->isSiteEngineer()) {
            $_SESSION['error'] = 'Access denied.';
            $this->back($id);
        }

        // Handle proof image upload
        $proof = null;
        if (!empty($_FILES['disbursement_proof']['name'])) {
            $uploadDirRelative = 'public/uploads/labor_disbursement_proofs/';
            $uploadDirAbsolute = dirname(dirname(dirname(__DIR__))) . '/' . $uploadDirRelative;
            if (!is_dir($uploadDirAbsolute)) mkdir($uploadDirAbsolute, 0755, true);
            $ext     = strtolower(pathinfo($_FILES['disbursement_proof']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            if (in_array($ext, $allowed)) {
                $name = 'DISB-' . (int)$id . '-' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['disbursement_proof']['tmp_name'], $uploadDirAbsolute . $name)) {
                    $proof = $uploadDirRelative . $name;
                }
            }
        }

        $note   = trim($_POST['disbursement_note'] ?? '');
        $result = $this->model->markLaborDisbursed((int)$id, $this->userId, $proof, $note);

        if ($result) {
            $_SESSION['success'] = '✅ Labour payment disbursement confirmed successfully!';
        } else {
            $_SESSION['error'] = 'Could not confirm disbursement. Requisition may not be in paid status.';
        }
        $this->back($id);
    }

    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = (int)$id;

        $requisition = $this->model->find($id);
        if (!$requisition) {
            $_SESSION['error'] = 'Requisition not found.';
            header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisitions');
            exit;
        }

        // Only allow deleting pending requisitions
        if ($requisition['status'] !== 'pending') {
            $_SESSION['error'] = 'Processed or approved requisitions cannot be deleted.';
            $this->back($id);
        }

        // Check permissions: only creator or Admin (role 1)
        if ($requisition['requested_by'] !== $this->userId && ($_SESSION['user']['role_id'] ?? 0) !== 1) {
            $_SESSION['error'] = 'You do not have permission to delete this requisition.';
            $this->back($id);
        }

        // Delete from database
        $stmt = $this->db->prepare("DELETE FROM con_labor_requisitions WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $this->db->query("DELETE FROM approval_requests WHERE source_table = 'con_labor_requisitions' AND source_id = " . (int)$id);
            $_SESSION['success'] = '✅ Labor requisition deleted successfully!';
            header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisitions');
            exit;
        } else {
            $_SESSION['error'] = 'Failed to delete requisition.';
            $this->back($id);
        }
    }
}
