<?php
// Include required model files
require_once __DIR__ . '/../models/AccountManagementCashOut.php';
require_once __DIR__ . '/../models/ManagementCategory.php';

class AccountManagementCashoutController extends Controller
{
    protected $db;
    protected $cashoutModel;
    protected $categoryModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->cashoutModel = new AccountManagementCashout($this->db);
        $this->categoryModel = new ManagementCategory($this->db);
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    // Dashboard / list
    public function index()
    {
        $transactions = $this->cashoutModel->getAllTransactions();
        $statistics = $this->cashoutModel->getStatistics();

        $this->view("accounts/cashout/management-cashout-list", [
            "current_route" => "management-cash-out",
            "page_title" => "Management Cash Out",
            "transactions" => $transactions,
            "statistics" => $statistics
        ]);
    }

    // Show Add Form
    public function showAddForm()
    {
        $categories = $this->categoryModel->getAllActiveCategories();
        
        $this->view("accounts/cashout/management-cashout-form", [
            "current_route" => "add-management-cash-out",
            "page_title" => "Add Cash Out Transaction",
            "categories" => $categories
        ]);
    }

    // Store new transaction
    public function storeTransaction()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $created_by = $this->getCurrentUserId();
            
            $data = [
                'category_id' => $_POST['category_id'] ?? null,
                'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d'),
                'paid_to' => trim($_POST['paid_to'] ?? ''),
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
            if (empty($data['category_id']) || empty($data['paid_to']) || $data['amount'] <= 0) {
                $_SESSION['old_form_data'] = $_POST;
                setError("Please fill all required fields correctly.");
                header("Location: {$GLOBALS['base_url']}/add-management-cash-out");
                exit;
            }

