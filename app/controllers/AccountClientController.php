<?php
class AccountClientController extends Controller
{
    protected $db;
    protected $clientModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->clientModel = new AccountClient($this->db);
    }

    public function index()
    {
        // Get all clients (unique leads)
        $clients = $this->clientModel->getAllClients();

        // Get payment statistics
        $paymentStats = $this->clientModel->getPaymentStatistics();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->view("accounts/cashIn/clients", [
            "current_route" => "clients-cash-in",
            "page_title" => "Clients Management - Cash In",
            "clients" => $clients,
            "paymentStats" => $paymentStats
        ]);
    }

 public function indexTwo($bookingId = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get booking_id from URL parameter or query string
        if (!$bookingId) {
            $bookingId = $_GET['booking_id'] ?? null;
        }

        if (!$bookingId) {
            setError("No land share ID provided");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        // Get client details
        $clientDetails = $this->clientModel->getClientByBookingId($bookingId);

        if (!$clientDetails) {
            setError("Land share not found");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        // Get transaction history for this booking
        $transactions = $this->clientModel->getTransactionsByBookingId($bookingId);
        
        // Filter out any automatically generated booking money transactions
        $filteredTransactions = array_filter($transactions, function($transaction) {
            if (isset($transaction['id']) && is_string($transaction['id']) && strpos($transaction['id'], 'booking-') === 0) {
                return false;
            }
            return true;
        });
        
        // Re-index the array
        $transactions = array_values($filteredTransactions);

        // Get payment summary statistics (now includes service fee)
           $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($bookingId);
    


        $this->view("accounts/cashIn/clients-cash-in-history", [
            "current_route" => "clients-cash-in-history",
            "page_title" => "Transaction History - " . $clientDetails['client_name'],
            "clientDetails" => $clientDetails,
            "transactions" => $transactions,
            "paymentSummary" => $paymentSummary
        ]);
    }

 public function getClientLands($leadId = null)
    {
        if (!$leadId) {
            $leadId = $_GET['lead_id'] ?? null;
        }

        if (!$leadId) {
            echo json_encode(['success' => false, 'message' => 'No lead ID provided']);
            exit;
        }

        // Get client details
        $clientDetails = $this->clientModel->getClientByLeadId($leadId);
        
        // Get client's lands/projects
        $lands = $this->clientModel->getLandsByLeadId($leadId);

        echo json_encode([
            'success' => true,
            'client' => $clientDetails,
            'lands' => $lands
        ]);
        exit;
    }

    public function indexView()
    {
        // Get all clients (booked leads with their details)
        $clients = $this->clientModel->getAllClients();

        // Get payment statistics for each client
        $paymentStats = $this->clientModel->getPaymentStatistics();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->view("ced/clients-cashin-view", [
            "current_route" => "clients-cash-in-view",
            "page_title" => "Clients Management - Cash In View",
            "clients" => $clients,
            "paymentStats" => $paymentStats
        ]);
    }
    

public function indexTwoView($bookingId = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $bookingId = (int)$bookingId;

    if ($bookingId <= 0) {
        setError("No booking ID provided");
        header("Location: {$GLOBALS['base_url']}/clients-cashin-view");
        exit;
    }

    $clientDetails = $this->clientModel->getClientByBookingId($bookingId);

    if (!$clientDetails) {
        setError("Client not found");
        header("Location: {$GLOBALS['base_url']}/clients-cashin-view");
        exit;
    }

    $transactions = $this->clientModel->getTransactionsByBookingId($bookingId);
    $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($bookingId);

    $this->view("ced/clients-cash-in-history-view", [
        "current_route" => "clients-cash-in-history-view",
        "page_title" => "Transaction History - " . $clientDetails['client_name'],
        "clientDetails" => $clientDetails,
        "transactions" => $transactions,
        "paymentSummary" => $paymentSummary
    ]);
}

    /*
     * Show add transaction form
     */
public function showAddTransactionForm($bookingId = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!$bookingId) {
        $bookingId = $_GET['booking_id'] ?? null;
    }

    if (!$bookingId) {
        setError("No booking ID provided");
        header("Location: {$GLOBALS['base_url']}/clients-cash-in");
        exit;
    }

    $clientDetails = $this->clientModel->getClientByBookingId($bookingId);

    if (!$clientDetails) {
        setError("Client not found");
        header("Location: {$GLOBALS['base_url']}/clients-cash-in");
        exit;
    }

    // SIMPLE CHECK: Is registration paid?
    $registrationPaid = $this->clientModel->isRegistrationPaid($bookingId);

    $this->view("accounts/cashIn/client-cash-in-form", [
        "current_route" => "client-cash-in-form",
        "page_title" => "Add Transaction - " . $clientDetails['client_name'],
        "clientDetails" => $clientDetails,
        "bookingId" => $bookingId,
        "registrationPaid" => $registrationPaid  // Just pass this boolean
    ]);
}

public function addTransaction()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $bookingId = $_POST['booking_id'] ?? null;

        if (!$bookingId) {
            setError("Invalid booking ID");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $paymentType = $_POST['payment_type'] ?? '';
        
        // SIMPLE CHECK: If EMI is selected, verify registration is paid
        if ($paymentType === 'emi') {
            if (!$this->clientModel->isRegistrationPaid($bookingId)) {
                setError("Registration payment must be made before adding EMI transactions.");
                header("Location: {$GLOBALS['base_url']}/client-cash-in-form?booking_id={$bookingId}");
                exit;
            }
        }

        // Prepare transaction data
        $transactionData = [
            'booking_id' => $bookingId,
            'payment_type' => $paymentType,
            'amount' => $_POST['amount'] ?? 0,
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'payment_status' => $_POST['payment_status'] ?? 'paid',
            'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
            'transaction_ref' => $_POST['transaction_ref'] ?? null,
            'bank_name' => $_POST['bank_name'] ?? null,
            'bank_account_no' => $_POST['bank_account_no'] ?? null,
            'cheque_no' => $_POST['cheque_no'] ?? null,
            'online_type' => $_POST['online_type'] ?? null,
            'created_by' => $_SESSION['user_id'] ?? 1
        ];

        // EMI schedule data (only if payment type is EMI)
        $emiScheduleData = null;
        if ($transactionData['payment_type'] === 'emi') {
            $emiScheduleData = [
                'due_emi_amount' => $_POST['due_emi_amount'] ?? 0
            ];

            // UPDATED VALIDATION: Only require due amount for partially_paid status
            if ($transactionData['payment_status'] === 'partially_paid') {
                if ( $emiScheduleData['due_emi_amount'] <= 0) {
                    setError("Partially Paid status requires a due amount greater than 0");
                    header("Location: {$GLOBALS['base_url']}/client-cash-in-form?booking_id={$bookingId}");
                    exit;
                }
            }
            // For 'paid' status, due_emi_amount can be 0 or null - no validation needed
        }

        // Add transaction with EMI schedule if applicable
        $result = $this->clientModel->addTransactionWithEMI($transactionData, $emiScheduleData);

        if ($result['success']) {
            setSuccess($result['message']);
            header("Location: {$GLOBALS['base_url']}/clients-cash-in-history?booking_id={$bookingId}");
            exit;
        } else {
            setError($result['message']);
            header("Location: {$GLOBALS['base_url']}/client-cash-in-form?booking_id={$bookingId}");
            exit;
        }
    }
}

    /**
     * Show edit transaction form
     */
    public function showEditTransactionForm($paymentId = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!$paymentId) {
            $paymentId = $_GET['payment_id'] ?? null;
        }

        if (!$paymentId) {
            setError("No payment ID provided");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        // Get transaction details
        $transaction = $this->clientModel->getTransactionById($paymentId);

        if (!$transaction) {
            setError("Transaction not found");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        // Get client details
        $clientDetails = $this->clientModel->getClientByBookingId($transaction['booking_id']);

        // Get EMI schedule if payment type is EMI
        $emiSchedule = null;
        if ($transaction['payment_type'] === 'emi') {
            $emiSchedule = $this->clientModel->getEMIScheduleByPaymentId($paymentId);
        }

        // DEBUG: Check what data is being fetched
        error_log("Transaction Data: " . print_r($transaction, true));
        error_log("EMI Schedule: " . print_r($emiSchedule, true));

        $this->view("accounts/cashIn/client-cash-in-edit-form", [
            "current_route" => "client-cash-in-edit-form",
            "page_title" => "Edit Transaction - " . $clientDetails['client_name'],
            "clientDetails" => $clientDetails,
            "transactionDetails" => $transaction, // Changed from "transaction" to "transactionDetails"
            "emiSchedule" => $emiSchedule
        ]);
    }

    /**
     * Update transaction
     */
/**
 * Update transaction
 */
public function updateTransaction()
{
    error_log("=== CONTROLLER: updateTransaction START ===");
    error_log("POST Data: " . print_r($_POST, true));
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $paymentId = $_POST['payment_id'] ?? null;
        $bookingId = $_POST['booking_id'] ?? null;

        error_log("Payment ID: $paymentId");
        error_log("Booking ID: $bookingId");

        if (!$paymentId || !$bookingId) {
            setError("Invalid payment or booking ID");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        
        error_log("Session User ID: " . ($_SESSION['user_id'] ?? 'null'));

        // Get the OLD transaction data to compare due amounts
        $oldTransaction = $this->clientModel->getTransactionById($paymentId);
        $oldEmiSchedule = null;
        $oldDueAmount = 0;
        
        if ($oldTransaction && $oldTransaction['payment_type'] === 'emi') {
            $oldEmiSchedule = $this->clientModel->getEMIScheduleByPaymentId($paymentId);
            $oldDueAmount = (float)($oldEmiSchedule['due_emi_amount'] ?? 0);
        }

        error_log("Old Due Amount: " . $oldDueAmount);

        // Prepare transaction data
        $transactionData = [
            'payment_id' => $paymentId,
            'booking_id' => $bookingId,
            'payment_type' => $_POST['payment_type'] ?? 'emi',
            'amount' => $_POST['amount'] ?? 0,
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'payment_status' => $_POST['payment_status'] ?? 'paid',
            'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
            'transaction_ref' => $_POST['transaction_ref'] ?? null,
            'bank_name' => $_POST['bank_name'] ?? null,
            'bank_account_no' => $_POST['bank_account_no'] ?? null,
            'cheque_no' => $_POST['cheque_no'] ?? null,
            'online_type' => $_POST['online_type'] ?? null
        ];

        error_log("Transaction Data for update: " . print_r($transactionData, true));

        // EMI schedule data (only if payment type is EMI)
        $emiScheduleData = null;
        if ($transactionData['payment_type'] === 'emi') {
            $newDueAmount = (float)($_POST['due_emi_amount'] ?? 0);
            
            $emiScheduleData = [
                'due_emi_amount' => $newDueAmount,
                'old_due_amount' => $oldDueAmount  // Pass old due amount for calculation
            ];

            error_log("New Due Amount: " . $newDueAmount);
            error_log("EMI Schedule Data: " . print_r($emiScheduleData, true));

            // UPDATED VALIDATION: Only require due amount for partially_paid status
            if ($transactionData['payment_status'] === 'partially_paid') {
                if (empty($emiScheduleData['due_emi_amount']) || $emiScheduleData['due_emi_amount'] <= 0) {
                    setError("Partially Paid status requires a due amount greater than 0");
                    header("Location: {$GLOBALS['base_url']}/edit-client-cash-in?payment_id={$paymentId}");
                    exit;
                }
            }

            // Calculate the additional payment made (if due amount decreased)
            if ($oldDueAmount > 0 && $newDueAmount < $oldDueAmount) {
                $additionalPayment = $oldDueAmount - $newDueAmount;
                error_log("Additional payment detected: " . $additionalPayment);
                
                // Add this to the transaction amount
                $transactionData['amount'] = (float)$transactionData['amount'] + $additionalPayment;
                error_log("Updated transaction amount: " . $transactionData['amount']);
            }
        }

        // Update transaction with EMI schedule if applicable
        $result = $this->clientModel->updateTransactionWithEMI($transactionData, $emiScheduleData);

        error_log("Update Result: " . print_r($result, true));

        if ($result['success']) {
            setSuccess($result['message']);
            header("Location: {$GLOBALS['base_url']}/clients-cash-in-history?booking_id={$bookingId}");
            exit;
        } else {
            setError($result['message']);
            header("Location: {$GLOBALS['base_url']}/edit-client-cash-in?payment_id={$paymentId}");
            exit;
        }
    } else {
        setError("Invalid request method");
        header("Location: {$GLOBALS['base_url']}/clients-cash-in");
        exit;
    }
}

    /**
     * Delete transaction
     */
    public function deleteTransaction()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $paymentId = $_GET['payment_id'] ?? null;
        $bookingId = $_GET['booking_id'] ?? null;

        if (!$paymentId || !$bookingId) {
            setError("Invalid payment or booking ID");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        $result = $this->clientModel->deleteTransaction($paymentId);

        if ($result['success']) {
            setSuccess($result['message']);
        } else {
            setError($result['message']);
        }

        header("Location: {$GLOBALS['base_url']}/clients-cash-in-history?booking_id={$bookingId}");
        exit;
    }

    public function getClientDetails($bookingId)
    {
        header('Content-Type: application/json');
        $client = $this->clientModel->getClientByBookingId($bookingId);
        echo json_encode($client);
        exit;
    }

    public function clientStatement($booking_id) {
    try {
        // Get period from query params
        $period = $_GET['period'] ?? 'all';
        $from_date = $_GET['from'] ?? null;
        $to_date = $_GET['to'] ?? null;
        
        // Get client details
        $client_query = "SELECT 
            b.id as booking_id,
            b.booking_date,
            b.booking_money,
            b.registration_money,
            b.total_price,
            b.number_of_emi,
            c.client_name,
            c.phone,
            c.email,
            p.project_name,
            f.flat_no,
            u.name as unit_name
        FROM bookings b
        LEFT JOIN clients c ON b.client_id = c.id
        LEFT JOIN projects p ON b.project_id = p.id
        LEFT JOIN flats f ON b.flat_id = f.id
        LEFT JOIN units u ON f.unit_id = u.id
        WHERE b.id = ?";
        
        $stmt = $this->db->prepare($client_query);
        $stmt->execute([$booking_id]);
        $clientDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$clientDetails) {
            die("Client not found");
        }
        
        // Calculate construction fee
        $construction_fee = $clientDetails['total_price'] - 
                          ($clientDetails['booking_money'] + $clientDetails['registration_money']);
        
        // Build transaction query based on period
        $transaction_query = "SELECT 
            'emi' as payment_type,
            pe.payment_date,
            NULL as booking_date,
            pe.amount,
            pe.payment_status,
            pe.payment_method,
            pe.bank_name,
            pe.bank_account_no,
            pe.cheque_no,
            pe.online_type,
            CONCAT('EMI ', pe.emi_no, ' of ', pe.emi_total) as installment_label
        FROM payment_emi pe
        WHERE pe.booking_id = ?";
        
        $params = [$booking_id];
        
        // Add date filters
        if ($period === 'custom' && $from_date && $to_date) {
            $transaction_query .= " AND pe.payment_date BETWEEN ? AND ?";
            $params[] = $from_date;
            $params[] = $to_date;
            $period_text = date('d M, Y', strtotime($from_date)) . ' to ' . date('d M, Y', strtotime($to_date));
        } elseif ($period !== 'all') {
            $months = (int)$period;
            $from_date = date('Y-m-d', strtotime("-$months months"));
            $transaction_query .= " AND pe.payment_date >= ?";
            $params[] = $from_date;
            $period_text = "Last $months Months (" . date('d M, Y', strtotime($from_date)) . " to " . date('d M, Y') . ")";
        } else {
            $period_text = "All Transactions";
        }
        
        // Add other payment types
        $transaction_query .= "
        UNION ALL
        SELECT 
            pr.payment_type,
            pr.payment_date,
            NULL as booking_date,
            pr.amount,
            pr.payment_status,
            pr.payment_method,
            pr.bank_name,
            pr.bank_account_no,
            pr.cheque_no,
            pr.online_type,
            UPPER(REPLACE(pr.payment_type, '_', ' ')) as installment_label
        FROM payment_registration pr
        WHERE pr.booking_id = ?";
        
        $params[] = $booking_id;
        
        if ($period === 'custom' && $from_date && $to_date) {
            $transaction_query .= " AND pr.payment_date BETWEEN ? AND ?";
            $params[] = $from_date;
            $params[] = $to_date;
        } elseif ($period !== 'all') {
            $transaction_query .= " AND pr.payment_date >= ?";
            $params[] = $from_date;
        }
        
        // Add booking money
        $transaction_query .= "
        UNION ALL
        SELECT 
            'booking_money' as payment_type,
            NULL as payment_date,
            b.booking_date,
            b.booking_money as amount,
            'paid' as payment_status,
            'agreement' as payment_method,
            NULL as bank_name,
            NULL as bank_account_no,
            NULL as cheque_no,
            NULL as online_type,
            'BOOKING' as installment_label
        FROM bookings b
        WHERE b.id = ?";
        
        $params[] = $booking_id;
        
        if ($period === 'custom' && $from_date && $to_date) {
            $transaction_query .= " AND b.booking_date BETWEEN ? AND ?";
            $params[] = $from_date;
            $params[] = $to_date;
        } elseif ($period !== 'all') {
            $transaction_query .= " AND b.booking_date >= ?";
            $params[] = $from_date;
        }
        
        $transaction_query .= " ORDER BY COALESCE(payment_date, booking_date) DESC";
        
        $stmt = $this->db->prepare($transaction_query);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate statement summary
        $total_paid = 0;
        foreach ($transactions as $transaction) {
            $total_paid += $transaction['amount'] ?? 0;
        }
        
        $statement_summary = [
            'total_paid' => $total_paid,
            'transaction_count' => count($transactions)
        ];
        
        // Get payment summary for outstanding amount
        $emi_query = "SELECT 
            COUNT(*) as total_emi,
            SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as completed_emi,
            SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_emi,
            SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as construction_fee_paid
        FROM payment_emi 
        WHERE booking_id = ?";
        
        $stmt = $this->db->prepare($emi_query);
        $stmt->execute([$booking_id]);
        $emi_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $paymentSummary = [
            'total_emi' => $emi_data['total_emi'] ?? 0,
            'completed_emi' => $emi_data['completed_emi'] ?? 0,
            'remaining_construction_fee' => $construction_fee - ($emi_data['construction_fee_paid'] ?? 0)
        ];
        
        // Load the statement view
        include '../app/views/accounts/client_statement.php';
        
    } catch (Exception $e) {
        error_log("Statement Error: " . $e->getMessage());
        die("Error generating statement: " . $e->getMessage());
    }
}

