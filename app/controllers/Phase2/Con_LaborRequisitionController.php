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
    // 12/22 = Site Engineer, 15 = Project Manager
    // 13/24/25 = QC, 3 = Management, 6 = Accounts, 1 = Admin
    private function isSiteEngineer() { return in_array($this->userRole, [12, 22, 1]); }
    private function isPM()           { return in_array($this->userRole, [15, 1]); }
    private function isQC()           { return in_array($this->userRole, [13, 24, 25, 1]); }
    private function isManagement()   { return in_array($this->userRole, [3, 1]);  }
    private function isAccounts()     { return in_array($this->userRole, [6, 1]);  }

    // =========================================================================
    // INDEX
    // =========================================================================
    public function index()
    {
        // SE only sees their own requisitions; PM/QC/Mgmt/Accounts see all
        $requestedBy = null;
        if ($this->isSiteEngineer() && $this->userRole !== 1) $requestedBy = $this->userId;

        $status    = $_GET['status']     ?? null;
        $projectId = $_GET['project_id'] ?? null;

        $requisitions = $this->model->getAll($projectId ?: null, $status ?: null, $requestedBy);

        // SE sees only their assigned projects in filter dropdown; others see all
        if ($this->isSiteEngineer() && $this->userRole !== 1) {
            $rPrj = $this->db->query(
                "SELECT id, name FROM projects WHERE deleted_at IS NULL AND site_engineer_id = {$this->userId} ORDER BY name"
            );
        } else {
            $rPrj = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name");
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
        if (!$this->isSiteEngineer()) {
            $_SESSION['error'] = 'Access denied.';
            header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisitions');
            exit;
        }

        // SE sees only their assigned projects; PM/Mgmt/Admin see all
        if ($this->isSiteEngineer() && $this->userRole !== 1) {
            $rProjects = $this->db->query(
                "SELECT id, name FROM projects WHERE deleted_at IS NULL AND site_engineer_id = {$this->userId} ORDER BY name"
            );
        } else {
            $rProjects = $this->db->query(
                "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name"
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
        if (!$this->isSiteEngineer()) { http_response_code(403); exit; }

        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            $uploadDir = 'public/uploads/labor_requisitions/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext  = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $name = time() . '_' . $this->userId . '.' . $ext;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $name)) {
                $attachment = $uploadDir . $name;
            }
        }

        $data = [
            'project_id'      => (int)$_POST['project_id'],
            'phase_id'        => (int)($_POST['phase_id'] ?? 0) ?: null,
            'phase_target_id' => (int)($_POST['phase_target_id'] ?? 0) ?: null,
            'requested_by'    => $this->userId,
            'requisition_date' => $_POST['requisition_date'],
            'work_date_from'   => !empty($_POST['work_date_from']) ? $_POST['work_date_from'] : null,
            'work_date_to'     => !empty($_POST['work_date_to'])   ? $_POST['work_date_to']   : null,
            'labor_type'      => $_POST['labor_type']     ?? 'General',
            'labor_count'     => (int)$_POST['labor_count'],
            'per_labor_rate'  => (float)$_POST['per_labor_rate'],
            'working_days'    => (float)($_POST['working_days'] ?? 1),
            'purpose'         => trim($_POST['purpose']),
            'remarks'         => trim($_POST['remarks'] ?? ''),
            'attachment'      => $attachment,
            'created_by'      => $this->userId,
        ];

        $id = $this->model->create($data);
        $_SESSION['success'] = 'Labor Requisition submitted successfully.';
        header('Location: ' . $GLOBALS['base_url'] . '/con-labor-requisition/' . $id);
        exit;
    }

    // =========================================================================
    // SHOW
    // =========================================================================
    public function show($id)
    {
        $req = $this->model->find((int)$id);
        if (!$req) { die('Requisition not found.'); }

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
            'isManagement'  => $this->isManagement(),
            'isAccounts'    => $this->isAccounts(),
            'isSE'          => $this->isSiteEngineer(),
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
        $_SESSION['success'] = 'Work marked as completed.';
        $this->back($id);
    }

    public function requestPayment($id)
    {
        $this->model->requestPayment((int)$id, $this->userId);
        $_SESSION['success'] = 'Payment request submitted successfully.';
        $this->back($id);
    }

    // =========================================================================
    // QC APPROVE / REJECT
    // =========================================================================
    public function qcApprove($id)
    {
        if (!$this->isQC()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }
        $note = trim($_POST['qc_note'] ?? '');
        $this->model->qcApprove((int)$id, $this->userId, $note);
        $_SESSION['success'] = 'QC approved the payment request.';
        $this->back($id);
    }

    public function qcReject($id)
    {
        if (!$this->isQC()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }
        $reason = trim($_POST['rejection_reason'] ?? 'Rejected by QC');
        $this->model->qcReject((int)$id, $this->userId, $reason);
        $_SESSION['error'] = 'Payment request rejected by QC.';
        $this->back($id);
    }

    // =========================================================================
    // PM PAYMENT APPROVE / REJECT
    // =========================================================================
    public function pmPaymentApprove($id)
    {
        if (!$this->isPM()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }
        $note = trim($_POST['pm_payment_note'] ?? '');
        $this->model->pmPaymentApprove((int)$id, $this->userId, $note);
        $_SESSION['success'] = 'PM approved for management payment.';
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
    // MANAGEMENT APPROVE / REJECT
    // =========================================================================
    public function managementApprove($id)
    {
        if (!$this->isManagement()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }
        $approvedAmount = (float)($_POST['approved_amount'] ?? 0);
        $note           = trim($_POST['management_note'] ?? '');
        $this->model->managementApprove((int)$id, $this->userId, $approvedAmount, $note);
        $_SESSION['success'] = 'Management approved. Ready for payment.';
        $this->back($id);
    }

    public function managementReject($id)
    {
        if (!$this->isManagement()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }
        $reason = trim($_POST['rejection_reason'] ?? 'Rejected by Management');
        $this->model->managementReject((int)$id, $this->userId, $reason);
        $_SESSION['error'] = 'Rejected by Management.';
        $this->back($id);
    }

    // =========================================================================
    // ACCOUNTS MARK PAID (supports partial payment + proof image)
    // =========================================================================
    public function markPaid($id)
    {
        if (!$this->isAccounts()) { $_SESSION['error'] = 'Access denied.'; $this->back($id); }

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
            'paid_amount'       => (float)$_POST['paid_amount'],
            'paid_by'           => $this->userId,
            'payment_date'      => $_POST['payment_date'],
            'payment_method'    => $_POST['payment_method'] ?? 'Cash',
            'payment_reference' => trim($_POST['payment_reference'] ?? ''),
            'payment_note'      => trim($_POST['payment_note'] ?? ''),
            'proof_image'       => $proofImage,
        ];

        $result = $this->model->markPaid((int)$id, $data);

        if ($result === 'paid') {
            $_SESSION['success'] = '✅ Full payment settled! Requisition is now marked as Paid.';
        } elseif ($result === 'partially_paid') {
            $_SESSION['success'] = '⚠️ Partial payment recorded. Remaining balance needs Management approval.';
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
}
