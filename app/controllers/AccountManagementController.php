<?php
class AccountManagementController extends Controller
{
    protected $db;
    protected $managementModel;
    protected $roleModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->managementModel = new AccountManagement($this->db);
        $this->roleModel = new ManagementRole($this->db);
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    // Dashboard / list
    public function index()
    {
        $transactions = $this->managementModel->getAllTransactions();
        $statistics = $this->managementModel->getStatistics();

        $this->view("accounts/cashIn/managements", [
            "current_route" => "management-cash-in",
            "page_title" => "Management Cash In",
            "transactions" => $transactions,
            "statistics" => $statistics
        ]);
    }

    // Show Add Form
    public function showAddForm()
    {
        $roles = $this->roleModel->getAllActiveRoles();
        
        $this->view("accounts/cashIn/managements-cash-in-form", [
            "current_route" => "add-management-cash-in",
            "page_title" => "Add Management Transaction",
            "roles" => $roles
        ]);
    }

    // Store new transaction
    public function storeTransaction()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $created_by = $this->getCurrentUserId();
            
            // Get role and person name
            $role_id = $_POST['role_id'] ?? null;
            $person_name = '';
            $role_name = '';
            
            if ($role_id) {
                $roleData = $this->roleModel->getRoleById($role_id);
                if ($roleData) {
                    $person_name = $roleData['person_name'];
                    $role_name = $roleData['role_name'];
                }
            }
            
            $data = [
                'role_id' => $role_id,
                'role_name' => $role_name,
                'person_name' => $person_name,
                'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d'),
                'source' => trim($_POST['source'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'amount' => $_POST['amount'] ?? 0,
                'payment_method' => $_POST['payment_method'] ?? 'cash',
                'payment_status' => $_POST['payment_status'] ?? 'paid',
                'transaction_ref' => trim($_POST['transaction_ref'] ?? ''),
                'bank_name' => trim($_POST['bank_name'] ?? ''),
                'bank_account_no' => trim($_POST['bank_account_no'] ?? ''),
                'cheque_no' => trim($_POST['cheque_no'] ?? ''),
                'online_type' => trim($_POST['online_type'] ?? ''),
                'created_by' => $created_by
            ];

            // Basic validation
            if (empty($data['role_id']) || empty($data['source']) || $data['amount'] <= 0) {
                $_SESSION['old_form_data'] = $_POST;
                setError("Please fill all required fields correctly.");
                header("Location: {$GLOBALS['base_url']}/add-management-cash-in");
                exit;
            }

            // Validate numeric amount
            if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
                $_SESSION['old_form_data'] = $_POST;
                setError("Please enter a valid amount.");
                header("Location: {$GLOBALS['base_url']}/add-management-cash-in");
                exit;
            }

            // Additional validation based on payment method
            if ($data['payment_method'] === 'bank' && empty($data['bank_name'])) {
                $_SESSION['old_form_data'] = $_POST;
                setError("Bank name is required for bank payments.");
                header("Location: {$GLOBALS['base_url']}/add-management-cash-in");
                exit;
            }

            if ($data['payment_method'] === 'cheque' && empty($data['cheque_no'])) {
                $_SESSION['old_form_data'] = $_POST;
                setError("Cheque number is required for cheque payments.");
                header("Location: {$GLOBALS['base_url']}/add-management-cash-in");
                exit;
            }

