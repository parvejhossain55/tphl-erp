<?php
namespace Phase2;

use Controller;
use Con_VendorPaymentRequest;
use Con_Vendor;
use Con_PurchaseOrder;
use Con_FinancialTransaction;
use ApprovalRequest;
use Project;
use AccountMailService;

require_once __DIR__ . '/../../views/accounts/AccountMailService.php';

class Con_VendorPaymentRequestController extends Controller
{
    protected $db;
    protected $model;
    protected $vendorModel;
    protected $projectModel;
    protected $poModel;
    protected $txnModel;
    protected $approvalModel;
    protected $mailService;
    protected $userRole;
    protected $userId;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->model         = new Con_VendorPaymentRequest($db);
        $this->vendorModel   = new Con_Vendor($db);
        $this->projectModel  = new Project($db);
        $this->poModel       = new Con_PurchaseOrder($db);
        $this->txnModel      = new Con_FinancialTransaction($db);
        $this->approvalModel = new ApprovalRequest($db);
        $this->mailService   = new AccountMailService($db);

        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->userId   = $_SESSION['user']['id'] ?? null;
        $this->userRole = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : null;
    }

    // =========================================================================
    // ROLE HELPERS
    // =========================================================================

    /** Roles 1 (Admin), 3 (Super Admin) can approve */
    private function canApprove()
    {
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        return (
            in_array($this->userRole, [1, 3, 33]) 
            || strpos($roleName, 'admin') !== false 
            || strpos($roleName, 'management') !== false
        );
    }
    
    /** Roles that can do QC Approval */
    private function canApproveQC()
    {
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        return (
            strpos($roleName, 'qc') !== false 
            || strpos($roleName, 'quality controller') !== false 
            || hasPermission('QC & Internal Audit') 
            || hasPermission('QC Dashboard') 
            || $this->canApprove()
        );
    }

    /** Helper to get assigned project IDs for a user */
    private function getAssignedProjectIds(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT id FROM projects
            WHERE deleted_at IS NULL
              AND (site_engineer_id   = ?
                OR pm_id              = ?
                OR operation_head_id  = ?
                OR qc_id              = ?
                OR struct_engineer_id = ?
                OR draftsman_id       = ?
                OR landman_id         = ?
                OR architect_id       = ?
                OR estimator_id       = ?
                OR operator_id        = ?
                OR purchase_user_id   = ?)
        ");
        if ($stmt) {
            $stmt->bind_param('iiiiiiiiiii', $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $ids = [];
            while ($row = $result->fetch_assoc()) {
                $ids[] = (int)$row['id'];
            }
            return $ids;
        }
        return [];
    }

    /** Roles 1, 3, 6 (Accounts) can process payments */
    private function canProcessPayment()
    {
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        return (
            in_array($this->userRole, [1, 3, 33, 6, 30]) 
            || strpos($roleName, 'admin') !== false 
            || strpos($roleName, 'management') !== false 
            || strpos($roleName, 'accounts') !== false
        );
    }

    // =========================================================================
    // LIST — role-based filtering
    // =========================================================================

    public function index()
    {
        // Roles 1 (Admin), 3 (Super Admin), 6 (Accounts), 22 (PM Head), 17 (Purchase Head) see ALL requests
        // Other roles only see their own requests OR requests for projects they are assigned to
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        $isPrivileged = (
            in_array($this->userRole, [1, 3, 33, 6, 22, 17, 31])
            || strpos($roleName, 'admin') !== false
            || strpos($roleName, 'management') !== false
            || strpos($roleName, 'accounts') !== false
            || strpos($roleName, 'pm head') !== false
            || strpos($roleName, 'purchase head') !== false
            || strpos($roleName, 'purchase team leader') !== false
            || strpos($roleName, 'operation head') !== false
            || strpos($roleName, 'senior purchase executive') !== false
        );

        $filterUserId = null;
        $assignedProjectIds = [];
        $qcApprovedCheck = $this->canApproveQC();
        if (!$isPrivileged && !$qcApprovedCheck) {
            $filterUserId = $this->userId;
            $assignedProjectIds = $this->getAssignedProjectIds($this->userId);
        }

        // Handle debug output if requested
        if (isset($_GET['debug'])) {
            echo "<h2>Debug Mode</h2>";
            echo "<pre>";
            echo "Session User details:\n";
            print_r($_SESSION['user'] ?? 'No user in session');
            echo "\nUser Role ID: " . var_export($this->userRole, true) . "\n";
            echo "User ID: " . var_export($this->userId, true) . "\n";
            echo "Is Privileged: " . var_export($isPrivileged, true) . "\n";
            echo "Can Approve QC: " . var_export($qcApprovedCheck, true) . "\n";
            echo "Filter User ID: " . var_export($filterUserId, true) . "\n";
            echo "Assigned Project IDs: " . var_export($assignedProjectIds, true) . "\n";
            
            // Build the query to display it
            $sql = "SELECT pr.*,
                           v.name       AS vendor_name,
                           p.name       AS project_name,
                           po.id        AS po_number,
                           u.full_name  AS created_by_name,
                           ab.full_name AS approved_by_name,
                           ab_r.name    AS approved_by_role,
                           pb.full_name AS payment_by_name,
                           COALESCE(txn_sum.total_paid, 0) AS already_paid
                    FROM vendor_payment_requests pr
                    LEFT JOIN vendors v          ON v.id  = pr.vendor_id
                    LEFT JOIN projects p         ON p.id  = pr.project_id
                    LEFT JOIN purchase_orders po ON po.id = pr.purchase_order_id
                    LEFT JOIN users u            ON u.id  = pr.created_by
                    LEFT JOIN users ab           ON ab.id = pr.approved_by
                    LEFT JOIN roles ab_r         ON ab_r.id = ab.role_id
                    LEFT JOIN (
                        SELECT t1.ref_id, t1.processed_by
                        FROM con_financial_transactions t1
                        INNER JOIN (
                            SELECT MAX(id) AS max_id
                            FROM con_financial_transactions
                            WHERE ref_type = 'vendor_payment'
                            GROUP BY ref_id
                        ) t2 ON t1.id = t2.max_id
                    ) latest_txn ON latest_txn.ref_id = pr.id
                    LEFT JOIN users pb ON pb.id = latest_txn.processed_by
                    LEFT JOIN (
                        SELECT ref_id, SUM(amount_paid) AS total_paid
                        FROM con_financial_transactions
                        WHERE ref_type = 'vendor_payment'
                        GROUP BY ref_id
                    ) txn_sum ON txn_sum.ref_id = pr.id";

            $whereClauses = [];
            if ($filterUserId !== null) {
                $userCond = "pr.created_by = " . (int)$filterUserId;
                if (!empty($assignedProjectIds)) {
                    $idList = implode(',', array_map('intval', $assignedProjectIds));
                    $whereClauses[] = "($userCond OR pr.project_id IN ($idList))";
                } else {
                    $whereClauses[] = $userCond;
                }
            }

            if (!empty($whereClauses)) {
                $sql .= " WHERE " . implode(" AND ", $whereClauses);
            }
            $sql .= " ORDER BY pr.id DESC";

            echo "\nExecuting SQL Query:\n" . $sql . "\n";
            $res = $this->db->query($sql);
            if (!$res) {
                echo "\nMySQL Error: " . $this->db->error . "\n";
            } else {
                echo "\nQuery Succeeded! Rows returned: " . $res->num_rows . "\n";
                print_r($res->fetch_all(MYSQLI_ASSOC));
            }

            // Dump model details
            try {
                $reflector = new \ReflectionClass('Con_VendorPaymentRequest');
                echo "\nModel Class Source File: " . $reflector->getFileName() . "\n";
            } catch (\Throwable $e) {
                echo "\nReflection Error: " . $e->getMessage() . "\n";
            }

            echo "\nCalling \$this->model->getAll() directly:\n";
            try {
                $modelRes = $this->model->getAll($filterUserId, $assignedProjectIds);
                echo "Returned rows count: " . count($modelRes) . "\n";
                print_r($modelRes);
            } catch (\Throwable $e) {
                echo "Error calling getAll(): " . $e->getMessage() . "\n";
            }
            
            echo "</pre>";
            exit;
        }

        $requests = $this->model->getAll($filterUserId, $assignedProjectIds);

        // Fetch QC Audited/Approved POs (having qc_approved_by_id)
        $qcApprovedPOs = [];
        $stmtPO = $this->db->prepare("
            SELECT po.id, po.status, po.created_at,
                   pr.project_id, p.name AS project_name,
                   v.id AS vendor_id, v.name AS vendor_name, v.company_name AS vendor_company,
                   (SELECT COALESCE(SUM(quantity * unit_price), 0) FROM purchase_order_items WHERE purchase_order_id = po.id) AS total_amount,
                   (SELECT COALESCE(SUM(vpr.net_payable_amount), 0) FROM vendor_payment_requests vpr WHERE vpr.purchase_order_id = po.id AND vpr.status IN ('Approved','Paid','Partially_Paid','Fully_Paid')) AS total_paid
            FROM purchase_orders po
            JOIN purchase_requests pr ON pr.id = po.request_id
            LEFT JOIN projects p ON p.id = pr.project_id
            LEFT JOIN vendors v ON v.id = po.vendor_id
            WHERE po.qc_approved_by_id IS NOT NULL 
              AND po.status != 'Cancelled'
            ORDER BY po.id DESC
        ");
        if ($stmtPO) {
            $stmtPO->execute();
            $qcApprovedPOs = $stmtPO->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/vendor-payment-requests/payment-lists', [
            'current_route'   => 'con-vendor-payment-requests',
            'page_title'      => 'Vendor Payment Requests',
            'requests'        => $requests,
            'qc_approved_pos' => $qcApprovedPOs,
            'userRole'        => $this->userRole,
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create()
    {
        // Filter projects to only those assigned to the current user
        // (as site engineer, operation head, PM, or admin/super-admin)
        $userId = (int)$this->userId;
        $roleId = (int)$this->userRole;

        // Payment create — show under construction projects
        $projectStmt = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND under_construction = 1 ORDER BY name");
        $projects = ($projectStmt instanceof \mysqli_result)
            ? $projectStmt->fetch_all(MYSQLI_ASSOC)
            : [];

        $vendors      = $this->vendorModel->getAll();
        $paymentTypes = ['Advance', 'Partial', 'Final'];

        $this->view('phase2/vendor-payment-requests/create-payment', [
            'current_route' => 'con-vendor-payment-requests',
            'page_title'    => 'Create Payment Request',
            'projects'      => $projects,
            'vendors'       => $vendors,
            'payment_types' => $paymentTypes,
            'all_pos'       => [],
        ]);
    }

    // =========================================================================
    // STORE — auto-create approval_request
    // =========================================================================

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId      = (int)($_SESSION['user']['id'] ?? 0);
        $vendorId    = (int)($_POST['vendor_id'] ?? 0);
        $paymentType = $_POST['payment_type'] ?? 'Final';
        $invoiceAmt  = (float)($_POST['invoice_amount'] ?? 0);
        // purchase_order_id is NOT NULL in DB — use 0 if not selected
        $poId        = (int)($_POST['purchase_order_id'] ?? 0);

        // Advance: no penalty deduction, net = invoice amount
        if ($paymentType === 'Advance') {
            $penaltyDeducted = 0.00;
            $netPayable      = $invoiceAmt;
        } else {
            $penaltyDeducted = $this->model->getPendingPenaltySum($vendorId);
            $netPayable      = max(0, $invoiceAmt - $penaltyDeducted);
        }

        $data = [
            'project_id'        => (int)($_POST['project_id'] ?? 0),
            'vendor_id'         => $vendorId,
            'purchase_order_id' => $poId,          // int (0 = no PO)
            'payment_type'      => $paymentType,
            'invoice_amount'    => $invoiceAmt,
            'penalty_deducted'  => $penaltyDeducted,
            'net_payable'       => $netPayable,
            'delivery_verified' => ($paymentType === 'Advance') ? 0 : (int)($_POST['delivery_verified'] ?? 0),
            'created_by'        => $userId,
        ];

        // Handle optional payment_attachment file upload
        if (!empty($_FILES['payment_attachment']['name'])) {
            $uploadResult = $this->handleFileUpload($_FILES['payment_attachment']);
            if ($uploadResult['success']) {
                $data['payment_attachment'] = $uploadResult['file_path'];
            } else {
                setError($uploadResult['message']);
                header("Location: {$GLOBALS['base_url']}/con-vendor-payment-create");
                exit;
            }
        }

        if (!$data['project_id'] || !$data['vendor_id'] || $data['invoice_amount'] <= 0) {
            setError('Project, Vendor, and a valid Amount are required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-create");
            exit;
        }

        $id = $this->model->create($data);
        if ($id) {
            // Fetch project and vendor names for approval title
            $project = $this->db->query("SELECT name FROM projects WHERE id = " . (int)$data['project_id'])->fetch_assoc();
            $vendor  = $this->db->query("SELECT name FROM vendors WHERE id = " . (int)$vendorId)->fetch_assoc();

            // Set approve_role: management if created by Purchase Head (role 17), else purchase team leader
            $approveRole = ($this->userRole === 17) ? 'management' : 'purchase team leader';

            // Now create the approval_request for management immediately
            $this->approvalModel->create([
                'department'     => 'construction',
                'request_type'   => 'vendor_payment',
                'title'          => 'Vendor Payment — ' . ($vendor['name'] ?? '') . ' | ' . ($project['name'] ?? ''),
                'source_table'   => 'vendor_payment_requests',
                'source_id'      => $id,
                'requested_days' => 0,
                'requested_by'   => $userId,
                'approve_role'   => $approveRole,
                'meta_data'      => null,
                'request_amount' => $netPayable,
            ]);

            // Send email notification to Management + Accounts
            try {
                $this->mailService->sendVendorPaymentOnSubmission([
                    'invoice_amount' => $invoiceAmt,
                    'net_payable'    => $netPayable,
                    'payment_type'   => $paymentType,
                    'vendor_name'    => $vendor['name'] ?? '',
                    'project_name'   => $project['name'] ?? '',
                    'project_id'     => $data['project_id'],
                    'created_by'     => $userId,
                ]);
            } catch (\Exception $e) {
                error_log("Vendor Payment email (submission) failed: " . $e->getMessage());
            }

            $msg = ($paymentType === 'Advance')
                ? "Advance Payment Request created. Amount: ৳ " . number_format($invoiceAmt, 2)
                : "Payment Request created. Penalty deducted: ৳ " . number_format($penaltyDeducted, 2);
            setSuccess($msg);
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
        } else {
            setError('Failed to create payment request.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-create");
        }
        exit;
    }

    // =========================================================================
    // SHOW — Enhanced single-page experience (like fund requests)
    // =========================================================================

    public function show($id)
    {
        $request = $this->model->find($id);
        if (!$request) {
            setError('Payment request not found.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-requests");
            exit;
        }

        // Fetch penalties linked to this request
        $penalties = [];
        $pStmt = $this->db->prepare("SELECT * FROM vendor_penalties WHERE payment_request_id = ?");
        if ($pStmt) {
            $pStmt->bind_param('i', $id);
            $pStmt->execute();
            $res = $pStmt->get_result();
            if ($res) {
                $penalties = $res->fetch_all(MYSQLI_ASSOC);
            }
        }

        // If Final/Partial: calculate total advance already paid for this PO
        $advancePaid = 0.00;
        if (!empty($request['purchase_order_id']) && $request['payment_type'] !== 'Advance') {
            $advancePaid = $this->model->getTotalAdvancePaid($request['purchase_order_id']);
        }

        // Fetch all payments for the same PO (for history)
        $poPayments = [];
        if (!empty($request['purchase_order_id'])) {
            $poPayments = $this->model->getByPurchaseOrder($request['purchase_order_id']);
        }

        // Get payment history (con_financial_transactions)
        $payments   = $this->txnModel->getByRef('vendor_payment', $id);
        $totalPaid  = $this->txnModel->getTotalPaidForSource('vendor_payment', $id);
        $payableAmt = (float)($request['approved_amount'] ?? $request['net_payable'] ?? 0);
        $dueAmount  = max(0, $payableAmt - $totalPaid);

        // Get linked approval_request for sync check
        $approvalReq = $this->getApprovalRequest($id);

        // Get banks for payment modal dropdown
        $banks = $this->db->query("SELECT id, bank_name, branch_name FROM banks WHERE status = 'active' ORDER BY bank_name")->fetch_all(MYSQLI_ASSOC);

        // Fetch extra documents/notes added post-approval
        $documents = [];
        $stmtDoc = $this->db->prepare("SELECT d.*, u.full_name as uploaded_by_name FROM vendor_payment_request_documents d LEFT JOIN users u ON u.id = d.uploaded_by WHERE d.vendor_payment_request_id = ? ORDER BY d.id ASC");
        if ($stmtDoc) {
            $stmtDoc->bind_param('i', $id);
            $stmtDoc->execute();
            $documents = $stmtDoc->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        // Fetch PO related stock for QC verification
        $poStocks = [];
        if (!empty($request['purchase_order_id']) && !empty($request['project_id'])) {
            $stmtStock = $this->db->prepare("
                SELECT poi.material_id, m.name AS material_name, m.unit, poi.quantity AS po_qty,
                       COALESCE((SELECT SUM(s.current_quantity) FROM con_stocks s JOIN con_warehouses w ON w.id = s.warehouse_id WHERE s.material_id = poi.material_id AND w.project_id = ?), 0) AS current_stock
                FROM purchase_order_items poi
                JOIN con_materials m ON m.id = poi.material_id
                WHERE poi.purchase_order_id = ?
            ");
            if ($stmtStock) {
                $stmtStock->bind_param('ii', $request['project_id'], $request['purchase_order_id']);
                $stmtStock->execute();
                $poStocks = $stmtStock->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        }

        // Fetch linked expense adjustments (Settlements)
        $adjustments = [];
        $stmtAdj = $this->db->prepare("
            SELECT ea.*, u.full_name as requester_name 
            FROM expense_adjustments ea 
            JOIN users u ON ea.user_id = u.id 
            WHERE ea.cashout_request_id = ? AND ea.parent_type = 'vendor'
            ORDER BY ea.id DESC
        ");
        if ($stmtAdj) {
            $stmtAdj->bind_param('i', $id);
            $stmtAdj->execute();
            $adjustments = $stmtAdj->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        // Fetch material receiving logs (GRNs) against PO
        $materialLogs = [];
        if (!empty($request['purchase_order_id']) && (int)$request['purchase_order_id'] > 0) {
            $stmtMat = $this->db->prepare("
                SELECT mr.*, u.full_name as received_by_name, w.name as warehouse_name
                FROM material_receiving mr
                LEFT JOIN users u ON u.id = mr.received_by
                LEFT JOIN con_warehouses w ON w.id = mr.warehouse_id
                WHERE mr.purchase_order_id = ?
                ORDER BY mr.id DESC
            ");
            if ($stmtMat) {
                $stmtMat->bind_param('i', $request['purchase_order_id']);
                $stmtMat->execute();
                $resMat = $stmtMat->get_result();
                if ($resMat) {
                    $materialLogs = $resMat->fetch_all(MYSQLI_ASSOC);
                }
            }
        }

        $this->view('phase2/vendor-payment-requests/show-payment', [
            'current_route'     => 'con-vendor-payment-requests',
            'page_title'        => 'Payment Request Details',
            'request'           => $request,
            'penalties'         => $penalties,
            'advance_paid'      => $advancePaid,
            'po_payments'       => $poPayments,
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
            'documents'         => $documents,
            'poStocks'          => $poStocks,
            'adjustments'       => $adjustments,
            'material_logs'     => $materialLogs,
        ]);
    }

    // =========================================================================
    // ADD DOCUMENT & NOTE (POST-APPROVAL FLEXIBILITY)
    // =========================================================================

    public function addDocument($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $note = trim($_POST['note'] ?? '');

        // Upload file if present
        $documentPath = null;
        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleFileUpload($_FILES['document']);
            if ($uploadResult['success']) {
                $documentPath = $uploadResult['file_path'];
            } else {
                setError($uploadResult['message']);
                header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
                exit;
            }
        }

        if (empty($note) && empty($documentPath)) {
            setError('Please provide a note or a document.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO vendor_payment_request_documents (vendor_payment_request_id, document_path, note, uploaded_by, created_at) VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param('issi', $id, $documentPath, $note, $userId);
        
        if ($stmt->execute()) {
            setSuccess('Document/Note added successfully.');
        } else {
            setError('Failed to add document.');
        }

        header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
        exit;
    }

    // =========================================================================
    // APPROVE — 2-Tier: QC -> Management
    // =========================================================================

    public function approve($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        $request = $this->model->find($id);
        if (!$request) {
            setError('Payment request not found.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['Approved'])) {
            setError('Invalid status.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        if ($status === 'Approved') {
            if (!$this->canApprove()) {
                setError('You are not authorized to approve payment requests.');
                header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
                exit;
            }
            if (!in_array($request['status'], ['Submitted', 'QC Approved', 'Approved', 'Partially_Paid'])) {
                setError('Payment request status is invalid for approval.');
                header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
                exit;
            }

            $inputAmount = (float)($_POST['approved_amount'] ?? 0);
            $approvedAmtSoFar = (float)($request['approved_amount'] ?? 0);
            $netPayable = (float)($request['net_payable'] ?? 0);
            $unapprovedAmt = max(0, $netPayable - $approvedAmtSoFar);

            if ($inputAmount <= 0) {
                $inputAmount = $unapprovedAmt;
            }

            if ($request['status'] === 'Approved' || $request['status'] === 'Partially_Paid') {
                $additionalApproved = $inputAmount;
                if ($additionalApproved > $unapprovedAmt + 0.01) {
                    $additionalApproved = $unapprovedAmt;
                }
                $approvedAmount = $approvedAmtSoFar + $additionalApproved;
            } else {
                $approvedAmount = $inputAmount;
                if ($approvedAmount > $netPayable + 0.01) {
                    $approvedAmount = $netPayable;
                }
            }

            $mgtNote = trim($_POST['approval_note'] ?? '');

            if ($this->model->approve($id, $this->userId, $approvedAmount)) {
                // Update management note
                $stmt = $this->db->prepare("UPDATE vendor_payment_requests SET management_note = ? WHERE id = ?");
                $stmt->bind_param('si', $mgtNote, $id);
                $stmt->execute();
                
                // Sync approval_requests table
                $this->syncApprovalRequest($id, 'approved');

                // AUTO-GENERATE ACCRUAL JOURNAL
                try {
                    require_once __DIR__ . '/../../services/JournalIntegrationService.php';
                    $journalService = new \Services\JournalIntegrationService($this->db);
                    $journalService->generateVendorAccrual($id, $this->userId);
                } catch (\Exception $e) {
                    error_log("Failed to auto-generate vendor accrual journal: " . $e->getMessage());
                }

                // Send email notification on approval
                try {
                    $updatedRequest = $this->model->find($id);
                    if ($updatedRequest) {
                        // Fetch vendor name for the email
                        $vendor = $this->db->query("SELECT name FROM vendors WHERE id = " . (int)($request['vendor_id'] ?? 0))->fetch_assoc();
                        $project = $this->db->query("SELECT name FROM projects WHERE id = " . (int)($request['project_id'] ?? 0))->fetch_assoc();
                        $updatedRequest['vendor_name']  = $vendor['name'] ?? '';
                        $updatedRequest['project_name'] = $project['name'] ?? '';

                        $this->mailService->sendVendorPaymentOnApproval(
                            $updatedRequest,
                            'approved',
                            $this->userId
                        );
                    }
                } catch (\Exception $e) {
                    error_log("Vendor Payment email (approval) failed: " . $e->getMessage());
                }

                setSuccess('Payment Request Approved with amount ৳' . number_format($approvedAmount, 2));
            } else {
                setError('Failed to approve or request already processed.');
            }
        }
        
        header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
        exit;
    }

    // =========================================================================
    // REJECT
    // =========================================================================

    public function reject($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }
        
        $request = $this->model->find($id);
        if (!$request) {
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        $isQC = ($request['status'] === 'Submitted');
        $isMgmt = ($request['status'] === 'QC Approved');

        if (($isQC || $isMgmt) && !$this->canApprove()) {
            setError('You are not authorized to reject as Management.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        if ($this->model->reject($id, $this->userId)) {
            $note = trim($_POST['approval_note'] ?? '');
            $stmt = $this->db->prepare("UPDATE vendor_payment_requests SET management_note = ? WHERE id = ?");
            $stmt->bind_param('si', $note, $id);
            $stmt->execute();

            // Sync approval_requests table
            $this->syncApprovalRequest($id, 'rejected');
            setSuccess('Payment Request Rejected.');
        } else {
            setError('Failed to reject request.');
        }
        header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
        exit;
    }

    // =========================================================================
    // ADD PAYMENT — Accounts (roles 1,3,6), creates con_financial_transaction
    // =========================================================================

    public function addPayment($id)
    {
        if (!$this->canProcessPayment()) {
            setError('You are not authorized to process payments.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        $request = $this->model->find($id);
        if (!$request || !in_array($request['status'], ['Approved', 'Partially_Paid'])) {
            setError('Payment request is not in a payable status.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
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
                    header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
                    exit;
                }
            }
        }

        if (empty($proofPaths)) {
            setError('Disbursement proof is required. Please upload at least one file.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            setError('Payment amount must be greater than zero.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        // Validate against remaining due
        $totalPaidSoFar = $this->txnModel->getTotalPaidForSource('vendor_payment', $id);
        $payableAmt     = (float)($request['approved_amount'] ?? $request['net_payable']);
        $remainingDue   = max(0, $payableAmt - $totalPaidSoFar);

        if ($amount > $remainingDue + 0.01) {
            setError("Payment amount (৳" . number_format($amount, 2) . ") exceeds remaining due (৳" . number_format($remainingDue, 2) . ").");
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        // Determine payment type
        $paymentType = ($amount < $remainingDue - 0.01) ? 'Partial' : 'Full';

        $data = [
            'ref_type'           => 'vendor_payment',
            'ref_id'             => (int)$id,
            'project_id'         => (int)($request['project_id'] ?? 0),
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
            // AUTO POSTING VENDOR PAYMENT DISBURSEMENT JOURNAL
            try {
                require_once __DIR__ . '/../../services/JournalIntegrationService.php';
                $journalService = new \Services\JournalIntegrationService($this->db);
                $journalService->generateDisbursementJournal($txnId, $this->userId);
            } catch (\Exception $e) {
                error_log("Failed to generate vendor payment disbursement journal: " . $e->getMessage());
            }

            setSuccess('Payment of ৳' . number_format($amount, 2) . ' recorded successfully! Transaction #' . $txnId);
        } else {
            setError('Failed to process payment.');
        }

        header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
        exit;
    }

    // =========================================================================
    // API: AJAX FETCH PENALTY
    // =========================================================================

    public function ajaxGetPenalty()
    {
        header('Content-Type: application/json');
        $vendorId = (int)($_GET['vendor_id'] ?? 0);
        $penalty  = $this->model->getPendingPenaltySum($vendorId);
        echo json_encode(['success' => true, 'penalty' => $penalty]);
        exit;
    }

    // =========================================================================
    // API: AJAX FETCH ADVANCE SUMMARY FOR A PO
    // =========================================================================

    public function ajaxGetAdvanceSummary()
    {
        header('Content-Type: application/json');

        $poId     = (int)($_GET['po_id'] ?? 0);
        $vendorId = (int)($_GET['vendor_id'] ?? 0);

        $advancePaid = $poId     ? $this->model->getTotalAdvancePaid($poId) : 0;
        $penalty     = $vendorId ? $this->model->getPendingPenaltySum($vendorId) : 0;

        echo json_encode([
            'success'      => true,
            'advance_paid' => $advancePaid,
            'penalty'      => $penalty,
        ]);
        exit;
    }

    // =========================================================================
    // API: AJAX — Net Receivable for a Vendor (project optional)
    // GET: ?vendor_id=N&project_id=N
    // Returns accepted_qty × unit_price MINUS returned qty — already paid
    // =========================================================================

    public function ajaxGetVendorReceivable()
    {
        header('Content-Type: application/json');

        $vendorId  = (int)($_GET['vendor_id']  ?? 0);
        $projectId = (int)($_GET['project_id'] ?? 0);

        if (!$vendorId) {
            echo json_encode(['success' => false, 'receivable' => 0, 'penalty' => 0]);
            exit;
        }

        // Net received amount (accepted_qty - returned_qty) × unit_price
        $sql = "
            SELECT
                COALESCE(SUM(
                    (mri.received_quantity - GREATEST(COALESCE(mri.return_quantity, 0), COALESCE(ret.return_qty, 0))) * poi.unit_price
                ), 0) AS net_receivable
            FROM material_receiving_items mri
            JOIN material_receiving mr    ON mr.id = mri.receiving_id
            JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
            JOIN purchase_orders po       ON po.id  = mr.purchase_order_id
            JOIN purchase_requests pr     ON pr.id  = po.request_id
            LEFT JOIN (
                SELECT material_item_id, SUM(return_qty) AS return_qty
                FROM material_return_items mret
                JOIN material_returns mrets ON mrets.id = mret.return_id
                WHERE mrets.status IN ('returned','sent')
                GROUP BY material_item_id
            ) ret ON ret.material_item_id = mri.id
            WHERE poi.selected_vendor_id = ?
              AND mri.status IN ('Accepted','Partially Accepted')
        ";

        $params = [$vendorId];
        $types  = 'i';

        if ($projectId) {
            $sql    .= " AND pr.project_id = ?";
            $params[] = $projectId;
            $types   .= 'i';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $netReceivable = (float)$stmt->get_result()->fetch_assoc()['net_receivable'];

        // Total already paid to this vendor (approved payments only)
        $paidSql = "
            SELECT COALESCE(SUM(vpr.net_payable_amount), 0) AS total_paid
            FROM vendor_payment_requests vpr
            JOIN purchase_orders po   ON po.id = vpr.purchase_order_id
            JOIN purchase_requests pr ON pr.id = po.request_id
            WHERE vpr.vendor_id = ?
              AND vpr.status IN ('Approved','Paid','Partially_Paid','Fully_Paid')
        ";
        $paidParams = [$vendorId];
        $paidTypes  = 'i';
        if ($projectId) {
            $paidSql    .= " AND pr.project_id = ?";
            $paidParams[] = $projectId;
            $paidTypes   .= 'i';
        }
        $paidStmt = $this->db->prepare($paidSql);
        $paidStmt->bind_param($paidTypes, ...$paidParams);
        $paidStmt->execute();
        $totalPaid = (float)$paidStmt->get_result()->fetch_assoc()['total_paid'];

        $dueAmount = max(0, $netReceivable - $totalPaid);
        $penalty   = $this->model->getPendingPenaltySum($vendorId);

        echo json_encode([
            'success'        => true,
            'net_receivable' => round($netReceivable, 2),
            'total_paid'     => round($totalPaid,     2),
            'due_amount'     => round($dueAmount,     2),
            'penalty'        => round($penalty,       2),
        ]);
        exit;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /** Get the linked approval_request for this vendor payment request */
    private function getApprovalRequest($requestId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM approval_requests
             WHERE source_table = 'vendor_payment_requests' AND source_id = ?
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Sync approval_requests status when approving/rejecting from this page */
    private function syncApprovalRequest($requestId, $newStatus)
    {
        $arStatus = ($newStatus === 'approved') ? 'approved' : 'rejected';

        $stmt = $this->db->prepare(
            "UPDATE approval_requests
             SET status = ?, approved_by = ?, approved_at = NOW()
             WHERE source_table = 'vendor_payment_requests' AND source_id = ? AND status = 'pending'"
        );
        $stmt->bind_param('sii', $arStatus, $this->userId, $requestId);
        $stmt->execute();
    }

    /** File upload handler */
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

        $newFileName = 'con_vendor_payment_' . time() . '_' . uniqid() . '.' . $fileExt;
        $uploadPath  = 'uploads/construction-payments/' . $newFileName;
        $fullPath    = __DIR__ . '/../../../public/' . $uploadPath;

        $dir = dirname($fullPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => true, 'file_path' => $uploadPath];
        }

        return ['success' => false, 'message' => "Failed to upload '{$file['name']}'."];
    }

    // =========================================================================
    // AJAX — POs for a vendor (+project), with financial summary
    // GET: ?vendor_id=N&project_id=N
    // Returns JSON array of PO rows with received/due/advance info
    // =========================================================================

    public function ajaxGetPOsByVendor()
    {
        header('Content-Type: application/json');

        $vendorId  = (int)($_GET['vendor_id']  ?? 0);
        $projectId = (int)($_GET['project_id'] ?? 0);

        if (!$vendorId) {
            echo json_encode([]);
            exit;
        }

        // ── Step 1: Find all PO IDs that have at least one item from this vendor ─────
        if ($projectId) {
            $stmt = $this->db->prepare("
                SELECT DISTINCT po.id
                FROM purchase_orders po
                JOIN purchase_requests pr ON pr.id = po.request_id
                JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
                WHERE poi.selected_vendor_id = ?
                  AND pr.project_id = ?
                ORDER BY po.id DESC
            ");
            $stmt->bind_param('ii', $vendorId, $projectId);
        } else {
            $stmt = $this->db->prepare("
                SELECT DISTINCT po.id
                FROM purchase_orders po
                JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
                WHERE poi.selected_vendor_id = ?
                ORDER BY po.id DESC
            ");
            $stmt->bind_param('i', $vendorId);
        }

        if (!$stmt->execute()) {
            echo json_encode(['error' => 'Query failed: ' . $stmt->error]);
            exit;
        }

        $poIds = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id');

        if (empty($poIds)) {
            echo json_encode([]);
            exit;
        }

        // ── Step 2: For each PO, fetch header + financial summary ─────────────────
        $result = [];
        foreach ($poIds as $poId) {
            // PO header info
            $hStmt = $this->db->prepare("
                SELECT po.id, po.status, po.created_at,
                       pr.project_id,
                       p.name AS project_name
                FROM purchase_orders po
                JOIN purchase_requests pr ON pr.id = po.request_id
                LEFT JOIN projects p ON p.id = pr.project_id
                WHERE po.id = ?
            ");
            $hStmt->bind_param('i', $poId);
            $hStmt->execute();
            $po = $hStmt->get_result()->fetch_assoc();
            if (!$po) continue;

            // Total order value (all items, not just this vendor)
            $vStmt = $this->db->prepare("
                SELECT COALESCE(SUM(quantity * unit_price), 0) AS total_order_value
                FROM purchase_order_items
                WHERE purchase_order_id = ?
            ");
            $vStmt->bind_param('i', $poId);
            $vStmt->execute();
            $totalValue = (float)$vStmt->get_result()->fetch_assoc()['total_order_value'];

            // Received amount (QC accepted items only)
            $rStmt = $this->db->prepare("
                SELECT COALESCE(SUM(mri.received_quantity * poi.unit_price), 0) AS received_amount
                FROM material_receiving_items mri
                JOIN material_receiving mr ON mr.id = mri.receiving_id
                JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
                WHERE mr.purchase_order_id = ?
                  AND mr.overall_status IN ('Accepted','Partially Accepted')
                  AND mri.status IN ('Accepted','Partially Accepted')
            ");
            $rStmt->bind_param('i', $poId);
            $rStmt->execute();
            $receivedAmount = (float)$rStmt->get_result()->fetch_assoc()['received_amount'];

            // Advance already paid for this PO
            $advancePaid = $this->model->getTotalAdvancePaid($poId);
            $due         = max(0, $receivedAmount - $advancePaid);

            $result[] = [
                'id'                => (int)$po['id'],
                'label'             => 'PO-' . $po['id'] . ' | ' . ($po['project_name'] ?? 'N/A') . ' | ' . $po['status'],
                'project_name'      => $po['project_name'] ?? '',
                'status'            => $po['status'] ?? '',
                'total_order_value' => round($totalValue,    2),
                'received_amount'   => round($receivedAmount, 2),
                'advance_paid'      => round($advancePaid,    2),
                'due_amount'        => round($due,            2),
            ];
        }

        echo json_encode($result);
        exit;
    }

    // =========================================================================
    // AJAX — POs by Project (project select করলেই load হবে, vendor optional)
    // GET: /con-ajax-po-by-project?project_id=N
    // Returns: [{id, label, project_name, status, total_order_value,
    //            received_amount, advance_paid, due_amount,
    //            payments:[{id, payment_type, invoice_amount, net_payable_amount, status, created_at}]}]
    // =========================================================================

    public function ajaxGetPOsByProject()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);

        if (!$projectId) {
            echo json_encode([]);
            exit;
        }

        // Get all POs for this project
        $stmt = $this->db->prepare("
            SELECT DISTINCT po.id
            FROM purchase_orders po
            JOIN purchase_requests pr ON pr.id = po.request_id
            WHERE pr.project_id = ?
            ORDER BY po.id DESC");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $poIds = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id');

        if (empty($poIds)) {
            echo json_encode([]);
            exit;
        }

        $months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $result = [];

        foreach ($poIds as $poId) {
            // PO header + vendor details
            $hStmt = $this->db->prepare("
                SELECT po.id, po.status, po.created_at,
                       pr.project_id, p.name AS project_name,
                       v.id AS vendor_id,
                       COALESCE(v.name, 'N/A') AS vendor_name,
                       COALESCE(v.address, '') AS vendor_address,
                       COALESCE(v.company_name, '') AS vendor_company
                FROM purchase_orders po
                JOIN purchase_requests pr ON pr.id = po.request_id
                LEFT JOIN projects p ON p.id = pr.project_id
                LEFT JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
                LEFT JOIN vendors v ON v.id = poi.selected_vendor_id
                WHERE po.id = ?
                LIMIT 1");
            $hStmt->bind_param('i', $poId);
            $hStmt->execute();
            $po = $hStmt->get_result()->fetch_assoc();
            if (!$po) continue;

            // Total PO value
            $vStmt = $this->db->prepare("
                SELECT COALESCE(SUM(quantity * unit_price), 0) AS total
                FROM purchase_order_items WHERE purchase_order_id = ?");
            $vStmt->bind_param('i', $poId);
            $vStmt->execute();
            $totalValue = (float)$vStmt->get_result()->fetch_assoc()['total'];

            // Received amount (accepted qty × unit_price) MINUS returned qty
            $rStmt = $this->db->prepare("
                SELECT
                    COALESCE(SUM(
                        (mri.received_quantity - GREATEST(COALESCE(mri.return_quantity, 0), COALESCE(ret.return_qty, 0))) * poi.unit_price
                    ), 0) AS received_amount
                FROM material_receiving_items mri
                JOIN material_receiving mr   ON mr.id = mri.receiving_id
                JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
                LEFT JOIN (
                    SELECT material_item_id, SUM(return_qty) AS return_qty
                    FROM material_return_items mret
                    JOIN material_returns mrets ON mrets.id = mret.return_id
                    WHERE mrets.status IN ('returned','sent')
                    GROUP BY material_item_id
                ) ret ON ret.material_item_id = mri.id
                WHERE mr.purchase_order_id = ?
                  AND mr.overall_status IN ('QC Approved','Accepted','Fully Received','Partially Received','Partially Accepted','Completed')
                  AND mri.status IN ('Accepted','Partially Accepted')
            ");
            $rStmt->bind_param('i', $poId);
            $rStmt->execute();
            $receivedAmount = (float)$rStmt->get_result()->fetch_assoc()['received_amount'];

            // Previous payments for this PO
            $pStmt = $this->db->prepare("
                SELECT vpr.id, vpr.payment_type, vpr.invoice_amount,
                       vpr.net_payable_amount, vpr.status, vpr.created_at,
                       v.name AS vendor_name, v.company_name
                FROM vendor_payment_requests vpr
                LEFT JOIN vendors v ON v.id = vpr.vendor_id
                WHERE vpr.purchase_order_id = ?
                ORDER BY vpr.id DESC");
            $pStmt->bind_param('i', $poId);
            $pStmt->execute();
            $payments = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Total paid (Approved/Paid statuses)
            $totalPaid = 0;
            foreach ($payments as $pay) {
                if (in_array($pay['status'], ['Approved','Paid','Partially_Paid','Fully_Paid'])) {
                    $totalPaid += (float)$pay['net_payable_amount'];
                }
            }
            $due = max(0, $totalValue - $totalPaid);

            // PO date formatted
            $poDate = !empty($po['created_at'])
                ? date('d M Y', strtotime($po['created_at'])) : '';

            $result[] = [
                'id'                => (int)$po['id'],
                'label'             => 'PO-' . $po['id'] . ' | ' . ($po['project_name'] ?? 'N/A')
                                       . ' | ৳' . number_format($totalValue, 0)
                                       . ' | ' . ($po['status'] ?? ''),
                'project_name'      => $po['project_name'] ?? '',
                'status'            => $po['status'] ?? '',
                'po_date'           => $poDate,
                'total_order_value' => round($totalValue,      2),
                'received_amount'   => round($receivedAmount,  2),
                'total_paid'        => round($totalPaid,       2),
                'due_amount'        => round($due,             2),
                'payments'          => $payments,
                'vendor_id'         => (int)($po['vendor_id'] ?? 0),
                'vendor_name'       => $po['vendor_name'] ?? 'N/A',
                'vendor_company'    => $po['vendor_company'] ?? '',
                'vendor_address'    => $po['vendor_address'] ?? '',
            ];
        }

        echo json_encode($result);
        exit;
    }
}
