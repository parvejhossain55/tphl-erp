<?php
namespace Phase2;

use Controller;

class FundRequisitionController extends Controller {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Fetch queries based on role
        // Management (role 3), Admin (role 1), Accounts (role 6) see everything
        $where = "1=1";
        if (!in_array($roleId, [1, 3, 6])) {
            $where = "fr.requested_by = $userId";
        }

        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $params = [];
        $types = "";

        if (!empty($startDate)) {
            $where .= " AND DATE(fr.created_at) >= ?";
            $params[] = $startDate;
            $types .= "s";
        }
        if (!empty($endDate)) {
            $where .= " AND DATE(fr.created_at) <= ?";
            $params[] = $endDate;
            $types .= "s";
        }

        // Limit to 10 by default if no date filter is applied
        $limitClause = "";
        $isDefaultLimit = false;
        if (empty($startDate) && empty($endDate)) {
            $limitClause = " LIMIT 10";
            $isDefaultLimit = true;
        }

        $sql = "
            SELECT fr.*, 
                   p.name as project_name,
                   d.name as department_name,
                   u_req.full_name as requester_name,
                   u_app.full_name as approved_by_name,
                   u_paid.full_name as paid_by_name
            FROM fund_requisitions fr
            LEFT JOIN projects p ON fr.project_id = p.id
            LEFT JOIN departments d ON fr.department_id = d.id
            LEFT JOIN users u_req ON fr.requested_by = u_req.id
            LEFT JOIN users u_app ON fr.approved_by = u_app.id
            LEFT JOIN users u_paid ON fr.paid_by = u_paid.id
            WHERE $where
            ORDER BY fr.id DESC
            $limitClause
        ";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $requests = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
        }

        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $departments = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/fund_requisition/index', [
            'current_route' => 'fund-requisitions',
            'page_title' => 'Fund Requisitions',
            'requests' => $requests,
            'projects' => $projects,
            'departments' => $departments,
            'roleId' => $roleId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'isDefaultLimit' => $isDefaultLimit
        ]);
    }

    public function store() {
        $department_id = (int)($_POST['department_id'] ?? 0);
        $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        $requisition_type = $_POST['requisition_type'] ?? 'general';
        $category = $_POST['category'] ?? 'Other Expense';
        $amount = (float)($_POST['amount'] ?? 0);
        $purpose = $_POST['purpose'] ?? '';
        $requested_by = (int)$_SESSION['user']['id'];

        if (!$department_id || $amount <= 0 || empty($purpose)) {
            setError('Please select department, provide a valid amount and specify the purpose.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $designationId = (int)($_SESSION['user']['designation_id'] ?? 0);

        // Validation for Petty Cash: Only Site Engineer (role 12 or designation 38)
        if ($requisition_type === 'petty_cash') {
            if ($roleId !== 12 && $designationId !== 38) {
                setError('Petty Cash requisitions can only be requested by Site Engineers.');
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
                exit;
            }
            if ($amount > 10000) {
                setError('Maximum Petty Cash request limit is BDT 10,000.');
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
                exit;
            }
            $category = 'Petty Cash';
        }

        // Receipt Upload
        $receipt_path = null;
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/receipts/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
            $filename = 'receipt_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $upload_dir . $filename)) {
                $receipt_path = 'uploads/receipts/' . $filename;
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO fund_requisitions 
            (department_id, project_id, requested_by, requisition_type, category, amount, purpose, receipt_path, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("iiissdss", $department_id, $project_id, $requested_by, $requisition_type, $category, $amount, $purpose, $receipt_path);

        if ($stmt->execute()) {
            setSuccess("Fund Requisition submitted successfully.");
        } else {
            setError("Failed to submit request: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    public function approve() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3])) {
            setError('Only Management or Admins can approve requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'approved'; // 'approved' or 'rejected'
        $approval_note = $_POST['approval_note'] ?? '';
        $approved_by = (int)$_SESSION['user']['id'];

        if (!in_array($status, ['approved', 'rejected'])) {
            setError('Invalid status.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions 
            SET status = ?, approved_by = ?, approval_note = ?, approved_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->bind_param("sisi", $status, $approved_by, $approval_note, $id);

        if ($stmt->execute()) {
            setSuccess("Requisition status updated to: " . $status);
        } else {
            setError("Failed to update requisition: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    public function pay() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 6])) {
            setError('Only Accounts or Admins can pay requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'paid'; // 'paid' or 'rejected'
        $payment_method = $_POST['payment_method'] ?? 'Cash';
        $accounts_note = $_POST['accounts_note'] ?? '';
        $paid_by = (int)$_SESSION['user']['id'];

        if (!in_array($status, ['paid', 'rejected'])) {
            setError('Invalid status.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Handle payment attachment upload
        $payment_attachment_path = null;
        if (isset($_FILES['payment_attachment']) && $_FILES['payment_attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/payments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['payment_attachment']['name'], PATHINFO_EXTENSION);
            $filename = 'payment_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            if (move_uploaded_file($_FILES['payment_attachment']['tmp_name'], $upload_dir . $filename)) {
                $payment_attachment_path = 'uploads/payments/' . $filename;
            }
        }

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions 
            SET status = ?, paid_by = ?, payment_method = ?, accounts_note = ?, payment_attachment_path = ?, paid_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->bind_param("sisssi", $status, $paid_by, $payment_method, $accounts_note, $payment_attachment_path, $id);

        if ($stmt->execute()) {
            setSuccess("Requisition payment status updated to: " . $status);
        } else {
            setError("Failed to update payment status: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    public function delete() {
        $id = (int)($_POST['id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        if (!$id) {
            setError("Invalid record ID.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Fetch request to check owner/status
        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $id")->fetch_assoc();
        if (!$req) {
            setError("Request not found.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Only owner (if pending) or Admin/Super Admin can delete
        if ($req['requested_by'] === $userId && $req['status'] === 'pending') {
            $allow = true;
        } elseif (in_array($roleId, [1, 3])) {
            $allow = true;
        } else {
            $allow = false;
        }

        if ($allow) {
            $this->db->query("DELETE FROM fund_requisitions WHERE id = $id");
            setSuccess("Request deleted successfully.");
        } else {
            setError("Unauthorized to delete this request.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    public function show($id) {
        $id = (int)$id;
        
        // Fetch requisition details
        $sql = "
            SELECT fr.*, 
                   p.name as project_name,
                   d.name as department_name,
                   u_req.full_name as requester_name,
                   u_app.full_name as approved_by_name,
                   u_paid.full_name as paid_by_name
            FROM fund_requisitions fr
            LEFT JOIN projects p ON fr.project_id = p.id
            LEFT JOIN departments d ON fr.department_id = d.id
            LEFT JOIN users u_req ON fr.requested_by = u_req.id
            LEFT JOIN users u_app ON fr.approved_by = u_app.id
            LEFT JOIN users u_paid ON fr.paid_by = u_paid.id
            WHERE fr.id = $id
        ";
        $req = $this->db->query($sql)->fetch_assoc();
        if (!$req) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Requisition not found']);
            exit;
        }

        // Fetch total spent globally (without date filter) for correct balance calculation
        $sumSql = "SELECT SUM(amount) as total_spent FROM fund_requisition_expenses WHERE fund_requisition_id = $id";
        $sumRes = $this->db->query($sumSql)->fetch_assoc();
        $totalSpentGlobal = (float)($sumRes['total_spent'] ?? 0);

        // Fetch expenses (with date range filter if specified, or limit to 10 latest by default)
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $expenseWhere = "fre.fund_requisition_id = $id";
        $params = [];
        $types = "";

        if (!empty($startDate)) {
            $expenseWhere .= " AND DATE(fre.created_at) >= ?";
            $params[] = $startDate;
            $types .= "s";
        }
        if (!empty($endDate)) {
            $expenseWhere .= " AND DATE(fre.created_at) <= ?";
            $params[] = $endDate;
            $types .= "s";
        }

        $limitClause = "";
        $isDefaultLimit = false;
        if (empty($startDate) && empty($endDate)) {
            $limitClause = " LIMIT 10";
            $isDefaultLimit = true;
        }

        $expenseSql = "
            SELECT fre.*, 
                   p.name as project_name,
                   u.full_name as creator_name
            FROM fund_requisition_expenses fre
            LEFT JOIN projects p ON fre.project_id = p.id
            LEFT JOIN users u ON fre.created_by = u.id
            WHERE $expenseWhere
            ORDER BY fre.id DESC
            $limitClause
        ";

        if (!empty($params)) {
            $stmt = $this->db->prepare($expenseSql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $expenses = $this->db->query($expenseSql)->fetch_all(MYSQLI_ASSOC);
        }

        // Fetch projects for the add expense form
        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'requisition' => $req,
            'expenses' => $expenses,
            'projects' => $projects,
            'session_user_id' => (int)$_SESSION['user']['id'],
            'session_role_id' => (int)$_SESSION['user']['role_id'],
            'is_default_limit' => $isDefaultLimit,
            'total_spent_global' => $totalSpentGlobal
        ]);
        exit;
    }

    public function storeExpense() {
        $fund_requisition_id = (int)($_POST['fund_requisition_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $purpose = $_POST['purpose'] ?? '';
        $project_type = $_POST['project_type'] ?? 'by_project'; // 'by_project' or 'other_project'
        
        $project_id = null;
        $other_project_name = null;
        
        if ($project_type === 'by_project') {
            $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        } else {
            $other_project_name = $_POST['other_project_name'] ?? null;
        }

        $userId = (int)$_SESSION['user']['id'];

        // Validate that the requisition exists and is paid
        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $fund_requisition_id")->fetch_assoc();
        if (!$req || $req['status'] !== 'paid') {
            setError('Expenses can only be logged for paid requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        if ($amount <= 0 || empty($purpose)) {
            setError('Please specify a valid amount and purpose.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Limit checking
        $sumSql = "SELECT SUM(amount) as total_spent FROM fund_requisition_expenses WHERE fund_requisition_id = $fund_requisition_id";
        $sumRes = $this->db->query($sumSql)->fetch_assoc();
        $total_spent = (float)($sumRes['total_spent'] ?? 0);
        $remaining = $req['amount'] - $total_spent;
        if ($amount > $remaining) {
            setError('Expense amount (BDT ' . number_format($amount, 2) . ') exceeds the remaining requisition balance (BDT ' . number_format($remaining, 2) . ').');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Handle attachment upload
        $attachment_path = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/expenses/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        error_log("Failed to create directory: " . $upload_dir);
                        setError("Failed to create upload directory.");
                    }
                }
                if (is_dir($upload_dir)) {
                    $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
                    $filename = 'expense_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $filename)) {
                        $attachment_path = 'uploads/expenses/' . $filename;
                    } else {
                        $err = error_get_last();
                        error_log("Failed to move uploaded expense attachment. Error: " . ($err['message'] ?? 'Unknown'));
                        setError("Failed to save uploaded attachment.");
                    }
                }
            } else {
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
                ];
                $errorCode = $_FILES['attachment']['error'];
                $errorMessage = $upload_errors[$errorCode] ?? 'Unknown upload error.';
                error_log("Expense upload error: " . $errorMessage);
                setError("Attachment upload failed: " . $errorMessage);
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO fund_requisition_expenses 
            (fund_requisition_id, project_id, other_project_name, amount, purpose, attachment_path, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisdssi", $fund_requisition_id, $project_id, $other_project_name, $amount, $purpose, $attachment_path, $userId);

        if ($stmt->execute()) {
            setSuccess("Expense logged successfully.");
        } else {
            setError("Failed to log expense: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    public function deleteExpense() {
        $id = (int)($_POST['id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        if (!$id) {
            setError("Invalid expense ID.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $expense = $this->db->query("SELECT * FROM fund_requisition_expenses WHERE id = $id")->fetch_assoc();
        if (!$expense) {
            setError("Expense not found.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Only owner (created_by) or Admin/Super Admin can delete
        if ($expense['created_by'] === $userId || in_array($roleId, [1, 3])) {
            // Delete file if exists
            if (!empty($expense['attachment_path'])) {
                $file = dirname(dirname(dirname(__DIR__))) . '/public/' . $expense['attachment_path'];
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            $this->db->query("DELETE FROM fund_requisition_expenses WHERE id = $id");
            setSuccess("Expense deleted successfully.");
        } else {
            setError("Unauthorized to delete this expense.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }
}
