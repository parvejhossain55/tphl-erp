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
            "employees" => $employees
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

        if (!$employee_id || $amount <= 0) {
            $_SESSION['error'] = "Employee and valid amount are required.";
            header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
            exit;
        }

        $data = [
            'employee_id' => $employee_id,
            'request_date' => $request_date,
            'amount' => $amount,
            'reason' => $reason,
            'status' => 'pending_hr',
            'created_by' => $created_by
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
                    'redirect_url' => '/advance-salary'
                ])
            ]);
            $_SESSION['success'] = "Advance Salary requested successfully.";
        } else {
            $_SESSION['error'] = "Failed to request Advance Salary.";
        }

        header("Location: " . $GLOBALS['base_url'] . "/advance-salary");
        exit;
    }

    public function approve($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $stmt = $this->db->prepare("SELECT status FROM advance_salaries WHERE id = ?");
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
            if ($this->model->updateStatus($id, 'approved')) {
                // Also update the approval_requests table so the notification goes away
                $this->db->query("UPDATE approval_requests SET status='approved' WHERE source_table='advance_salaries' AND source_id={$id} AND status='pending'");
                $_SESSION['flash_success'] = "Advance Salary approved by Management. Ready for Payment.";
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

        $id = $_POST['advance_salary_id'] ?? null;
        $paid_amount = $_POST['paid_amount'] ?? 0;

        if (!$id || $paid_amount <= 0) {
            $_SESSION['flash_error'] = "Invalid payment data.";
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