// Add these methods to your AccountClientController class (after existing methods)

/**
 * View individual payment invoice/receipt
 */
public function viewPaymentInvoice($paymentId = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Get payment_id from URL parameter or query string
    if (!$paymentId) {
        $paymentId = $_GET['payment_id'] ?? null;
    }

    if (!$paymentId) {
        setError("No payment ID provided");
        header("Location: {$GLOBALS['base_url']}/clients-cash-in");
        exit;
    }

    // Get payment details
    $payment = $this->clientModel->getTransactionById($paymentId);

    if (!$payment) {
        setError("Payment not found");
        header("Location: {$GLOBALS['base_url']}/clients-cash-in");
        exit;
    }

    // Get client details
    $clientDetails = $this->clientModel->getClientByBookingId($payment['booking_id']);

    if (!$clientDetails) {
        setError("Client not found");
        header("Location: {$GLOBALS['base_url']}/clients-cash-in");
        exit;
    }

    // Get EMI schedule if payment type is EMI
    $emiSchedule = null;
    if ($payment['payment_type'] === 'emi') {
        $emiSchedule = $this->clientModel->getEMIScheduleByPaymentId($paymentId);
        
        // Add installment label to payment
        if ($emiSchedule && isset($emiSchedule['emi_no'])) {
            $emiNo = $emiSchedule['emi_no'];
            
            // Determine suffix for ordinal numbers
            if ($emiNo % 100 >= 11 && $emiNo % 100 <= 13) {
                $suffix = 'th';
            } else {
                switch ($emiNo % 10) {
                    case 1: $suffix = 'st'; break;
                    case 2: $suffix = 'nd'; break;
                    case 3: $suffix = 'rd'; break;
                    default: $suffix = 'th';
                }
            }
            
            $payment['installment_label'] = $emiNo . $suffix . ' Installment';
        }
    }

    // Use absolute path from root
    $root = dirname(dirname(__DIR__)); // Go up to project root
    $viewPath = $root . '/app/views/accounts/cashIn/invoice.php';
    
    if (!file_exists($viewPath)) {
        die("View file not found at: " . $viewPath);
    }
    
    // Render the individual payment invoice view
    include $viewPath;
    exit;
}

