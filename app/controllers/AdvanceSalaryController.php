<?php

class AdvanceSalaryController extends Controller
{
    protected $db;
    protected $model;
    protected $employeeModel;
    protected $approvalModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->model = new AdvanceSalary($this->db);
        $this->employeeModel = new Employee($this->db);
        $this->approvalModel = new ApprovalRequest($this->db);
    }

    public function index()
    {
        $advances = $this->model->getAll();
        $employees = $this->employeeModel->getAll();

        $this->view("hrm/advance-salary/index", [
            "current_route" => "advance-salary-lists",
            "page_title" => "Advance Salary Lists",
            "advances" => $advances,
            "employees" => $employees,
            "coaList" => getCOAExpenseList($this->db)
        ]);
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $employee_id = $_POST['employee_id'] ?? null;
        $amount = $_POST['amount'] ?? 0;
        $reason = $_POST['reason'] ?? '';
        $request_date = $_POST['request_date'] ?? date('Y-m-d');
        $created_by = $_SESSION['user']['id'] ?? 1;

        $deduction_start_month = $_POST['deduction_start_month'] ?? null;
        $deduction_end_month = $_POST['deduction_end_month'] ?? null;
        $deduction_months = $_POST['deduction_months'] ?? null;

        if (!$employee_id || $amount <= 0 || !$deduction_start_month || !$deduction_end_month || !$deduction_months) {
            $_SESSION['flash_error'] = "Employee, valid amount, and deduction months are required.";
            header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
            exit;
        }

        $data = [
            'employee_id' => $employee_id,
            'request_date' => $request_date,
            'amount' => $amount,
            'reason' => $reason,
            'deduction_start_month' => $deduction_start_month,
            'deduction_end_month' => $deduction_end_month,
            'deduction_months' => $deduction_months,
            'status' => 'pending_hr',
            'created_by' => $created_by,
            'account_id' => !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null
        ];

        $id = $this->model->create($data);

        if ($id) {
            // Create Approval Request for Advance Salary
            $this->approvalModel->create([
                'department' => 'HR',
                'request_type' => 'advance_salary',
                'title' => 'Advance Salary Request',
                'source_table' => 'advance_salaries',
                'source_id' => $id,
                'request_amount' => $amount,
                'requested_by' => $created_by,
                'approve_role' => 'hr_head',
                'meta_data' => json_encode([
                    'employee_id' => $employee_id,
                    'reason' => $reason,
                    'deduction_start_month' => $deduction_start_month,
                    'deduction_end_month' => $deduction_end_month,
                    'deduction_months' => $deduction_months,
                    'redirect_url' => '/advance-salary'
                ])
            ]);
            $_SESSION['flash_success'] = "Advance Salary requested successfully.";
        } else {
            $_SESSION['flash_error'] = "Failed to request Advance Salary.";
        }

        header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
        exit;
    }

    public function approve($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $stmt = $this->db->prepare("SELECT id, status, amount FROM advance_salaries WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $curr = $stmt->get_result()->fetch_assoc();

        $step = $_GET['step'] ?? '';

        if ($step === 'hr' && ($curr['status'] === 'pending_hr' || $curr['status'] === 'pending')) {
            if ($this->model->updateStatus($id, 'pending_accounts')) {
                $this->approvalModel->create([
                    'department' => 'Accounts',
                    'request_type' => 'advance_salary_payment',
                    'title' => 'Advance Salary Payment Approval',
                    'source_table' => 'advance_salaries',
                    'source_id' => $id,
                    'request_amount' => $curr['amount'],
                    'requested_by' => $_SESSION['user']['id'],
                    'approve_role' => 'management',
                    'meta_data' => json_encode(['redirect_url' => '/advance-salary'])
                ]);
                $_SESSION['flash_success'] = "Advance Salary approved by HR. Now pending by Management.";
            } else {
                $_SESSION['flash_error'] = "Failed to approve Advance Salary.";
            }
        } elseif ($step === 'mgmt' && $curr['status'] === 'pending_accounts') {
            $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
            $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
            $userId = (int)($_SESSION['user']['id'] ?? 0);
            $amount = (float)($curr['amount'] ?? 0);
            $isOver1Lac = ($amount > 100000);
            $isOpHeadUser = (in_array($userRole, [20, 32], true) || str_contains($roleName, 'operation head'));
            $isMgmtUser = in_array($userRole, [1, 3], true);

            // Fetch approval request record
            $arCheck = $this->db->query("SELECT id, op_head_status FROM approval_requests WHERE source_table = 'advance_salaries' AND source_id = " . (int)$id . " ORDER BY id DESC LIMIT 1");
            $arRow = $arCheck ? $arCheck->fetch_assoc() : null;
            $opHeadStatus = strtolower($arRow['op_head_status'] ?? 'pending');

            if ($isOver1Lac) {
                if ($isOpHeadUser && !$isMgmtUser) {
                    $arId = (int)($arRow['id'] ?? 0);
                    $escNote = $this->db->real_escape_string('Verified and approved by Admin & Operation Head');
                    if (!class_exists('ApprovalRequest')) {
                        require_once __DIR__ . '/../models/ApprovalRequest.php';
                    }
                    $approvalModel = new \ApprovalRequest($this->db);
                    if ($arId > 0) {
                        $approvalModel->approveOperationHead($arId, $userId, $escNote);
                    } else {
                        $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$userId}, op_head_at = NOW(), op_head_note = '{$escNote}', approve_role = 'management' WHERE source_table = 'advance_salaries' AND source_id = " . (int)$id);
                    }
                    $_SESSION['flash_success'] = "Advance Salary verified & approved by Admin & Operation Head (Role 20). Forwarded to Management for sign-off.";
                    if (!empty($_GET['redirect']) && $_GET['redirect'] === 'home') {
                        header("Location: " . $GLOBALS['base_url'] . "/hrm-dashboard");
                    } else {
                        header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
                    }
                    exit;
                }

                if ($isMgmtUser) {
                    if ($opHeadStatus !== 'approved') {
                        $_SESSION['flash_error'] = "Access Denied: Advance Salary exceeding ৳1,00,000 must be approved by Admin & Operation Head (Role 20) before Management can approve.";
                        if (!empty($_GET['redirect']) && $_GET['redirect'] === 'home') {
                            header("Location: " . $GLOBALS['base_url'] . "/hrm-dashboard");
                        } else {
                            header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
                        }
                        exit;
                    }
                }
            }

            if ($this->model->updateStatus($id, 'approved')) {
                // Also update the approval_requests table so the notification goes away
                $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', status='approved', approve_role='accounts_head', accounts_head_status='pending' WHERE source_table='advance_salaries' AND source_id={$id}");
                $_SESSION['flash_success'] = "Advance Salary approved by Management. Ready for Accounts Head sign-off & Payment.";
            } else {
                $_SESSION['flash_error'] = "Failed to approve by Accounts.";
            }
        } else {
            $_SESSION['flash_error'] = "Invalid state, incorrect permission step, or already approved.";
        }

        if (!empty($_GET['redirect']) && $_GET['redirect'] === 'home') {
            header("Location: " . $GLOBALS['base_url'] . "/hrm-dashboard");
        } else {
            header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
        }
        exit;
    }

    public function pay($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = (int)$id;
        if (!class_exists('ApprovalRequest')) {
            require_once __DIR__ . '/../models/ApprovalRequest.php';
        }
        $ahCheck = ApprovalRequest::getAccountsHeadApprovalInfo($this->db, 'advance_salaries', $id);
        if (!empty($ahCheck['has_request']) && empty($ahCheck['is_accounts_head_approved'])) {
            $_SESSION['flash_error'] = "Payment locked. Mandatory approval by Accounts Head is required before disbursement.";
            header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
            exit;
        }

        if ($this->model->updateStatus($id, 'paid')) {
            $_SESSION['success'] = "Advance Salary paid successfully.";
        } else {
            $_SESSION['error'] = "Failed to pay Advance Salary.";
        }

        header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
        exit;
    }
    public function payPost()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['flash_error'] = "Invalid request method.";
            header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
            exit;
        }

        $id = (int)($_POST['advance_salary_id'] ?? 0);
        $paid_amount = (float)($_POST['paid_amount'] ?? 0);

        if (!$id || $paid_amount <= 0) {
            $_SESSION['flash_error'] = "Invalid payment data.";
            header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
            exit;
        }

        if (!class_exists('ApprovalRequest')) {
            require_once __DIR__ . '/../models/ApprovalRequest.php';
        }
        $ahCheck = ApprovalRequest::getAccountsHeadApprovalInfo($this->db, 'advance_salaries', $id);
        if (!empty($ahCheck['has_request']) && empty($ahCheck['is_accounts_head_approved'])) {
            $_SESSION['flash_error'] = "Payment locked. Mandatory approval by Accounts Head is required before disbursement.";
            header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
            exit;
        }

        $advStmt = $this->db->prepare("SELECT * FROM advance_salaries WHERE id = ?");
        $advStmt->bind_param("i", $id);
        $advStmt->execute();
        $adv = $advStmt->get_result()->fetch_assoc();

        if (!$adv) {
            $_SESSION['flash_error'] = "Advance Salary record not found.";
            header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
            exit;
        }

        $maxAllowed = (float)($adv['amount'] ?? 0);
        if ($paid_amount > $maxAllowed + 0.01) {
            $_SESSION['flash_error'] = "Payment amount (৳" . number_format($paid_amount, 2) . ") cannot exceed the approved advance salary amount (৳" . number_format($maxAllowed, 2) . ").";
            header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
            exit;
        }

        $document_path = null;
        if (isset($_FILES['payment_document']) && $_FILES['payment_document']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'public/uploads/advance_salaries/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileInfo = pathinfo($_FILES['payment_document']['name']);
            $filename = time() . '_' . uniqid() . '.' . $fileInfo['extension'];
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['payment_document']['tmp_name'], $targetPath)) {
                $document_path = $targetPath;
            } else {
                $_SESSION['flash_error'] = "Failed to upload payment document.";
                header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
                exit;
            }
        }

        if ($this->model->payAdvance($id, $paid_amount, $document_path)) {
            // Mark the approval request as paid if it exists
            $this->db->query("UPDATE approval_requests SET status='approved' WHERE source_table='advance_salaries' AND source_id={$id}");
            $_SESSION['flash_success'] = "Advance Salary paid successfully.";
        } else {
            $_SESSION['flash_error'] = "Failed to pay Advance Salary.";
        }

        header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
        exit;
    }
}
