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
        // Get all clients (booked leads with their details)
        $clients = $this->clientModel->getAllClients();

        // Get payment statistics for each client
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

    // Get transaction history for this booking
    $transactions = $this->clientModel->getTransactionsByBookingId($bookingId);

    // Check if booking money exists in transactions
    $hasBookingMoney = false;
    foreach ($transactions as $transaction) {
        if ($transaction['payment_type'] === 'booking_money') {
            $hasBookingMoney = true;
            
            // Add booking money specific data for display
            $transaction['installment_label'] = 'BOOKING';
            $transaction['payment_details'] = 'Booking Agreement';
            break;
        }
    }

    // Only fetch booking money from database if not already in transactions
    if (!$hasBookingMoney) {
        $bookingMoney = $this->clientModel->getBookingMoneyTransaction($bookingId);
        if ($bookingMoney) {
            $bookingMoney['installment_label'] = 'BOOKING';
            $bookingMoney['payment_details'] = 'Booking Agreement';
            array_unshift($transactions, $bookingMoney);
        }
    }

    // Get payment summary statistics
    $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($bookingId);

    $this->view("accounts/cashIn/clients-cash-in-history", [
        "current_route" => "clients-cash-in-history",
        "page_title" => "Transaction History - " . $clientDetails['client_name'],
        "clientDetails" => $clientDetails,
        "transactions" => $transactions,
        "paymentSummary" => $paymentSummary
    ]);
}

    /**
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

            // Validate EMI data
            // if (empty($emiScheduleData['due_emi_amount']) || $emiScheduleData['due_emi_amount'] <= 0) {
            //     setError("Please enter a valid due amount for EMI");
            //     header("Location: {$GLOBALS['base_url']}/client-cash-in-form?booking_id={$bookingId}");
            //     exit;
            // }
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

        // Prepare transaction data WITHOUT updated_by (table doesn't have this column)
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
            // Removed: 'updated_by' => $_SESSION['user_id'] ?? 1 (column doesn't exist)
        ];

        error_log("Transaction Data for update: " . print_r($transactionData, true));

        // EMI schedule data (only if payment type is EMI)
        $emiScheduleData = null;
        if ($transactionData['payment_type'] === 'emi') {
            $emiScheduleData = [
                'due_emi_amount' => $_POST['due_emi_amount'] ?? 0
            ];

            error_log("EMI Schedule Data: " . print_r($emiScheduleData, true));

            // Validate EMI data
            if (empty($emiScheduleData['due_emi_amount']) || $emiScheduleData['due_emi_amount'] <= 0) {
                setError("Please enter a valid due amount for EMI");
                header("Location: {$GLOBALS['base_url']}/edit-client-cash-in?payment_id={$paymentId}");
                exit;
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
}
