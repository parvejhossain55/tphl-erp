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

        $sql = "
            SELECT fr.*, 
                   p.name as project_name,
                   d.name as department_name,
                   u_req.full_name as requester_name,
                   u_paid.full_name as paid_by_name
            FROM fund_requisitions fr
            LEFT JOIN projects p ON fr.project_id = p.id
            LEFT JOIN departments d ON fr.department_id = d.id
            LEFT JOIN users u_req ON fr.requested_by = u_req.id
            LEFT JOIN users u_paid ON fr.paid_by = u_paid.id
            WHERE $where
            ORDER BY fr.id DESC
        ";

        $requests = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $departments = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/fund_requisition/index', [
            'current_route' => 'fund-requisitions',
            'page_title' => 'Fund Requisitions',
            'requests' => $requests,
            'projects' => $projects,
            'departments' => $departments,
            'roleId' => $roleId
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

    public function pay() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3, 6])) {
            setError('Only Accounts or Admins can approve or pay requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'paid'; // 'paid' or 'rejected'
        $payment_method = $_POST['payment_method'] ?? 'Cash';
        $approval_note = $_POST['approval_note'] ?? '';
        $paid_by = (int)$_SESSION['user']['id'];

        if (!in_array($status, ['paid', 'rejected'])) {
            setError('Invalid status.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions 
            SET status = ?, paid_by = ?, payment_method = ?, approval_note = ?, paid_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->bind_param("sissi", $status, $paid_by, $payment_method, $approval_note, $id);

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
}