            try {
                $result = $this->managementModel->addTransaction($data);

                if ($result['success']) {
                    unset($_SESSION['old_form_data']);
                    setSuccess($result['message']);
                    header("Location: {$GLOBALS['base_url']}/management-cash-in");
                } else {
                    $_SESSION['old_form_data'] = $_POST;
                    setError($result['message']);
                    header("Location: {$GLOBALS['base_url']}/add-management-cash-in");
                }
            } catch (Exception $e) {
                $_SESSION['old_form_data'] = $_POST;
                setError("An error occurred: " . $e->getMessage());
                header("Location: {$GLOBALS['base_url']}/add-management-cash-in");
            }
            exit;
        }
        
        header("Location: {$GLOBALS['base_url']}/add-management-cash-in");
        exit;
    }

    // Show Edit Form
    public function showEditForm($transactionId = null)
    {
        $transactionId = $transactionId ?? $_GET['id'] ?? null;
        if (!$transactionId) {
            setError("Transaction ID missing.");
            header("Location: {$GLOBALS['base_url']}/management-cash-in");
            exit;
        }

        $transaction = $this->managementModel->getTransactionById($transactionId);
        if (!$transaction) {
            setError("Transaction not found.");
            header("Location: {$GLOBALS['base_url']}/management-cash-in");
            exit;
        }

        $roles = $this->roleModel->getAllActiveRoles();

        $this->view("accounts/cashIn/management-edit-form", [
            "current_route" => "edit-management-cash-in",
            "page_title" => "Edit Management Transaction",
            "transaction" => $transaction,
            "roles" => $roles
        ]);
    }

    // Update transaction
    public function updateTransaction()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $transactionId = $_POST['transaction_id'] ?? null;
            if (!$transactionId) {
                setError("Invalid transaction ID");
                header("Location: {$GLOBALS['base_url']}/management-cash-in");
                exit;
            }

            // Get role and person name
            $role_id = $_POST['role_id'] ?? null;
            $person_name = '';
            $role_name = '';
            
            if ($role_id) {
                $roleData = $this->roleModel->getRoleById($role_id);
                if ($roleData) {
                    $person_name = $roleData['person_name'];
                    $role_name = $roleData['role_name'];
                }
            }

            $data = [
                'transaction_id' => $transactionId,
                'role_id' => $role_id,
                'role_name' => $role_name,
                'person_name' => $person_name,
                'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d'),
                'source' => trim($_POST['source'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'amount' => $_POST['amount'] ?? 0,
                'payment_method' => $_POST['payment_method'] ?? 'cash',
                'payment_status' => $_POST['payment_status'] ?? 'paid',
                'transaction_ref' => trim($_POST['transaction_ref'] ?? ''),
                'bank_name' => trim($_POST['bank_name'] ?? ''),
                'bank_account_no' => trim($_POST['bank_account_no'] ?? ''),
                'cheque_no' => trim($_POST['cheque_no'] ?? ''),
                'online_type' => trim($_POST['online_type'] ?? '')
            ];

            if (empty($data['role_id']) || empty($data['source']) || $data['amount'] <= 0) {
                setError("Please fill all required fields correctly.");
                header("Location: {$GLOBALS['base_url']}/edit-management-cash-in?id={$transactionId}");
                exit;
            }

            if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
                setError("Please enter a valid amount.");
                header("Location: {$GLOBALS['base_url']}/edit-management-cash-in?id={$transactionId}");
                exit;
            }

            if ($data['payment_method'] === 'bank' && empty($data['bank_name'])) {
                setError("Bank name is required for bank payments.");
                header("Location: {$GLOBALS['base_url']}/edit-management-cash-in?id={$transactionId}");
                exit;
            }

            if ($data['payment_method'] === 'cheque' && empty($data['cheque_no'])) {
                setError("Cheque number is required for cheque payments.");
                header("Location: {$GLOBALS['base_url']}/edit-management-cash-in?id={$transactionId}");
                exit;
            }

            try {
                $result = $this->managementModel->updateTransaction($data);

                if ($result['success']) {
                    setSuccess($result['message']);
                    header("Location: {$GLOBALS['base_url']}/management-cash-in");
                } else {
                    setError($result['message']);
                    header("Location: {$GLOBALS['base_url']}/edit-management-cash-in?id={$transactionId}");
                }
            } catch (Exception $e) {
                setError("An error occurred: " . $e->getMessage());
                header("Location: {$GLOBALS['base_url']}/edit-management-cash-in?id={$transactionId}");
            }
            exit;
        }
        
        header("Location: {$GLOBALS['base_url']}/management-cash-in");
        exit;
    }

    // Delete transaction
    public function deleteTransaction()
    {
        $transactionId = $_GET['id'] ?? null;
        if (!$transactionId) {
            setError("Invalid transaction ID");
            header("Location: {$GLOBALS['base_url']}/management-cash-in");
            exit;
        }

        try {
            $result = $this->managementModel->deleteTransaction($transactionId);
            if ($result['success']) setSuccess($result['message']);
            else setError($result['message']);
        } catch (Exception $e) {
            setError("An error occurred: " . $e->getMessage());
        }
        
        header("Location: {$GLOBALS['base_url']}/management-cash-in");
        exit;
    }

    // AJAX: Get transaction details
    public function getTransactionDetails($transactionId = null)
    {
        $transactionId = $transactionId ?? $_GET['id'] ?? null;
        if (!$transactionId) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Transaction ID required']);
            exit;
        }
        
        header('Content-Type: application/json');
        $transaction = $this->managementModel->getTransactionById($transactionId);
        if (!$transaction) {
            echo json_encode(['error' => 'Transaction not found']);
        } else {
            echo json_encode($transaction);
        }
        exit;
    }

    // AJAX: Get role details


    // Management Roles CRUD
    public function manageRoles()
    {
        $roles = $this->roleModel->getAllRoles();
        
        $this->view("accounts/cashIn/management-roles", [
            "current_route" => "management-roles",
            "page_title" => "Manage Roles",
            "roles" => $roles
        ]);
    }