            // Validate numeric amount
            if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
                $_SESSION['old_form_data'] = $_POST;
                setError("Please enter a valid amount.");
                header("Location: {$GLOBALS['base_url']}/add-management-cash-out");
                exit;
            }

            // Additional validation based on payment method
            if ($data['payment_method'] === 'bank' && empty($data['bank_name'])) {
                $_SESSION['old_form_data'] = $_POST;
                setError("Bank name is required for bank payments.");
                header("Location: {$GLOBALS['base_url']}/add-management-cash-out");
                exit;
            }

            if ($data['payment_method'] === 'cheque' && empty($data['cheque_no'])) {
                $_SESSION['old_form_data'] = $_POST;
                setError("Cheque number is required for cheque payments.");
                header("Location: {$GLOBALS['base_url']}/add-management-cash-out");
                exit;
            }

            try {
                $result = $this->cashoutModel->addTransaction($data);

                if ($result['success']) {
                    unset($_SESSION['old_form_data']);
                    setSuccess($result['message']);
                    header("Location: {$GLOBALS['base_url']}/management-cash-out");
                } else {
                    $_SESSION['old_form_data'] = $_POST;
                    setError($result['message']);
                    header("Location: {$GLOBALS['base_url']}/add-management-cash-out");
                }
            } catch (Exception $e) {
                $_SESSION['old_form_data'] = $_POST;
                setError("An error occurred: " . $e->getMessage());
                header("Location: {$GLOBALS['base_url']}/add-management-cash-out");
            }
            exit;
        }
        
        header("Location: {$GLOBALS['base_url']}/add-management-cash-out");
        exit;
    }

    // Show Edit Form
    public function showEditForm($transactionId = null)
    {
        $transactionId = $transactionId ?? $_GET['id'] ?? null;
        if (!$transactionId) {
            setError("Transaction ID missing.");
            header("Location: {$GLOBALS['base_url']}/management-cash-out");
            exit;
        }

        $transaction = $this->cashoutModel->getTransactionById($transactionId);
        if (!$transaction) {
            setError("Transaction not found.");
            header("Location: {$GLOBALS['base_url']}/management-cash-out");
            exit;
        }

        $categories = $this->categoryModel->getAllActiveCategories();

        $this->view("accounts/cashout/edit-management-cashout", [
            "current_route" => "edit-management-cash-out",
            "page_title" => "Edit Cash Out Transaction",
            "transaction" => $transaction,
            "categories" => $categories
        ]);
    }

    // Update transaction
    public function updateTransaction()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $transactionId = $_POST['transaction_id'] ?? null;
            if (!$transactionId) {
                setError("Invalid transaction ID");
                header("Location: {$GLOBALS['base_url']}/management-cash-out");
                exit;
            }

            $data = [
                'transaction_id' => $transactionId,
                'category_id' => $_POST['category_id'] ?? null,
                'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d'),
                'paid_to' => trim($_POST['paid_to'] ?? ''),
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

            if (empty($data['category_id']) || empty($data['paid_to']) || $data['amount'] <= 0) {
                setError("Please fill all required fields correctly.");
                header("Location: {$GLOBALS['base_url']}/edit-management-cash-out?id={$transactionId}");
                exit;
            }

            if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
                setError("Please enter a valid amount.");
                header("Location: {$GLOBALS['base_url']}/edit-management-cash-out?id={$transactionId}");
                exit;
            }

            if ($data['payment_method'] === 'bank' && empty($data['bank_name'])) {
                setError("Bank name is required for bank payments.");
                header("Location: {$GLOBALS['base_url']}/edit-management-cash-out?id={$transactionId}");
                exit;
            }

            if ($data['payment_method'] === 'cheque' && empty($data['cheque_no'])) {
                setError("Cheque number is required for cheque payments.");
                header("Location: {$GLOBALS['base_url']}/edit-management-cash-out?id={$transactionId}");
                exit;
            }

            try {
                $result = $this->cashoutModel->updateTransaction($data);

                if ($result['success']) {
                    setSuccess($result['message']);
                    header("Location: {$GLOBALS['base_url']}/management-cash-out");
                } else {
                    setError($result['message']);
                    header("Location: {$GLOBALS['base_url']}/edit-management-cash-out?id={$transactionId}");
                }
            } catch (Exception $e) {
                setError("An error occurred: " . $e->getMessage());
                header("Location: {$GLOBALS['base_url']}/edit-management-cash-out?id={$transactionId}");
            }
            exit;
        }
        
        header("Location: {$GLOBALS['base_url']}/management-cash-out");
        exit;
    }

    // Delete transaction
    public function deleteTransaction()
    {
        $transactionId = $_GET['id'] ?? null;
        if (!$transactionId) {
            setError("Invalid transaction ID");
            header("Location: {$GLOBALS['base_url']}/management-cash-out");
            exit;
        }

        try {
            $result = $this->cashoutModel->deleteTransaction($transactionId);
            if ($result['success']) setSuccess($result['message']);
            else setError($result['message']);
        } catch (Exception $e) {
            setError("An error occurred: " . $e->getMessage());
        }
        
        header("Location: {$GLOBALS['base_url']}/management-cash-out");
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
        $transaction = $this->cashoutModel->getTransactionById($transactionId);
        if (!$transaction) {
            echo json_encode(['error' => 'Transaction not found']);
        } else {
            echo json_encode([
                'description' => $transaction['description'] ?? ''
            ]);
        }
        exit;
    }

    /**
     * View cash out statement with period filter
     */
    public function showStatement()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get period filter parameters
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

        // Get filtered transactions
        if (!empty($dateFilter)) {
            $transactions = $this->cashoutModel->getTransactionsByDateRange($dateFilter['from'], $dateFilter['to']);
        } else {
            $transactions = $this->cashoutModel->getAllTransactions();
        }

        // Calculate statement summary for the period
        $statementSummary = [
            'transaction_count' => count($transactions),
            'total_amount' => 0,
            'paid_amount' => 0,
            'pending_amount' => 0
        ];

        foreach ($transactions as $transaction) {
            $amount = $transaction['amount'] ?? 0;
            $status = $transaction['payment_status'] ?? 'paid';
            
            $statementSummary['total_amount'] += $amount;
            
            if ($status === 'paid') {
                $statementSummary['paid_amount'] += $amount;
            } else {
                $statementSummary['pending_amount'] += $amount;
            }
        }

        // Use absolute path from root
        $root = dirname(dirname(__DIR__)); // Go up to project root
        $viewPath = $root . '/app/views/accounts/cashout/management-cashout-statement.php';
        
        if (!file_exists($viewPath)) {
            die("View file not found at: " . $viewPath);
        }
        
        // Render the statement view
        include $viewPath;
        exit;
    }

    /**
     * View individual transaction invoice
     */
    public function showInvoice($transactionId = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get transaction_id from URL parameter or query string
        if (!$transactionId) {
            $transactionId = $_GET['id'] ?? null;
        }

        if (!$transactionId) {
            setError("No transaction ID provided");
            header("Location: {$GLOBALS['base_url']}/management-cash-out");
            exit;
        }

        // Get transaction details
        $transaction = $this->cashoutModel->getTransactionById($transactionId);

        if (!$transaction) {
            setError("Transaction not found");
            header("Location: {$GLOBALS['base_url']}/management-cash-out");
            exit;
        }

        // Use absolute path from root
        $root = dirname(dirname(__DIR__)); // Go up to project root
        $viewPath = $root . '/app/views/accounts/cashout/management-cashout-invoice.php';
        
        if (!file_exists($viewPath)) {
            die("Invoice view file not found at: " . $viewPath);
        }
        
        // Render the invoice view
        include $viewPath;
        exit;
    }

    // Export transactions
    public function exportTransactions()
    {
        $transactions = $this->cashoutModel->getAllTransactions();
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cashout_transactions_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, [
            'ID', 'Transaction Date', 'Category', 'Paid To', 
            'Description', 'Amount', 'Payment Method', 'Payment Status',
            'Bank Name', 'Bank Account No', 'Cheque No', 'Online Type',
            'Transaction Ref', 'Created By', 'Created At'
        ]);
        
        // Add data rows
        foreach ($transactions as $transaction) {
            fputcsv($output, [
                $transaction['id'],
                $transaction['transaction_date'],
                $transaction['category_name'] ?? 'N/A',
                $transaction['paid_to'],
                $transaction['description'],
                $transaction['amount'],
                $transaction['payment_method'],
                $transaction['payment_status'],
                $transaction['bank_name'] ?? '',
                $transaction['bank_account_no'] ?? '',
                $transaction['cheque_no'] ?? '',
                $transaction['online_type'] ?? '',
                $transaction['transaction_ref'] ?? '',
                $transaction['created_by_name'] ?? '',
                $transaction['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }

    // Helper method to get current user ID from session
    private function getCurrentUserId()
    {
        if (isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id'])) {
            $userId = $_SESSION['user']['id'];
            
            if ($this->validateUserExists($userId)) {
                return $userId;
            }
        }
        
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
        
        return 1;
    }
}
