<?php
/**
 * BankRegistryController — CRUD for the `banks` table.
 * Accessible from the Accounts module as a dedicated Bank Management page.
 */
class BankRegistryController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    /** List all banks */
    public function index()
    {
        $result = $this->db->query("SELECT * FROM banks ORDER BY bank_name, branch_name");
        $banks  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('accounts/bank-registry', [
            'current_route' => 'bank-registry',
            'page_title'    => 'Bank Registry Management',
            'banks'         => $banks,
        ]);
    }

    /** Create a new bank record (AJAX or POST) */
    public function store()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']); exit;
        }

        $bankName      = trim($_POST['bank_name']     ?? '');
        $branchName    = trim($_POST['branch_name']   ?? '');
        $routingNumber = trim($_POST['routing_number'] ?? '');
        $createdBy     = $_SESSION['user']['id'] ?? 0;

        if (!$bankName || !$branchName) {
            echo json_encode(['success' => false, 'message' => 'Bank Name and Branch Name are required']); exit;
        }

        $stmt = $this->db->prepare("
            INSERT INTO banks (bank_name, branch_name, routing_number, status, created_by)
            VALUES (?, ?, ?, 'active', ?)
        ");
        $stmt->bind_param("sssi", $bankName, $branchName, $routingNumber, $createdBy);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Bank added successfully',
                'id'      => $this->db->insert_id,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $stmt->error]);
        }
        exit;
    }

    /** Update an existing bank (AJAX POST) */
    public function update()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']); exit;
        }

        $id            = (int)($_POST['id']            ?? 0);
        $bankName      = trim($_POST['bank_name']      ?? '');
        $branchName    = trim($_POST['branch_name']    ?? '');
        $routingNumber = trim($_POST['routing_number'] ?? '');

        if (!$id || !$bankName || !$branchName) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']); exit;
        }

        $stmt = $this->db->prepare("
            UPDATE banks SET bank_name = ?, branch_name = ?, routing_number = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $bankName, $branchName, $routingNumber, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Bank updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
        }
        exit;
    }

    /** Soft-delete (or hard-delete) a bank */
    public function delete()
    {
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']); exit;
        }

        $stmt = $this->db->prepare("DELETE FROM banks WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Bank deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $stmt->error]);
        }
        exit;
    }

    /** Toggle active/inactive status */
    public function toggleStatus()
    {
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']); exit;
        }

        $stmt = $this->db->prepare("
            UPDATE banks
            SET status = IF(status = 'active', 'inactive', 'active')
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // Fetch new status
            $row = $this->db->query("SELECT status FROM banks WHERE id = {$id}")->fetch_assoc();
            echo json_encode(['success' => true, 'new_status' => $row['status']]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        exit;
    }
}
