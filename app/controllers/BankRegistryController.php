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

    /** List all banks — redirected to Company Bank Accounts */
    public function index()
    {
        header("Location: {$GLOBALS['base_url']}/company-bank-accounts");
        exit;
    }

    /** Create a new bank record (AJAX or POST) */
    public function store()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']); exit;
        }

        $bankName      = trim($_POST['bank_name']      ?? '');
        $accountName   = trim($_POST['branch_name']    ?? ($_POST['account_name'] ?? ''));
        $accountNumber = trim($_POST['account_number'] ?? '');
        $createdBy     = $_SESSION['user']['id']       ?? 0;

        if (!$bankName || !$accountName) {
            echo json_encode(['success' => false, 'message' => 'Bank Name and Account/Branch Name are required']); exit;
        }

        $stmt = $this->db->prepare("
            INSERT INTO company_bank_accounts (bank_name, account_name, account_number, status, created_by)
            VALUES (?, ?, ?, 'active', ?)
        ");
        $stmt->bind_param("sssi", $bankName, $accountName, $accountNumber, $createdBy);

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
        $accountName   = trim($_POST['branch_name']    ?? ($_POST['account_name'] ?? ''));
        $accountNumber = trim($_POST['account_number'] ?? '');

        if (!$id || !$bankName || !$accountName) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']); exit;
        }

        $stmt = $this->db->prepare("
            UPDATE company_bank_accounts SET bank_name = ?, account_name = ?, account_number = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $bankName, $accountName, $accountNumber, $id);

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

        $stmt = $this->db->prepare("DELETE FROM company_bank_accounts WHERE id = ?");
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
            UPDATE company_bank_accounts
            SET status = IF(status = 'active', 'inactive', 'active')
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // Fetch new status
            $row = $this->db->query("SELECT status FROM company_bank_accounts WHERE id = {$id}")->fetch_assoc();
            echo json_encode(['success' => true, 'new_status' => $row['status']]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        exit;
    }
}
