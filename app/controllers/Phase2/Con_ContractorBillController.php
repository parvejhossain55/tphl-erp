<?php
namespace Phase2;

use Controller;
use Con_ContractorBill;
use Con_Operator;
use Con_FinancialTransaction;
use ApprovalRequest;
use Con_Contractor;
use AccountMailService;

require_once __DIR__ . '/../../views/accounts/AccountMailService.php';

class Con_ContractorBillController extends Controller
{
    protected $db;
    protected $billModel;
    protected $operatorModel;
    protected $approvalModel;
    protected $txnModel;
    protected $targetModel;
    protected $mailService;
    protected $qcInspectionModel;
    protected $userRole;
    protected $userId;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->billModel     = new Con_ContractorBill($this->db);
        $this->operatorModel = new Con_Contractor($this->db);
        $this->approvalModel = new ApprovalRequest($this->db);
        $this->txnModel      = new Con_FinancialTransaction($this->db);
        $this->mailService   = new AccountMailService($this->db);
        
        require_once __DIR__ . '/../../models/Con_QCInspection.php';
        $this->qcInspectionModel = new \Con_QCInspection($this->db);

        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->userId   = $_SESSION['user']['id'] ?? null;
        $this->userRole = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : null;
    }

    // =========================================================================
    // ROLE HELPERS
    // =========================================================================

    /** Roles 1 (Admin), 3 (Super Admin), 22 (PM Head) can approve */
    private function canApprove()
    {
        return in_array($this->userRole, [1, 3, 22]);
    }

    /** QC & Internal Audit role or specific permission check */
    private function canApproveQC()
    {
        if (in_array($this->userRole, [3, 22])) return false;
        if ($this->userRole === 1) return true;
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        return ($roleName === 'qc & internal audit' || hasPermission('QC & Internal Audit'));
    }

    /** Roles 1, 3, 6 (Accounts) can process payments */
    private function canProcessPayment()
    {
        return in_array($this->userRole, [1, 3, 6]);
    }

    // =========================================================================
    // LIST — role-based filtering
    // =========================================================================

    public function index()
    {
        $filterUserId = null;
        if (!in_array($this->userRole, [1, 3, 6, 22])) {
            $filterUserId = $this->userId;
        }

        $bills = $this->billModel->getAllWithRelations($filterUserId);

        $this->view('phase2/contractor-bills/bill-lists', [
            'current_route' => 'con-contractor-bills',
            'page_title'    => 'Contractor Bill Requests',
            'bills'         => $bills,
            'userRole'      => $this->userRole,
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create()
    {
        if (!hasPermission('Contractor Bill') && !in_array($this->userRole, [1, 3])) {
            setError('You do not have permission to create contractor bills.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }
        $operators = $this->operatorModel->getActiveContractors();
        $projects  = $this->getProjects();
        $targets   = [];
        $workTypes = $this->db->query("SELECT id, work_type_name FROM work_types ORDER BY work_type_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/contractor-bills/create-bill', [
            'current_route' => 'con-contractor-bills',
            'page_title'    => 'New Contractor Bill',
            'operators'     => $operators,
            'projects'      => $projects,
            'targets'       => $targets,
            'workTypes'     => $workTypes,
        ]);
    }

    // =========================================================================
    // STORE — auto-create approval_request
    // =========================================================================

    public function store()
    {
        if (!hasPermission('Contractor Bill') && !in_array($this->userRole, [1, 3])) {
            setError('You do not have permission to create contractor bills.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }
        $data = [
            'project_id'       => $_POST['project_id'] ?? null,
            'operator_id'      => $_POST['operator_id'] ?? null,
            'work_order_id'    => !empty($_POST['work_order_id']) ? $_POST['work_order_id'] : null,
            'target_id'        => !empty($_POST['target_id']) ? $_POST['target_id'] : null,
            'bill_type'        => $_POST['bill_type'] ?? 'Progress',
            'bill_date'        => $_POST['bill_date'] ?? date('Y-m-d'),
            'work_description' => trim($_POST['work_description'] ?? ''),
            'bill_amount'      => $_POST['bill_amount'] ?? 0,
            'attachment_path'  => null,
            'status'           => 'submitted',
            'created_by'       => $this->userId,
        ];

        // Handle file upload
        if (!empty($_FILES['attachment']['name'])) {
            $uploadDir = __DIR__ . '/../../../public/uploads/contractor-bills/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = time() . '_' . basename($_FILES['attachment']['name']);
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $filename)) {
                $data['attachment_path'] = 'uploads/contractor-bills/' . $filename;
            }
        }

        if (empty($data['project_id']) || empty($data['operator_id']) || empty($data['work_order_id']) || empty($data['work_description'])) {
            setError('Project, Contractor, Work Order, and Work Description are required.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
            exit;
        }

        // Validate Work Order Budget
        require_once __DIR__ . '/../../models/Con_WorkOrder.php';
        $woModel = new \Con_WorkOrder($this->db);
        $workOrder = $woModel->find($data['work_order_id']);
        if (!$workOrder) {
            setError('Invalid Work Order selected.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
            exit;
        }
        
        $billedSoFarRes = $this->db->query("SELECT COALESCE(SUM(IF(approved_amount IS NOT NULL AND approved_amount > 0, approved_amount, bill_amount)), 0) as total FROM contractor_bill_requests WHERE work_order_id = {$data['work_order_id']} AND status != 'rejected'");
        $billedSoFar = $billedSoFarRes ? (float)$billedSoFarRes->fetch_assoc()['total'] : 0;
        
        $remainingWOAmount = (float)$workOrder['total_amount'] - $billedSoFar;
        if ($data['bill_amount'] > $remainingWOAmount) {
            setError('Bill amount (৳' . number_format($data['bill_amount'], 2) . ') exceeds remaining Work Order limit (৳' . number_format($remainingWOAmount, 2) . ').');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
            exit;
        }

        // Validate Target if selected (Strict Financial Limit for Progress Bills)
        if (!empty($data['target_id'])) {
            $target = $this->findTarget($data['target_id']);
            if ($target) {
                $gainPct = (float)$target['gain_percentage'];
                $targetPct = (float)$target['target_percentage'];
                
                if ($gainPct <= 0) {
                    setError('Selected target must have a Gain % greater than 0 to submit a bill.');
                    header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
                    exit;
                }

                // If this target represents a specific % of the Work Order, enforce financial limit
                if ($targetPct > 0 && $data['bill_type'] === 'Progress') {
                    // Maximum allowable amount for this target: (WO Total * Target%) * Gain%
                    $maxTargetAmount = ((float)$workOrder['total_amount'] * ($targetPct / 100)) * ($gainPct / 100);
                    
                    // Find out how much they already billed specifically for this target
                    $targetBilledRes = $this->db->query("SELECT COALESCE(SUM(IF(approved_amount IS NOT NULL AND approved_amount > 0, approved_amount, bill_amount)), 0) as total FROM contractor_bill_requests WHERE target_id = {$data['target_id']} AND status != 'rejected'");
                    $targetBilledSoFar = $targetBilledRes ? (float)$targetBilledRes->fetch_assoc()['total'] : 0;
                    
                    $remainingTargetAmount = $maxTargetAmount - $targetBilledSoFar;
                    
                    // Use a tiny 1-taka margin for float rounding issues
                    if ($data['bill_amount'] > ($remainingTargetAmount + 1.00)) {
                        setError('Bill amount (৳' . number_format($data['bill_amount'], 2) . ') exceeds the completed work value for this target. Maximum allowable request is ৳' . number_format($remainingTargetAmount, 2) . '.');
                        header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
                        exit;
                    }
                }
            }
        }


        $billId = $this->billModel->create($data);

        if ($billId) {
            $operator = $this->operatorModel->find($data['operator_id']);
            $project  = $this->db->query("SELECT name FROM projects WHERE id = " . (int)$data['project_id'])->fetch_assoc();

            $this->approvalModel->create([
                'department'    => 'construction',
                'request_type'  => 'contractor_bill',
                'title'         => 'Contractor Bill — ' . ($operator['name'] ?? '') . ' | ' . ($project['name'] ?? ''),
                'source_table'  => 'contractor_bill_requests',
                'source_id'     => $billId,
                'requested_days'=> 0,
                'requested_by'  => $this->userId,
                'approve_role'  => 'management',
                'meta_data'     => null,
                'request_amount'=> $data['bill_amount'],
            ]);

            // Send email notification to Management + Accounts
            try {
                $this->mailService->sendContractorBillOnSubmission([
                    'bill_amount'      => $data['bill_amount'],
                    'contractor_name'  => $operator['name'] ?? '',
                    'work_description' => $data['work_description'],
                    'project_name'     => $project['name'] ?? '',
                    'project_id'       => $data['project_id'],
                    'created_by'       => $this->userId,
                ]);
            } catch (\Exception $e) {
                error_log("Contractor Bill email (submission) failed: " . $e->getMessage());
            }

            setSuccess('Contractor bill submitted and sent for approval!');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$billId}");
            exit;
        }

        setError('Failed to submit contractor bill.');
        header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
        exit;
    }

    // =========================================================================
    // SHOW — Enhanced single-page experience
    // =========================================================================

    public function show($id)
    {
        $bill = $this->billModel->findWithRelations($id);
        if (!$bill) {
            setError('Bill not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        // Get payment history (con_financial_transactions)
        $payments   = $this->txnModel->getByRef('contractor_bill', $id);
        $totalPaid  = $this->txnModel->getTotalPaidForSource('contractor_bill', $id);
        $payableAmt = (float)($bill['approved_amount'] ?? $bill['bill_amount'] ?? 0);
        $dueAmount  = max(0, $payableAmt - $totalPaid);

        // Get linked approval_request
        $approvalReq = $this->getApprovalRequest($id);

        // Get banks for payment modal
        $banks = $this->db->query("SELECT id, bank_name, branch_name FROM banks WHERE status = 'active' ORDER BY bank_name")->fetch_all(MYSQLI_ASSOC);

        // Fetch weekly progress items for the target if linked
        $targetItems = [];
        if (!empty($bill['target_id'])) {
            $stmt = $this->db->prepare("
                SELECT pwpi.*, pmti.work_item_name, pmti.unit, c.name AS contractor_name
                FROM phase_weekly_progress_items pwpi
                INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
                LEFT JOIN contractors c ON c.id = pwpi.contractor_id
                WHERE pwpi.weekly_progress_id = ?
            ");
            $stmt->bind_param('i', $bill['target_id']);
            $stmt->execute();
            $targetItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/contractor-bills/show-bill', [
            'current_route'     => 'con-contractor-bills',
            'page_title'        => 'Contractor Bill Detail',
            'bill'              => $bill,
            'payments'          => $payments,
            'total_paid'        => $totalPaid,
            'due_amount'        => $dueAmount,
            'payable_amount'    => $payableAmt,
            'approval_request'  => $approvalReq,
            'canApprove'        => $this->canApprove(),
            'canApproveQC'      => $this->canApproveQC(),
            'canProcessPayment' => $this->canProcessPayment(),
            'userRole'          => $this->userRole,
            'banks'             => $banks,
            'target_items'      => $targetItems,
        ]);
    }

    // =========================================================================
    // VERIFY (Site Engineer / Operation Head)
    // =========================================================================

    public function verify($id)
    {
        $data = [
            'status'            => 'verified',
            'verified_by'       => $this->userId,
            'verified_at'       => date('Y-m-d H:i:s'),
            'verification_note' => trim($_POST['verification_note'] ?? ''),
        ];

        if ($this->billModel->updateStatus($id, $data)) {
            setSuccess('Bill verified successfully!');
        } else {
            setError('Verification failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/$id");
        exit;
    }

    // =========================================================================
    // APPROVE (QC OR MANAGEMENT)
    // =========================================================================

    public function approve($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $bill = $this->billModel->find($id);
        if (!$bill) {
            setError('Bill not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        $isQcApproval = isset($_POST['status']) && $_POST['status'] === 'qc_approved';

        if ($isQcApproval) {
            // -- QC APPROVAL PATH --
            if (!$this->canApproveQC()) {
                setError('You are not authorized for QC approval.');
                header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                exit;
            }

            if (!in_array($bill['status'], ['submitted', 'verified'])) {
                setError('Bill is not ready for QC approval.');
                header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                exit;
            }

            $qcNote = trim($_POST['approval_note'] ?? '');
            if ($this->billModel->updateQCStatus($id, $this->userId, $qcNote)) {
                setSuccess('Bill QC Approved successfully.');
            } else {
                setError('Failed to QC approve bill.');
            }
        } else {
            // -- MANAGEMENT APPROVAL PATH --
            if (!$this->canApprove()) {
                setError('You are not authorized to approve bills.');
                header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                exit;
            }

            if (!in_array($bill['status'], ['submitted', 'verified', 'qc_approved'])) {
                setError('Bill is not in an approvable status.');
                header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                exit;
            }

            $approvedAmount = (float)($_POST['approved_amount'] ?? $bill['bill_amount']);
            if ($approvedAmount <= 0) {
                $approvedAmount = (float)$bill['bill_amount'];
            }
            $managementNote = trim($_POST['management_note'] ?? '');

            if ($this->billModel->approve($id, $this->userId, $approvedAmount, $managementNote)) {
                $this->syncApprovalRequest($id, 'approved');

                // Send email notification on approval
                try {
                    $updatedBill = $this->billModel->find($id);
                    if ($updatedBill) {
                        // Fetch contractor and project names for the email
                        $operator = $this->operatorModel->find($bill['operator_id'] ?? 0);
                        $project  = $this->db->query("SELECT name FROM projects WHERE id = " . (int)($bill['project_id'] ?? 0))->fetch_assoc();
                        $updatedBill['contractor_name'] = $operator['name'] ?? '';
                        $updatedBill['project_name']    = $project['name'] ?? '';

                        $this->mailService->sendContractorBillOnApproval(
                            $updatedBill,
                            'approved',
                            $this->userId
                        );
                    }
                } catch (\Exception $e) {
                    error_log("Contractor Bill email (approval) failed: " . $e->getMessage());
                }

                setSuccess('Bill approved with amount ৳' . number_format($approvedAmount, 2));
            } else {
                setError('Failed to approve or bill already processed.');
            }
        }

        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
        exit;
    }

    // =========================================================================
    // REJECT — Management (roles 1,3)
    // =========================================================================

    public function reject($id)
    {
        if (!$this->canApprove() && !$this->canApproveQC()) {
            setError('You are not authorized to reject bills.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        if ($this->billModel->reject($id, $this->userId)) {
            $this->syncApprovalRequest($id, 'rejected');
            
            // Send email notification on rejection
            try {
                $rejectedBill = $this->billModel->find($id);
                if ($rejectedBill) {
                    $operator = $this->operatorModel->find($rejectedBill['operator_id'] ?? 0);
                    $project  = $this->db->query("SELECT name FROM projects WHERE id = " . (int)($rejectedBill['project_id'] ?? 0))->fetch_assoc();
                    $rejectedBill['contractor_name'] = $operator['name'] ?? '';
                    $rejectedBill['project_name']    = $project['name'] ?? '';

                    $this->mailService->sendContractorBillOnRejection(
                        $rejectedBill,
                        $this->userId
                    );
                }
            } catch (\Exception $e) {
                error_log("Contractor Bill email (rejection) failed: " . $e->getMessage());
            }

            setSuccess('Bill has been rejected.');
        } else {
            setError('Failed to reject bill.');
        }
        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
        exit;
    }

    // =========================================================================
    // ADD PAYMENT — Accounts (roles 1,3,6)
    // =========================================================================

    public function addPayment($id)
    {
        if (!$this->canProcessPayment()) {
            setError('You are not authorized to process payments.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $bill = $this->billModel->find($id);
        if (!$bill || !in_array($bill['status'], ['approved', 'partially_paid'])) {
            setError('Bill is not in a payable status.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        // Handle file upload (mandatory)
        $proofPaths = [];
        if (!empty($_FILES['disbursement_proof']['name'][0])) {
            foreach ($_FILES['disbursement_proof']['tmp_name'] as $idx => $tmpName) {
                if ($_FILES['disbursement_proof']['error'][$idx] !== UPLOAD_ERR_OK) continue;
                $file = [
                    'name'     => $_FILES['disbursement_proof']['name'][$idx],
                    'tmp_name' => $tmpName,
                    'size'     => $_FILES['disbursement_proof']['size'][$idx],
                    'error'    => $_FILES['disbursement_proof']['error'][$idx],
                ];
                $res = $this->handleFileUpload($file);
                if ($res['success']) {
                    $proofPaths[] = $res['file_path'];
                } else {
                    setError($res['message']);
                    header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                    exit;
                }
            }
        }

        if (empty($proofPaths)) {
            setError('Disbursement proof is required.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            setError('Payment amount must be greater than zero.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $totalPaidSoFar = $this->txnModel->getTotalPaidForSource('contractor_bill', $id);
        $payableAmt     = (float)($bill['approved_amount'] ?? $bill['bill_amount']);
        $remainingDue   = max(0, $payableAmt - $totalPaidSoFar);

        if ($amount > $remainingDue + 0.01) {
            setError("Payment (৳" . number_format($amount, 2) . ") exceeds remaining due (৳" . number_format($remainingDue, 2) . ").");
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $paymentType = ($amount < $remainingDue - 0.01) ? 'Partial' : 'Full';

        $data = [
            'ref_type'           => 'contractor_bill',
            'ref_id'             => (int)$id,
            'project_id'         => (int)($bill['project_id'] ?? 0),
            'amount'             => $amount,
            'payment_type'       => $paymentType,
            'payment_method'     => $_POST['payment_method'] ?? 'Cash',
            'payment_date'       => $_POST['payment_date'] ?? date('Y-m-d'),
            'reference_no'       => trim($_POST['reference_no'] ?? ''),
            'notes'              => trim($_POST['notes'] ?? ''),
            'disbursement_proof' => json_encode($proofPaths),
            'processed_by'       => $this->userId,
        ];

        $txnId = $this->txnModel->create($data);

        if ($txnId) {
            setSuccess('Payment of ৳' . number_format($amount, 2) . ' recorded! Transaction #' . $txnId);
        } else {
            setError('Failed to process payment.');
        }

        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
        exit;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function getApprovalRequest($billId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM approval_requests
             WHERE source_table = 'contractor_bill_requests' AND source_id = ?
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param('i', $billId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function syncApprovalRequest($billId, $newStatus)
    {
        $arStatus = ($newStatus === 'approved') ? 'approved' : 'rejected';

        $stmt = $this->db->prepare(
            "UPDATE approval_requests
             SET status = ?, approved_by = ?, approved_at = NOW()
             WHERE source_table = 'contractor_bill_requests' AND source_id = ? AND status = 'pending'"
        );
        $stmt->bind_param('sii', $arStatus, $this->userId, $billId);
        $stmt->execute();
    }

    private function handleFileUpload($file)
    {
        $allowedTypes = ['doc', 'docx', 'pdf', 'jpg', 'jpeg', 'png'];
        $maxSize      = 5 * 1024 * 1024;

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedTypes)) {
            return ['success' => false, 'message' => "File '{$file['name']}': only DOC, DOCX, PDF, JPG, PNG allowed."];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => "File '{$file['name']}' exceeds 5MB limit."];
        }

        $newFileName = 'con_bill_payment_' . time() . '_' . uniqid() . '.' . $fileExt;
        $uploadPath  = 'uploads/construction-payments/' . $newFileName;
        $fullPath    = __DIR__ . '/../../../public/' . $uploadPath;

        $dir = dirname($fullPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => true, 'file_path' => $uploadPath];
        }

        return ['success' => false, 'message' => "Failed to upload file '{$file['name']}'."];
    }

    // =========================================================================
    // HELPER — Get projects filtered by role
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
            "SELECT p.id, p.name 
             FROM projects p
             WHERE p.deleted_at IS NULL AND {$filter}
             ORDER BY p.name ASC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function findTarget($targetId)
    {
        $stmt = $this->db->prepare("
            SELECT pwp.id,
                   pwp.overall_progress_pct AS gain_percentage,
                   100.00 AS target_percentage,
                   pmt.target_year AS year,
                   pwp.week_number,
                   pp.phase_name AS target_title
            FROM phase_weekly_progress pwp
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
            INNER JOIN project_phases pp ON pp.id = pwp.phase_id
            WHERE pwp.id = ?
        ");
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function ajaxGetTargetsByProject()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$projectId) {
            echo json_encode(['targets' => []]);
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT pwp.id AS id,
                   pmt.target_year AS year,
                   pwp.week_number AS week_number,
                   pp.phase_name AS target_title,
                   pwp.overall_progress_pct AS gain_percentage
            FROM phase_weekly_progress pwp
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
            INNER JOIN project_phases pp ON pp.id = pwp.phase_id
            WHERE pwp.project_id = ? AND pwp.entry_status = 'Approved'
            ORDER BY pmt.target_year DESC, pmt.target_month_number DESC, pwp.week_number DESC
        ");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $targets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['targets' => $targets]);
        exit;
    }
}