// Add this method to your AccountClientController class

/**
 * View client statement with period filter
 */
public function viewClientStatement($bookingId = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Get booking_id from URL parameter or query string
    if (!$bookingId) {
        $bookingId = $_GET['booking_id'] ?? null;
    }

    if (!$bookingId) {
        setError("No booking ID provided");
        header("Location: {$GLOBALS['base_url']}/clients-cash-in");
        exit;
    }

    // Get client details
    $clientDetails = $this->clientModel->getClientByBookingId($bookingId);

    if (!$clientDetails) {
        setError("Client not found");
        header("Location: {$GLOBALS['base_url']}/clients-cash-in");
        exit;
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

    // Get filtered transactions
    $transactions = $this->clientModel->getFilteredTransactionsByBookingId($bookingId, $dateFilter);

    // Filter out booking money placeholder if exists
    $filteredTransactions = array_filter($transactions, function($transaction) {
        if (isset($transaction['id']) && is_string($transaction['id']) && strpos($transaction['id'], 'booking-') === 0) {
            return false;
        }
        return true;
    });
    
    $transactions = array_values($filteredTransactions);

    // Calculate statement summary for the period
    $statementSummary = [
        'total_paid' => 0,
        'transaction_count' => count($transactions)
    ];

    foreach ($transactions as $transaction) {
        $statementSummary['total_paid'] += $transaction['amount'] ?? 0;
    }

    // Get overall payment summary (not filtered by period)
    $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($bookingId);

    // Use absolute path from root
    $root = dirname(dirname(__DIR__));
    $viewPath = $root . '/app/views/accounts/cashIn/client-statement.php';
    
    if (!file_exists($viewPath)) {
        die("View file not found at: " . $viewPath);
    }
    
    // Render the statement view
    include $viewPath;
    exit;
}
}
