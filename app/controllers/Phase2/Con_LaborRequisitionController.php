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
    // 24 = QC & Internal Audit Head, 3 = Management, 6/30 = Accounts, 1 = Admin, 33 = Chief Officer
    private function isSiteEngineer() { return in_array($this->userRole, [12, 1]); }
    private function isPM()           { return in_array($this->userRole, [15, 22, 1]); }
    private function isQC()           { return in_array($this->userRole, [24, 1]); }
    private function isManagement()   { return in_array($this->userRole, [3, 1, 33]);  }
    private function isAccounts()     { return in_array($this->userRole, [6, 30, 1, 3, 33]);  }

    // Checks whether current user can approve management step (Roles 1, 3, 33 or Role 20 for amount <= 100,000)
    private function canApproveManagement($amount = 0)
    {
        if (in_array($this->userRole, [3, 1, 33])) {
            return true;
        }
        if ($this->userRole === 20 && (float)$amount <= 100000) {
            return true;
        }
        return false;
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
            $uploadDir = 'public/uploads/labor_requisitions/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext  = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
            if (in_array($ext, $allowedExts)) {
                $name = time() . '_' . $this->userId . '.' . $ext;
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $name)) {
                    $attachment = $uploadDir . $name;
                }
            }
        }

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
    // MANAGEMENT APPROVE / REJECT (Supports Management + Role 20 for amount <= 100,000)
    // =========================================================================
    public function managementApprove($id)
    {
        $req = $this->model->find((int)$id);
        if (!$req) { $_SESSION['error'] = 'Requisition not found.'; $this->back($id); }

        $baseAmount = (float)($req['qc_approved_amount'] ?? $req['pm_verified_amount'] ?? $req['total_amount']);
        if (!$this->canApproveManagement($baseAmount)) {
            $_SESSION['error'] = ($this->userRole === 20)
                ? 'Access denied. Role 20 cannot approve amounts above ৳100,000.'
                : 'Access denied.';
            $this->back($id);
        }

        $approvedAmount = (float)($_POST['approved_amount'] ?? 0);
        if ($this->userRole === 20 && $approvedAmount > 100000) {
            $_SESSION['error'] = 'Access denied. Role 20 cannot approve amounts above ৳100,000.';
            $this->back($id);
        }

        $note = trim($_POST['management_note'] ?? '');
        $finalApprovedAmount = $approvedAmount > 0 ? $approvedAmount : $baseAmount;
        $this->model->managementApprove((int)$id, $this->userId, $finalApprovedAmount, $note);
        $this->syncToApprovalRequests((int)$id, 'approved', $finalApprovedAmount, $note);
        $_SESSION['success'] = 'Management approved ৳' . number_format($finalApprovedAmount, 2) . '. Ready for payment by Accounts.';
        $this->back($id);
    }

    public function managementReject($id)
    {
        $req = $this->model->find((int)$id);
        if (!$req) { $_SESSION['error'] = 'Requisition not found.'; $this->back($id); }

        $baseAmount = (float)($req['qc_approved_amount'] ?? $req['pm_verified_amount'] ?? $req['total_amount']);
        if (!$this->canApproveManagement($baseAmount)) {
            $_SESSION['error'] = ($this->userRole === 20)
                ? 'Access denied. Role 20 cannot reject amounts above ৳100,000.'
                : 'Access denied.';
            $this->back($id);
        }

        $reason = trim($_POST['rejection_reason'] ?? 'Rejected by Management');
        $this->model->managementReject((int)$id, $this->userId, $reason);
        $this->syncToApprovalRequests((int)$id, 'rejected', null, $reason);
        $_SESSION['error'] = 'Rejected by Management.';
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
            $amount = (float)($req['pm_verified_amount'] ?? $req['qc_approved_amount'] ?? $req['total_amount']);
            $metaData = json_encode([
                'requisition_no' => $req['requisition_no'],
                'labor_count'    => $req['labor_count'],
                'labor_type'     => $req['labor_type'],
                'working_days'   => $req['working_days'],
                'per_labor_rate' => $req['per_labor_rate'],
                'purpose'        => $req['purpose'],
            ]);

            if ($existing) {
                if ($action === 'approved') {
                    $stmt = $this->db->prepare("UPDATE approval_requests SET status = 'approved', approved_amount = ?, approved_by = ?, approved_at = NOW(), approval_note = ?, updated_at = NOW() WHERE id = ?");
                    $appAmt = ($approvedAmount !== null && $approvedAmount > 0) ? (float)$approvedAmount : $amount;
                    $stmt->bind_param('disi', $appAmt, $this->userId, $note, $existing['id']);
                    $stmt->execute();
                } elseif ($action === 'rejected') {
                    $stmt = $this->db->prepare("UPDATE approval_requests SET status = 'rejected', approved_by = ?, approved_at = NOW(), approval_note = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param('isi', $this->userId, $note, $existing['id']);
                    $stmt->execute();
                } else {
                    $stmt = $this->db->prepare("UPDATE approval_requests SET status = 'pending', request_amount = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param('di', $amount, $existing['id']);
                    $stmt->execute();
                }
            } else {
                $status = ($action === 'approved') ? 'approved' : (($action === 'rejected') ? 'rejected' : 'pending');
                $appAmt = ($status === 'approved') ? ($approvedAmount ?: $amount) : null;
                $appBy  = ($status !== 'pending') ? $this->userId : null;
                $appAt  = ($status !== 'pending') ? date('Y-m-d H:i:s') : null;

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES ('Construction', 'Labor Bill by SE', ?, 'con_labor_requisitions', ?, ?, ?, ?, 'management', ?, ?, ?, ?, ?, NOW())
                ");
                if ($stmt) {
                    $reqBy = (int)$req['requested_by'];
                    $stmt->bind_param("siddisisss", $title, $id, $amount, $appAmt, $reqBy, $status, $appBy, $appAt, $note, $metaData);
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
            $uploadDir = 'public/uploads/labor_payment_proofs/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext  = strtolower(pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','pdf'];
            if (in_array($ext, $allowed)) {
                $name = 'PAY-' . (int)$id . '-' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $uploadDir . $name)) {
                    $proofImage = $uploadDir . $name;
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
            $_SESSION['success'] = '✅ Full payment of ৳' . number_format($paidAmount, 2) . ' disbursed! Due amount: ৳ 0.00. Requisition is now marked as Paid.';
        } elseif ($result === 'partially_paid') {
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
            $uploadDir = 'public/uploads/labor_disbursement_proofs/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext     = strtolower(pathinfo($_FILES['disbursement_proof']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            if (in_array($ext, $allowed)) {
                $name = 'DISB-' . (int)$id . '-' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['disbursement_proof']['tmp_name'], $uploadDir . $name)) {
                    $proof = $uploadDir . $name;
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
