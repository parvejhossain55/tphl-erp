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
    
    // Check if booking money transaction exists in database
    $hasBookingMoneyInDB = $this->clientModel->hasBookingMoneyInDatabase($bookingId);
    
    // If booking money doesn't exist in transactions, add it from agreement
    if (!$hasBookingMoneyInDB && !empty($clientDetails['booking_money'])) {
        $bookingMoneyTransaction = $this->clientModel->getBookingMoneyTransaction($bookingId);
        
        // If still not found, create a display entry from the agreement
        if (!$bookingMoneyTransaction) {
            $bookingMoneyTransaction = [
                'id' => 'booking-' . $bookingId,
                'payment_date' => $clientDetails['booking_date'],
                'formatted_date' => date('d M, Y', strtotime($clientDetails['booking_date'])),
                'payment_type' => 'booking_money',
                'installment_label' => 'BOOKING',
                'amount' => $clientDetails['booking_money'],
                'land_share_amount' => $clientDetails['booking_money'],
                'service_fee' => 0,
                'payment_status' => 'paid',
                'status_class' => 'bg-success',
                'status_text' => 'Paid',
                'client_name' => $clientDetails['client_name'],
                'project_name' => $clientDetails['project_name'],
                'flat_no' => $clientDetails['flat_no'],
                'unit_name' => $clientDetails['unit_name'],
                'created_by_name' => $clientDetails['agent_name'] ?? 'System'
            ];
        }
        
        // Add booking money transaction at the beginning
        array_unshift($transactions, $bookingMoneyTransaction);
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check for project filter
        $selectedProjectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : null;

        // Get all clients, optionally filtered by project
        $clients = $this->clientModel->getAllClients($selectedProjectId);

        // Get payment statistics
        $paymentStats = $this->clientModel->getPaymentStatistics($selectedProjectId);

        // Get all projects for dropdown
        $projects = $this->clientModel->getProjectsForCED();

        // Get flat stats and booked flats if project selected
        $flatStats = null;
        $bookedFlats = [];
        $availableFlats = [];
        $selectedProjectName = '';

        if ($selectedProjectId) {
            $flatStats = $this->clientModel->getProjectFlatStats($selectedProjectId);
            $bookedFlats = $this->clientModel->getBookedFlatsByProject($selectedProjectId);
            $availableFlats = $this->clientModel->getAvailableFlatsByProject($selectedProjectId);

            foreach ($projects as $project) {
                if ((int) $project['id'] === $selectedProjectId) {
                    $selectedProjectName = $project['name'];
                    break;
                }
            }
        }

        $this->view("ced/clients-cashin-view", [
            "current_route" => "clients-cashin-view",
            "page_title" => "Clients Cash In" . ($selectedProjectName ? " - " . $selectedProjectName : ""),
            "clients" => $clients,
            "paymentStats" => $paymentStats,
            "projects" => $projects,
            "selectedProjectId" => $selectedProjectId,
            "selectedProjectName" => $selectedProjectName,
            "flatStats" => $flatStats,
            "bookedFlats" => $bookedFlats,
            "availableFlats" => $availableFlats
        ]);
    }

    public function CedView()
    {
        // Get all bookings for the CED dashboard
        $clients = $this->clientModel->getAllBookings();
        $paymentStats = $this->clientModel->getPaymentStatistics();
        $collectionData = $this->clientModel->getDashboardCollectionStats();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->view("ced/ced-dashboard", [
            "current_route" => "ced-dashboard",
            "page_title" => "CED Dashboard",
            "clients" => $clients,
            "paymentStats" => $paymentStats,
            "collectionData" => $collectionData
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
    
    // Check if booking money transaction exists in database
    $hasBookingMoneyInDB = $this->clientModel->hasBookingMoneyInDatabase($bookingId);
    
    // If booking money doesn't exist in transactions, add it from agreement
    if (!$hasBookingMoneyInDB && !empty($clientDetails['booking_money'])) {
        $bookingMoneyTransaction = $this->clientModel->getBookingMoneyTransaction($bookingId);
        
        // If still not found, create a display entry from the agreement
        if (!$bookingMoneyTransaction) {
            $bookingMoneyTransaction = [
                'id' => 'booking-' . $bookingId,
                'payment_date' => $clientDetails['booking_date'],
                'formatted_date' => date('d M, Y', strtotime($clientDetails['booking_date'])),
                'payment_type' => 'booking_money',
                'installment_label' => 'BOOKING',
                'amount' => $clientDetails['booking_money'],
                'land_share_amount' => $clientDetails['booking_money'],
                'service_fee' => 0,
                'payment_status' => 'paid',
                'status_class' => 'bg-success',
                'status_text' => 'Paid',
                'client_name' => $clientDetails['client_name'],
                'project_name' => $clientDetails['project_name'],
                'flat_no' => $clientDetails['flat_no'],
                'unit_name' => $clientDetails['unit_name'],
                'created_by_name' => $clientDetails['agent_name'] ?? 'System'
            ];
        }
        
        // Add booking money transaction at the beginning
        array_unshift($transactions, $bookingMoneyTransaction);
    }
    
    $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($bookingId);

    $this->view("ced/clients-cash-in-history-view", [
        "current_route" => "clients-cash-in-history-view",
        "page_title" => "Transaction History - " . $clientDetails['client_name'],
        "clientDetails" => $clientDetails,
        "transactions" => $transactions,
        "paymentSummary" => $paymentSummary
    ]);
}
/**
 * View booking money invoice/receipt
 */
public function viewBookingInvoice($bookingId = null)
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

    // Check if there's an actual booking money payment in the database
    $bookingMoneyPayment = $this->clientModel->getBookingMoneyTransaction($bookingId);
    
    if ($bookingMoneyPayment) {
        // If there's an actual payment record, redirect to the regular invoice
        header("Location: {$GLOBALS['base_url']}/payment-invoice?payment_id=" . $bookingMoneyPayment['id']);
        exit;
    }

    // Create a payment object from the booking agreement
    $payment = [
        'id' => 'booking-' . $bookingId,
        'payment_date' => $clientDetails['booking_date'],
        'payment_type' => 'booking_money',
        'amount' => $clientDetails['booking_money'],
        'land_share_amount' => $clientDetails['booking_money'],
        'service_fee' => 0,
        'payment_status' => 'paid',
        'transaction_ref' => '',
        'bank_name' => '',
        'bank_account_no' => '',
        'cheque_no' => '',
        'online_type' => '',
        'installment_label' => 'BOOKING',
        'payment_details' => 'Booking Agreement',
        'client_name' => $clientDetails['client_name'],
        'project_name' => $clientDetails['project_name'],
        'flat_no' => $clientDetails['flat_no'],
        'unit_name' => $clientDetails['unit_name'],
        'flat_id' => $clientDetails['flat_id'],
        'floor_no' => $clientDetails['flat_no']
    ];

    // Use the same invoice view
    $root = dirname(dirname(__DIR__));
    $viewPath = $root . '/app/views/accounts/cashIn/invoice.php';
    
    if (!file_exists($viewPath)) {
        die("View file not found at: " . $viewPath);
    }
    
    include $viewPath;
    exit;
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
        
        // CHECK 0: Ensure land share is completed before other payments
        $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($bookingId);
        if ($paymentType !== 'land_share' && $paymentType !== 'booking_money' && empty($paymentSummary['is_land_share_completed'])) {
            setError("Land share must be fully paid before adding other payments.");
            header("Location: {$GLOBALS['base_url']}/client-cash-in-form?booking_id={$bookingId}");
            exit;
        }

        // SIMPLE CHECK: If EMI is selected, verify registration is paid
        if ($paymentType === 'emi') {
            if (!$this->clientModel->isRegistrationPaid($bookingId)) {
                setError("Registration payment must be made before adding EMI transactions.");
                header("Location: {$GLOBALS['base_url']}/client-cash-in-form?booking_id={$bookingId}");
                exit;
            }
        }

        // Handle multi-file proof uploads (before insert so paths are stored atomically)
        $uploadedPaths = [];
        if (!empty($_FILES['proof_files']['name'][0])) {
            $uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/transactions/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
            $maxSize = 5 * 1024 * 1024;
            foreach ($_FILES['proof_files']['tmp_name'] as $idx => $tmpName) {
                if (!is_uploaded_file($tmpName)) continue;
                if (!in_array(mime_content_type($tmpName), $allowedTypes)) continue;
                if ($_FILES['proof_files']['size'][$idx] > $maxSize) continue;
                $ext = pathinfo(basename($_FILES['proof_files']['name'][$idx]), PATHINFO_EXTENSION);
                $name = 'proof_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($tmpName, $uploadDir . $name)) {
                    $uploadedPaths[] = 'uploads/transactions/' . $name;
                }
            }
        }

        // Prepare transaction data
        $transactionData = [
            'booking_id'     => $bookingId,
            'payment_type'   => $paymentType,
            'amount'         => $_POST['amount'] ?? 0,
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'payment_status' => $_POST['payment_status'] ?? 'paid',
            'payment_date'   => $_POST['payment_date'] ?? date('Y-m-d'),
            'transaction_ref'=> $_POST['transaction_ref'] ?? null,
            'bank_id'        => !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null,
            'bank_account_no'=> $_POST['bank_account_no'] ?? null,
            'cheque_no'      => $_POST['cheque_no'] ?? null,
            'online_type'    => $_POST['online_type'] ?? null,
            'attachment_paths' => $uploadedPaths ?: null,
            'created_by'     => $_SESSION['user']['id'] ?? 1
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
        
        error_log("Session User ID: " . ($_SESSION['user']['id'] ?? 'null'));

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
            'booking' as payment_method,
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
public function getClientPaymentHistory()
{
    $bookingId = $_GET['booking_id'] ?? 0;
    
    $accountModel = new AccountClient();
    
    // Get client details with land share calculations
    $clientDetails = $accountModel->getClientByBookingId($bookingId);
    
    // Get payment summary with land share
    $paymentSummary = $accountModel->getPaymentSummaryByBookingId($bookingId);
    
    // Get transactions
    $transactions = $accountModel->getTransactionsByBookingId($bookingId);
    
    // Prepare data for view
    $data = [
        'client' => $clientDetails,
        'summary' => $paymentSummary,
        'transactions' => $transactions,
        'remaining_land_share' => $paymentSummary['remaining_land_share'],
        'is_land_share_completed' => $paymentSummary['is_land_share_completed']
    ];
    
    // Return JSON or load view
    echo json_encode($data);
}
// Add this method to your AccountClientController class
public function addPayment()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = $_POST;
        
        $accountModel = new AccountClient();
        
        // Prepare transaction data
        $transactionData = [
            'booking_id' => $data['booking_id'],
            'payment_type' => $data['payment_type'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'payment_status' => $data['payment_status'],
            'payment_date' => $data['payment_date'],
            'transaction_ref' => $data['transaction_ref'] ?? '',
            'bank_name' => $data['bank_name'] ?? '',
            'bank_account_no' => $data['bank_account_no'] ?? '',
            'cheque_no' => $data['cheque_no'] ?? '',
            'online_type' => $data['online_type'] ?? '',
            'created_by' => $_SESSION['user']['id'] ?? 1
        ];
        
        // For land share payments, validate remaining amount
        if ($data['payment_type'] === 'land_share') {
            $remainingLandShare = $accountModel->getRemainingLandShare($data['booking_id']);
            
            if ($remainingLandShare <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Land share payment is already completed!'
                ]);
                return;
            }
            
            if ($data['amount'] > $remainingLandShare) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Land share amount cannot exceed remaining: ৳' . number_format($remainingLandShare, 2)
                ]);
                return;
            }
        }
        
        // Prepare EMI schedule data if applicable
        $emiScheduleData = null;
        if ($data['payment_type'] === 'emi') {
            $emiScheduleData = [
                'due_emi_amount' => $data['due_emi_amount'] ?? 0,
                'due_date' => $data['due_date'] ?? date('Y-m-d')
            ];
        }
        
        $result = $accountModel->addTransaction($transactionData, $emiScheduleData);
        
        echo json_encode($result);
    }
}
public function getPaymentForm()
{
    $bookingId = $_GET['booking_id'] ?? 0;
    
    $accountModel = new AccountClient();
    
    // Get client details
    $client = $accountModel->getClientByBookingId($bookingId);
    
    // Get remaining land share
    $remainingLandShare = $accountModel->getRemainingLandShare($bookingId);
    $isLandShareCompleted = $accountModel->isLandShareCompleted($bookingId);
    
    // Prepare form data
    $formData = [
        'client' => $client,
        'remaining_land_share' => $remainingLandShare,
        'is_land_share_completed' => $isLandShareCompleted,
        'max_land_share_amount' => $isLandShareCompleted ? 0 : $remainingLandShare
    ];
    
    // Load view with form data
    // echo json_encode($formData);
}
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

    /**
     * Verify or reject a payment (Accounts team action)
     * POST: payment_id, action (verify|reject), rejection_reason (optional)
     */
    public function verifyPayment()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $paymentId      = (int)($_POST['payment_id'] ?? 0);
        $action         = $_POST['action'] ?? '';          // 'verify' or 'reject'
        $userId         = $_SESSION['user']['id'] ?? 0;
        $rejectionReason = trim($_POST['rejection_reason'] ?? '');

        if (!$paymentId || !in_array($action, ['verify', 'reject'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        if ($action === 'reject' && $rejectionReason === '') {
            echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
            exit;
        }

        $newStatus = ($action === 'verify') ? 'verified' : 'rejected';

        // Build the rejection_reason column dynamically (only relevant for reject action)
        $rejectionReasonSql = ($action === 'reject') ? ', rejection_reason = ?' : '';

        $stmt = $this->db->prepare("
            UPDATE lead_payments
            SET    verification_status = ?,
                   verified_by         = ?,
                   verified_at         = NOW()
                   {$rejectionReasonSql}
            WHERE  id = ?
            AND    verification_status = 'pending'
        ");

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'DB prepare error']);
            exit;
        }

        if ($action === 'reject') {
            $stmt->bind_param("sisi", $newStatus, $userId, $rejectionReason, $paymentId);
        } else {
            $stmt->bind_param("sii", $newStatus, $userId, $paymentId);
        }

        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
            exit;
        }

        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Payment not found or already processed']);
            exit;
        }

        $label = ($action === 'verify') ? 'verified' : 'rejected';
        echo json_encode(['success' => true, 'message' => "Payment {$label} successfully", 'status' => $newStatus]);
        exit;
    }

    /**
     * AJAX: Get all active banks for dynamic dropdown
     */
    public function getBanks()
    {
        header('Content-Type: application/json');
        $stmt = $this->db->prepare("
            SELECT id, bank_name, branch_name, routing_number
            FROM   banks
            WHERE  status = 'active'
            ORDER BY bank_name, branch_name
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $banks  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        echo json_encode(['success' => true, 'banks' => $banks]);
        exit;
    }

    /**
     * Handle multi-file proof-of-payment upload for a lead_payment record
     * Called after addTransaction saves the payment, or as a standalone AJAX call.
     */
    public function uploadTransactionProof()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $paymentId = (int)($_POST['payment_id'] ?? 0);
        if (!$paymentId) {
            echo json_encode(['success' => false, 'message' => 'Missing payment ID']);
            exit;
        }

        $uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/transactions/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadedPaths = [];
        $files = $_FILES['proof_files'] ?? null;

        if (!$files || empty($files['name'][0])) {
            echo json_encode(['success' => false, 'message' => 'No files received']);
            exit;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        $maxSize      = 5 * 1024 * 1024; // 5 MB

        foreach ($files['tmp_name'] as $idx => $tmpName) {
            if (!is_uploaded_file($tmpName)) continue;

            $mimeType = mime_content_type($tmpName);
            if (!in_array($mimeType, $allowedTypes)) continue;
            if ($files['size'][$idx] > $maxSize) continue;

            $originalName = basename($files['name'][$idx]);
            $safeExt      = pathinfo($originalName, PATHINFO_EXTENSION);
            $uniqueName   = 'proof_' . $paymentId . '_' . uniqid() . '.' . $safeExt;
            $destination  = $uploadDir . $uniqueName;

            if (move_uploaded_file($tmpName, $destination)) {
                $uploadedPaths[] = 'uploads/transactions/' . $uniqueName;
            }
        }

        if (empty($uploadedPaths)) {
            echo json_encode(['success' => false, 'message' => 'No valid files uploaded']);
            exit;
        }

        // Merge with any existing paths
        $existingRow = $this->db->query("SELECT attachment_paths FROM lead_payments WHERE id = {$paymentId}");
        $existing    = $existingRow ? $existingRow->fetch_assoc() : null;
        $allPaths    = [];
        if ($existing && !empty($existing['attachment_paths'])) {
            $decoded = json_decode($existing['attachment_paths'], true);
            if (is_array($decoded)) $allPaths = $decoded;
        }
        $allPaths = array_merge($allPaths, $uploadedPaths);

        $jsonPaths = json_encode($allPaths);
        $stmt = $this->db->prepare("UPDATE lead_payments SET attachment_paths = ? WHERE id = ?");
        $stmt->bind_param("si", $jsonPaths, $paymentId);
        $stmt->execute();

        echo json_encode(['success' => true, 'paths' => $uploadedPaths, 'all_paths' => $allPaths]);
        exit;
    }
}
