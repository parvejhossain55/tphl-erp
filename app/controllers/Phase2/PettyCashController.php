<?php
namespace Phase2;

use Controller;

class PettyCashController extends Controller {
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

        // Fetch query based on role
        // Management (role 3) and Admin (role 1) see everything
        // Accounts (role 6) see approved and paid ones
        // Others see their own created requests
        $where = "1=1";
        if (!in_array($roleId, [1, 2, 3, 6])) {
            $where = "pcr.requested_by = $userId";
        }

        $sql = "
            SELECT pcr.*, 
                   p.name as project_name,
                   d.name as department_name,
                   u_req.full_name as requester_name,
                   u_app.full_name as approved_by_name,
                   u_paid.full_name as paid_by_name
            FROM petty_cash_requests pcr
            LEFT JOIN projects p ON pcr.project_id = p.id
            LEFT JOIN departments d ON pcr.department_id = d.id
            LEFT JOIN users u_req ON pcr.requested_by = u_req.id
            LEFT JOIN users u_app ON pcr.approved_by = u_app.id
            LEFT JOIN users u_paid ON pcr.paid_by = u_paid.id
            WHERE $where
            ORDER BY pcr.id DESC
        ";

        $requests = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $departments = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/petty_cash/index', [
            'current_route' => 'petty-cash-requests',
            'page_title' => 'Petty Cash Requests',
            'requests' => $requests,
            'projects' => $projects,
            'departments' => $departments,
            'roleId' => $roleId
        ]);
    }

    public function create() {
        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $departments = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/petty_cash/create', [
            'current_route' => 'petty-cash-requests',
            'page_title' => 'Request Petty Cash',
            'projects' => $projects,
            'departments' => $departments
        ]);
    }

    public function store() {
        $department_id = (int)($_POST['department_id'] ?? 0);
        $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
        $expense_category = $_POST['expense_category'] ?? 'Miscellaneous';
        $amount = (float)($_POST['amount'] ?? 0);
        $purpose = $_POST['purpose'] ?? '';
        $requested_by = (int)$_SESSION['user']['id'];

        if (!$department_id || $amount <= 0 || empty($purpose)) {
            setError('Please select department, provide a valid amount and specify the purpose.');
            header("Location: " . $GLOBALS['base_url'] . "/petty-cash-requests");
            exit;
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
            INSERT INTO petty_cash_requests 
            (department_id, project_id, requested_by, expense_date, expense_category, amount, purpose, receipt_path, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("iiissdss", $department_id, $project_id, $requested_by, $expense_date, $expense_category, $amount, $purpose, $receipt_path);

        if ($stmt->execute()) {
            setSuccess("Petty Cash Request submitted successfully.");
        } else {
            setError("Failed to submit request: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/petty-cash-requests");
        exit;
    }

    public function approve() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3])) {
            setError('Only Management can approve petty cash requests.');
            header("Location: " . $GLOBALS['base_url'] . "/petty-cash-requests");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'pending'; // 'approved' or 'rejected'
        $note = $_POST['approval_note'] ?? '';
        $approved_by = (int)$_SESSION['user']['id'];

        if (!in_array($status, ['approved', 'rejected'])) {
            setError('Invalid status.');
            header("Location: " . $GLOBALS['base_url'] . "/petty-cash-requests");
            exit;
        }

        $stmt = $this->db->prepare("
            UPDATE petty_cash_requests 
            SET status = ?, approved_by = ?, approval_note = ?, approved_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->bind_param("sisi", $status, $approved_by, $note, $id);

        if ($stmt->execute()) {
            setSuccess("Petty Cash request updated to " . $status);
        } else {
            setError("Failed to update status: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/petty-cash-requests");
        exit;
    }

    public function pay() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 6])) {
            setError('Only Accounts can record payments.');
            header("Location: " . $GLOBALS['base_url'] . "/petty-cash-requests");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'paid'; // 'paid' or 'rejected'
        $payment_method = $_POST['payment_method'] ?? 'Cash';
        $paid_by = (int)$_SESSION['user']['id'];

        if (!in_array($status, ['paid', 'rejected'])) {
            setError('Invalid status.');
            header("Location: " . $GLOBALS['base_url'] . "/petty-cash-requests");
            exit;
        }

        // Verify approved by mgmt
        $req = $this->db->query("SELECT status FROM petty_cash_requests WHERE id = $id")->fetch_assoc();
        if (!$req || $req['status'] !== 'approved') {
            setError('Request must be approved by Management before Accounts can pay.');
            header("Location: " . $GLOBALS['base_url'] . "/petty-cash-requests");
            exit;
        }

        $stmt = $this->db->prepare("
            UPDATE petty_cash_requests 
            SET status = ?, paid_by = ?, payment_method = ?, paid_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->bind_param("sisi", $status, $paid_by, $payment_method, $id);

        if ($stmt->execute()) {
            setSuccess("Petty Cash payment recorded successfully.");
        } else {
            setError("Failed to record payment: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/petty-cash-requests");
        exit;
    }

    public function delete() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3])) {
            setError('Unauthorized.');
            header("Location: " . $GLOBALS['base_url'] . "/petty-cash-requests");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $this->db->query("DELETE FROM petty_cash_requests WHERE id = $id");
            setSuccess("Request deleted.");
        } else {
            setError("Invalid record ID.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/petty-cash-requests");
        exit;
    }
}