// AJAX: Get role details
public function getRoleDetails()
{
    header('Content-Type: application/json');
    
    $roleId = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$roleId) {
        echo json_encode(['error' => 'Role ID required']);
        exit;
    }
    
    try {
        $role = $this->roleModel->getRoleById($roleId);
        
        if (!$role) {
            echo json_encode(['error' => 'Role not found']);
            exit;
        }
        
        // Ensure all fields are set
        $response = [
            'id' => $role['id'] ?? '',
            'role_name' => $role['role_name'] ?? '',
            'person_name' => $role['person_name'] ?? '',
            'status' => $role['status'] ?? 1,
            'created_by' => $role['created_by'] ?? '',
            'created_at' => $role['created_at'] ?? ''
        ];
        
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

public function addRole()
{
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    
    try {
        $data = [
            'role_name' => trim($_POST['role_name'] ?? ''),
            'person_name' => trim($_POST['person_name'] ?? ''),
            'status' => $_POST['status'] ?? 1,
            'created_by' => $this->getCurrentUserId()
        ];

        if (empty($data['role_name']) || empty($data['person_name'])) {
            echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
            exit;
        }

        $result = $this->roleModel->addRole($data);
        echo json_encode($result);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

public function updateRole()
{
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    
    try {
        $data = [
            'id' => $_POST['id'] ?? null,
            'role_name' => trim($_POST['role_name'] ?? ''),
            'person_name' => trim($_POST['person_name'] ?? ''),
            'status' => $_POST['status'] ?? 1
        ];

        if (!$data['id'] || empty($data['role_name']) || empty($data['person_name'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
            exit;
        }

        $result = $this->roleModel->updateRole($data);
        echo json_encode($result);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

public function deleteRole()
{
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    
    try {
        $roleId = $_GET['id'] ?? $_POST['id'] ?? null;
        
        if (!$roleId) {
            echo json_encode(['success' => false, 'message' => 'Invalid role ID']);
            exit;
        }

        $result = $this->roleModel->deleteRole($roleId);
        echo json_encode($result);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

public function toggleRoleStatus()
{
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    
    try {
        $roleId = $_GET['id'] ?? $_POST['id'] ?? null;
        
        if (!$roleId) {
            echo json_encode(['success' => false, 'message' => 'Invalid role ID']);
            exit;
        }

        $result = $this->roleModel->toggleStatus($roleId);
        echo json_encode($result);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}



    // Helper method to get current user ID from session
    private function getCurrentUserId()
    {
        // Get user ID from $_SESSION['user']['id'] (your session structure)
        if (isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id'])) {
            $userId = $_SESSION['user']['id'];
            
            // Validate the user exists in database
            if ($this->validateUserExists($userId)) {
                return $userId;
            }
        }
        
        // Fallback: Get first available user from database
        return $this->getFirstValidUserId();
    }

    // Validate if user exists in database
    private function validateUserExists($userId)
    {
        $sql = "SELECT id FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    // Get first valid user ID from database
    private function getFirstValidUserId()
    {
        $sql = "SELECT id FROM users ORDER BY id LIMIT 1";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        // Absolute fallback
        return 1;
    }

    // Add these methods to your AccountManagementController class

/**
 * View management statement with period filter (all roles)
 */
/**
 * View management statement with period filter (all roles)
 */
public function viewManagementStatement()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Get period filter
    $period = $_GET['period'] ?? 'all';
    $fromDate = $_GET['from'] ?? null;
    $toDate = $_GET['to'] ?? null;

    // Calculate date range based on period
    $dateFilter = [];
    $periodText = '';

    if ($period === 'custom' && $fromDate && $toDate) {
        $dateFilter['from'] = $fromDate;
        $dateFilter['to'] = $toDate;
        $periodText = date('d M, Y', strtotime($fromDate)) . ' to ' . date('d M, Y', strtotime($toDate));
    } elseif ($period !== 'all' && is_numeric($period)) {
        $months = (int)$period;
        $dateFilter['from'] = date('Y-m-d', strtotime("-{$months} months"));
        $dateFilter['to'] = date('Y-m-d');
        $periodText = "Last {$months} Months (" . date('d M, Y', strtotime($dateFilter['from'])) . " to " . date('d M, Y') . ")";
    } else {
        $periodText = "All Transactions";
    }

    // Get filtered transactions (all roles)
    $transactions = $this->managementModel->getFilteredTransactions($dateFilter);

    // Calculate statement summary for the period
    $statementSummary = [
        'total_amount' => 0,
        'paid_amount' => 0,
        'pending_amount' => 0,
        'transaction_count' => count($transactions)
    ];

    foreach ($transactions as $transaction) {
        $amount = $transaction['amount'] ?? 0;
        $statementSummary['total_amount'] += $amount;
        
        if (($transaction['payment_status'] ?? 'paid') === 'paid') {
            $statementSummary['paid_amount'] += $amount;
        } else {
            $statementSummary['pending_amount'] += $amount;
        }
    }

    // Use absolute path from root
    $root = dirname(dirname(__DIR__));
    $viewPath = $root . '/app/views/accounts/cashIn/management-cash-in-statement.php';
    
    if (!file_exists($viewPath)) {
        die("View file not found at: " . $viewPath);
    }
    
    // Render the statement view with ALL data needed
    // The view expects these variables:
    // - $transactions
    // - $statementSummary
    // - $periodText
    // - $period, $fromDate, $toDate (for filter)
    // - $roleDetails (will be null for ALL roles statement)
    
    // Since this is ALL roles statement, we don't have a specific role
    $roleDetails = null;
    
    include $viewPath;
    exit;
}

/**
 * View individual transaction invoice/receipt
 */
public function viewTransactionInvoice($transactionId = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Get transaction_id from URL parameter or query string
    if (!$transactionId) {
        $transactionId = $_GET['transaction_id'] ?? null;
    }

    if (!$transactionId) {
        setError("No transaction ID provided");
        header("Location: {$GLOBALS['base_url']}/management-cash-in");
        exit;
    }

    // Get transaction details (includes role info from the transaction record itself)
    $transaction = $this->managementModel->getTransactionById($transactionId);

    if (!$transaction) {
        setError("Transaction not found");
        header("Location: {$GLOBALS['base_url']}/management-cash-in");
        exit;
    }

    // Use absolute path from root
    $root = dirname(dirname(__DIR__));
    $viewPath = $root . '/app/views/accounts/cashIn/management-cash-in-invoice.php';
    
    if (!file_exists($viewPath)) {
        die("View file not found at: " . $viewPath);
    }
    
    // Render the invoice view
    include $viewPath;
    exit;
}
} 