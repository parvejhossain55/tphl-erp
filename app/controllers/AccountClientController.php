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
        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int) $_GET['project_id'] : null;
        $verificationStatus = isset($_GET['verification_status']) && $_GET['verification_status'] !== '' ? $_GET['verification_status'] : null;

        // Get all clients (unique leads) with filters
        $clients = $this->clientModel->getAllClients($selectedProjectId, $verificationStatus);

        // Get payment statistics
        $paymentStats = $this->clientModel->getPaymentStatistics($selectedProjectId);

        // Get Collection Summary & Project Breakdown
        $collectionStats = $this->clientModel->getCollectionStatsSummary($selectedProjectId);
        $projectCollectionBreakdown = $this->clientModel->getProjectCollectionBreakdown();

        // Get all projects for dropdown
        $projects = $this->clientModel->getProjectsForCED();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->view("accounts/cashIn/clients", [
            "current_route" => "clients-cash-in",
            "page_title" => "Clients Management - Cash In",
            "clients" => $clients,
            "paymentStats" => $paymentStats,
            "collectionStats" => $collectionStats,
            "projectCollectionBreakdown" => $projectCollectionBreakdown,
            "projects" => $projects,
            "selectedProjectId" => $selectedProjectId,
            "selectedVerificationStatus" => $verificationStatus
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

    if (!empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1) {
        require_once dirname(__DIR__) . '/models/AccountShopClient.php';
        $this->clientModel = new AccountShopClient($this->db);
        $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
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
    $paymentSummary  = $this->clientModel->getPaymentSummaryByBookingId($bookingId);
    $outstandingDues = $this->clientModel->getOutstandingEmiDues($bookingId);

    // Get refund details
    $refundDetails = null;
    $refundPaidAmount = 0;
    $refundStmt = $this->db->prepare("SELECT * FROM client_refund_requests WHERE booking_id = ? AND status != 'rejected' ORDER BY id DESC LIMIT 1");
    if ($refundStmt) {
        $refundStmt->bind_param('i', $bookingId);
        $refundStmt->execute();
        $refundRes = $refundStmt->get_result();
        $refundDetails = $refundRes ? $refundRes->fetch_assoc() : null;
        
        if ($refundDetails && !empty($refundDetails['cashout_request_id'])) {
            $cpStmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) as paid_amt FROM cashout_payments WHERE cashout_request_id = ?");
            if ($cpStmt) {
                $cpStmt->bind_param('i', $refundDetails['cashout_request_id']);
                $cpStmt->execute();
                $cpRes = $cpStmt->get_result();
                $refundPaidAmount = (float)($cpRes->fetch_assoc()['paid_amt'] ?? 0);
            }
        }
    }

    $virtualEmiSchedule = $this->clientModel->getVirtualEmiSchedule($bookingId);

    $this->view("accounts/cashIn/clients-cash-in-history", [
        "current_route"   => "clients-cash-in-history",
        "page_title"      => "Transaction History - " . $clientDetails['client_name'],
        "clientDetails"   => $clientDetails,
        "transactions"    => $transactions,
        "virtualEmiSchedule" => $virtualEmiSchedule,
        "paymentSummary"  => $paymentSummary,
        "outstandingDues" => $outstandingDues,
        "refundDetails"   => $refundDetails,
        "refundPaidAmount"=> $refundPaidAmount,
        "accountModel"    => $this->clientModel
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

        foreach ($lands as &$land) {
            $schedule = $this->clientModel->getVirtualEmiSchedule($land['booking_id']);
            $land['current_month_status'] = $this->calculateCurrentMonthStatus($schedule, $land['last_payment_date'] ?? null);
            $land['total_emi_count'] = count($schedule) > 0 ? count($schedule) : 36;
            $land['total_followup_count'] = $this->clientModel->getTotalFollowupCount($land['booking_id']);
        }

        echo json_encode([
            'success' => true,
            'client' => $clientDetails,
            'lands' => $lands
        ]);
        exit;
    }

    /**
     * AJAX: Get full client profile (lead + nominee) by lead_id
     */
    public function getClientProfile()
    {
        $leadId = $_GET['lead_id'] ?? null;
        if (!$leadId) {
            echo json_encode(['success' => false, 'message' => 'No lead ID provided']);
            exit;
        }

        $stmt = $this->clientModel->getDb()->prepare("
            SELECT l.*
            FROM leads l
            WHERE l.id = ?
        ");
        $stmt->bind_param('i', $leadId);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result ? $result->fetch_assoc() : null;

        if (!$profile) {
            echo json_encode(['success' => false, 'message' => 'Client not found']);
            exit;
        }

        echo json_encode(['success' => true, 'profile' => $profile]);
        exit;
    }

    public function indexView()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check for project and verification status filters
        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int) $_GET['project_id'] : null;
        $verificationStatus = isset($_GET['verification_status']) && $_GET['verification_status'] !== '' ? $_GET['verification_status'] : null;

        // Get all clients, optionally filtered by project and verification status
        $clients = $this->clientModel->getAllClients($selectedProjectId, $verificationStatus);

        // Get payment statistics
        $paymentStats = $this->clientModel->getPaymentStatistics($selectedProjectId);

        // Get Collection Summary & Project Breakdown
        $collectionStats = $this->clientModel->getCollectionStatsSummary($selectedProjectId);
        $projectCollectionBreakdown = $this->clientModel->getProjectCollectionBreakdown();

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

        foreach ($clients as &$client) {
            $schedule = $this->clientModel->getVirtualEmiSchedule($client['booking_id']);
            $client['current_month_status'] = $this->calculateCurrentMonthStatus($schedule, $client['last_payment_date'] ?? null);
            $client['total_emi_count'] = count($schedule) > 0 ? count($schedule) : 36;
            $client['total_followup_count'] = $this->clientModel->getTotalFollowupCount($client['booking_id']);
        }

        $this->view("ced/clients-cashin-view", [
            "current_route" => "clients-cashin-view",
            "page_title" => "Clients Cash In" . ($selectedProjectName ? " - " . $selectedProjectName : ""),
            "clients" => $clients,
            "paymentStats" => $paymentStats,
            "collectionStats" => $collectionStats,
            "projectCollectionBreakdown" => $projectCollectionBreakdown,
            "projects" => $projects,
            "selectedProjectId" => $selectedProjectId,
            "selectedVerificationStatus" => $verificationStatus,
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

        foreach ($clients as &$client) {
            $schedule = $this->clientModel->getVirtualEmiSchedule($client['booking_id']);
            $client['current_month_status'] = $this->calculateCurrentMonthStatus($schedule, $client['last_payment_date'] ?? null);
        }

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

    public function cedProjectNotices()
    {
        global $config;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $apiBaseUrl = $config['notices_api_base_url'] ?? 'https://api.dpremiumhomes.com/api';

        $this->view("ced/ced-project-notices", [
            "current_route" => "ced-project-notices",
            "page_title"    => "Project Notices",
            "api_base_url"  => $apiBaseUrl,
        ]);
    }
    
    private function calculateCurrentMonthStatus($schedule, $lastPaymentDate = null) {
        if (empty($schedule)) return 'N/A';
        $currentMonthStatus = 'N/A';
        $currentMonth = (int)date('Ym');
        $hasDue = false;
        
        foreach ($schedule as $emi) {
            if ($emi['maturity_status'] === 'due') {
                $hasDue = true;
            }
            $dueMonth = (int)date('Ym', strtotime($emi['due_date']));
            if ($dueMonth === $currentMonth) {
                $currentMonthStatus = $emi['maturity_status'];
            }
        }
        
        if ($hasDue) return 'due';
        if ($currentMonthStatus !== 'N/A') return $currentMonthStatus;
        
        // If there's no current month schedule, check if a pre-EMI payment was made this month
        if ($lastPaymentDate && (int)date('Ym', strtotime($lastPaymentDate)) === $currentMonth) {
            return 'paid';
        }
        
        return 'N/A';
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
        $this->view("ced/clients-cash-in-history-view", [
            "current_route" => "clients-cash-in-history-view",
            "page_title" => "Transaction History - Error",
            "clientDetails" => null,
            "transactions" => [],
            "paymentSummary" => [],
            "error_message" => "Booking not found or Configuration error. The land share or client data could not be resolved."
        ]);
        return;
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

    // Get refund details for CED view
    $refundDetails = null;
    $refundPaidAmount = 0;
    $refundStmt = $this->db->prepare("SELECT * FROM client_refund_requests WHERE booking_id = ? AND status != 'rejected' ORDER BY id DESC LIMIT 1");
    if ($refundStmt) {
        $refundStmt->bind_param('i', $bookingId);
        $refundStmt->execute();
        $refundRes = $refundStmt->get_result();
        $refundDetails = $refundRes ? $refundRes->fetch_assoc() : null;
        
        if ($refundDetails && !empty($refundDetails['cashout_request_id'])) {
            $cpStmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) as paid_amt FROM cashout_payments WHERE cashout_request_id = ?");
            if ($cpStmt) {
                $cpStmt->bind_param('i', $refundDetails['cashout_request_id']);
                $cpStmt->execute();
                $cpRes = $cpStmt->get_result();
                $refundPaidAmount = (float)($cpRes->fetch_assoc()['paid_amt'] ?? 0);
            }
        }
    }

    $virtualEmiSchedule = $this->clientModel->getVirtualEmiSchedule($bookingId);

    $bRes = $this->db->query("SELECT id, bank_name, branch_name, account_number FROM banks WHERE status = 'active' ORDER BY bank_name");
    $banks = $bRes ? $bRes->fetch_all(MYSQLI_ASSOC) : [];

    $this->view("ced/clients-cash-in-history-view", [
        "current_route" => "clients-cash-in-history-view",
        "page_title" => "Transaction History - " . $clientDetails['client_name'],
        "clientDetails" => $clientDetails,
        "transactions" => $transactions,
        "virtualEmiSchedule" => $virtualEmiSchedule,
        "paymentSummary" => $paymentSummary,
        "refundDetails"   => $refundDetails,
        "refundPaidAmount"=> $refundPaidAmount,
        "banks"           => $banks,
        "accountModel"    => $this->clientModel
    ]);
}

/**
 * CED: Show add-transaction form (Development Charges & EMI only)
 */
public function showCedTransactionForm($bookingId = null)
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!$bookingId) {
        $bookingId = $_GET['booking_id'] ?? null;
    }

    if (!$bookingId) {
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

    if (!empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1) {
        require_once dirname(__DIR__) . '/models/AccountShopClient.php';
        $this->clientModel = new AccountShopClient($this->db);
        $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
    }

    $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($bookingId);
    $outstandingDues = $this->clientModel->getOutstandingEmiDues($bookingId);
    $virtualEmiSchedule = method_exists($this->clientModel, 'getVirtualEmiSchedule') 
        ? $this->clientModel->getVirtualEmiSchedule($bookingId) 
        : [];

    $this->view("ced/ced-cash-in-form", [
        "current_route"      => "ced-add-transaction-form",
        "page_title"         => "CED Add Transaction - " . $clientDetails['client_name'],
        "clientDetails"      => $clientDetails,
        "bookingId"          => $bookingId,
        "paymentSummary"     => $paymentSummary,
        "outstandingDues"    => $outstandingDues,
        "virtualEmiSchedule" => $virtualEmiSchedule,
        "isShop"             => (!empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1)
    ]);
}

/**
 * CED: Process add-transaction POST (restricted to development_charge & emi)
 */
public function addCedTransaction()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: {$GLOBALS['base_url']}/clients-cashin-view");
        exit;
    }

    $bookingId = $_POST['booking_id'] ?? null;

    if (!$bookingId) {
        setError("Invalid booking ID");
        header("Location: {$GLOBALS['base_url']}/clients-cashin-view");
        exit;
    }

    if (session_status() === PHP_SESSION_NONE) session_start();

    $paymentType = $_POST['payment_type'] ?? '';

    $paymentType = $_POST['payment_type'] ?? '';

    // CED can only add development_charge, shop_service_charge, emi, emi_due_settlement, or land_share
    if (!in_array($paymentType, ['development_charge', 'shop_service_charge', 'emi', 'emi_due_settlement', 'land_share'])) {
        setError("CED can only add Development Charges, Shop Service Charges, EMI, EMI Settlement, or Land Share transactions.");
        header("Location: {$GLOBALS['base_url']}/ced-add-transaction-form?booking_id={$bookingId}");
        exit;
    }

    // CHECK: EMI due settlement requires a parent payment ID
    if ($paymentType === 'emi_due_settlement') {
        $parentPaymentId = !empty($_POST['parent_payment_id']) ? (int)$_POST['parent_payment_id'] : 0;
        if (!$parentPaymentId) {
            setError("EMI settlement requires selecting the original EMI payment.");
            header("Location: {$GLOBALS['base_url']}/ced-add-transaction-form?booking_id={$bookingId}");
            exit;
        }
    }

    // Land Share must be completed (Disabled as requested)
    $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($bookingId);
    /*
    if (empty($paymentSummary['is_land_share_completed'])) {
        setError("Land share payment must be fully completed before adding CED transactions.");
        header("Location: {$GLOBALS['base_url']}/ced-add-transaction-form?booking_id={$bookingId}");
        exit;
    }
    */



    // Handle multi-file proof uploads
    $uploadedPaths = [];
    if (!empty($_FILES['proof_files']['name'][0])) {
        $uploadDir = dirname(dirname(__DIR__)) . '/public/uploads/transactions/';
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

    // Handle waiver proof upload
    $waiverAttachmentPath = null;
    if (!empty($_FILES['waiver_file']['name'])) {
        $waiverDir = dirname(dirname(__DIR__)) . '/public/uploads/waivers/';
        if (!is_dir($waiverDir)) mkdir($waiverDir, 0755, true);
        $tmpName = $_FILES['waiver_file']['tmp_name'];
        if (is_uploaded_file($tmpName)) {
            $ext = pathinfo(basename($_FILES['waiver_file']['name']), PATHINFO_EXTENSION);
            $wName = 'waiver_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($tmpName, $waiverDir . $wName)) {
                $waiverAttachmentPath = 'uploads/waivers/' . $wName;
            }
        }
    }

    if (!empty($_POST['no_penalty'])) {
        $penaltyAmount = 0;
        $penaltyWaived = 0;
        $penaltyPaid = 0;
        $waiverReason = null;
        $waiverStatus = 'none';
    } else {
        $penaltyAmount = isset($_POST['penalty_amount']) ? (float)$_POST['penalty_amount'] : 0;
        $penaltyWaived = isset($_POST['penalty_waived']) ? (float)$_POST['penalty_waived'] : 0;
        $penaltyPaid   = max(0, $penaltyAmount - $penaltyWaived);
        $waiverReason  = $_POST['waiver_reason'] ?? null;
        $waiverStatus  = (!empty($_POST['apply_waiver']) && $penaltyWaived > 0) ? 'pending' : 'none';
    }

    // Prepare transaction data
    $transactionData = [
        'booking_id'        => $bookingId,
        'payment_type'      => $paymentType,
        'amount'            => $_POST['amount'] ?? 0,
        'payment_method'    => $_POST['payment_method'] ?? 'cash',
        'payment_status'    => 'pending', // Always pending until verified
        'payment_date'      => $_POST['payment_date'] ?? date('Y-m-d'),
        'transaction_ref'   => $_POST['transaction_ref'] ?? null,
        'bank_id'           => !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null,
        'bank_account_no'   => $_POST['bank_account_no'] ?? null,
        'cheque_no'         => $_POST['cheque_no'] ?? null,
        'online_type'       => $_POST['online_type'] ?? null,
        'attachment_paths'   => $uploadedPaths ?: null,
        'bulk_emi_discount' => isset($_POST['bulk_emi_discount']) ? (float)$_POST['bulk_emi_discount'] : 0,
        'due_emi_amount'     => isset($_POST['due_emi_amount']) ? (float)$_POST['due_emi_amount'] : 0,
        'parent_payment_id'  => !empty($_POST['parent_payment_id']) ? (int)$_POST['parent_payment_id'] : null,
        'settlement_type'    => !empty($_POST['settlement_type']) ? $_POST['settlement_type'] : null,
        'penalty_amount'    => $penaltyAmount,
        'penalty_paid'      => $penaltyPaid,
        'penalty_waived'    => $penaltyWaived,
        'waiver_reason'     => $waiverReason,
        'waiver_attachment' => $waiverAttachmentPath,
        'waiver_status'     => $waiverStatus,
        'created_by'        => $_SESSION['user']['id'] ?? 1
    ];

    // EMI schedule data
    $emiScheduleData = null;
    if ($paymentType === 'emi') {
        $emiScheduleData = [
            'due_emi_amount' => $_POST['due_emi_amount'] ?? 0
        ];

        if ($transactionData['payment_status'] === 'partially_paid') {
            if ($emiScheduleData['due_emi_amount'] <= 0) {
                setError("Partially Paid status requires a due amount greater than 0");
                header("Location: {$GLOBALS['base_url']}/ced-add-transaction-form?booking_id={$bookingId}");
                exit;
            }
        }
    }

    $isTestingMode = !empty($_POST['is_testing_mode']);

    require_once dirname(__DIR__) . '/services/SmsService.php';
    $smsService = new SmsService($this->db);

    if (!$isTestingMode) {
        $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
        $clientPhone   = !empty($clientDetails['phone']) ? trim($clientDetails['phone']) : (!empty($clientDetails['phone_number']) ? trim($clientDetails['phone_number']) : '');
        $phoneCheck    = $smsService->validatePhoneNumber($clientPhone);

        if (!$phoneCheck['valid']) {
            // Do not block payment. It will gracefully show a warning instead.
            // setError("Cannot process payment: " . $phoneCheck['message'] . ". Please update the client's phone number first or enable Testing Mode.");
            // header("Location: {$GLOBALS['base_url']}/ced-add-transaction-form?booking_id={$bookingId}");
            // exit;
        }
    }

    // Route to correct model method
    if ($transactionData['payment_type'] === 'emi_due_settlement') {
        // Use addTransaction (not WithEMI) so settlement_type & parent_payment_id are handled
        $result = $this->clientModel->addTransaction($transactionData, null);
    } else {
        $result = $this->clientModel->addTransactionWithEMI($transactionData, $emiScheduleData);
    }

    if ($result['success']) {
        $smsMsg = "";
        if (!empty($result['payment_id'])) {
            $smsResult = $smsService->sendPaymentReceivedSms($result['payment_id'], $isTestingMode);
            if ($isTestingMode) {
                $smsMsg = " (Testing Mode: SMS Skipped)";
            } elseif (!empty($smsResult['success'])) {
                $smsMsg = " & SMS dispatched to client!";
            } else {
                $errMsg = !empty($smsResult['response']) ? $smsResult['response'] : ($smsResult['message'] ?? 'Gateway Error');
                $smsMsg = " (Warning: SMS dispatch failed: " . htmlspecialchars($errMsg) . ")";
            }
        }
        
        setSuccess($result['message'] . " — Pending verification by Accounts." . $smsMsg);
        header("Location: {$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}");
        exit;
    } else {
        setError($result['message']);
        header("Location: {$GLOBALS['base_url']}/ced-add-transaction-form?booking_id={$bookingId}");
        exit;
    }
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

    // Get management users for handover dropdown
    $managementUsers = [];
    $mgmtQuery = $this->db->query("SELECT id, full_name, email FROM users WHERE role_id IN (1, 3)");
    if ($mgmtQuery) {
        while ($row = $mgmtQuery->fetch_assoc()) {
            $managementUsers[] = $row;
        }
    }

    $this->view("accounts/cashIn/client-cash-in-form", [
        "current_route" => "client-cash-in-form",
        "page_title" => "Add Transaction - " . $clientDetails['client_name'],
        "clientDetails" => $clientDetails,
        "bookingId" => $bookingId,
        "registrationPaid" => $registrationPaid,
        "managementUsers" => $managementUsers
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
        
        // CHECK: EMI due settlement requires a parent payment ID
        if ($paymentType === 'emi_due_settlement') {
            $parentPaymentId = !empty($_POST['parent_payment_id']) ? (int)$_POST['parent_payment_id'] : 0;
            if (!$parentPaymentId) {
                setError("EMI settlement requires selecting the original EMI payment.");
                header("Location: {$GLOBALS['base_url']}/client-cash-in-form?booking_id={$bookingId}");
                exit;
            }
        }

        // Handle multi-file proof uploads (before insert so paths are stored atomically)
        $uploadedPaths = [];
        if (!empty($_FILES['proof_files']['name'][0])) {
            $uploadDir = dirname(dirname(__DIR__)) . '/public/uploads/transactions/';
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
            'booking_id'       => $bookingId,
            'payment_type'     => $paymentType,
            'amount'           => $_POST['amount'] ?? 0,
            'payment_method'   => $_POST['payment_method'] ?? 'cash',
            'payment_status'   => 'pending', // Always pending until verified
            'payment_date'     => $_POST['payment_date'] ?? date('Y-m-d'),
            'transaction_ref'  => $_POST['transaction_ref'] ?? null,
            'bank_id'          => !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null,
            'bank_account_no'  => $_POST['bank_account_no'] ?? null,
            'cheque_no'        => $_POST['cheque_no'] ?? null,
            'online_type'      => $_POST['online_type'] ?? null,
            'attachment_paths' => $uploadedPaths ?: null,
            'due_emi_amount'   => isset($_POST['due_emi_amount']) ? (float)$_POST['due_emi_amount'] : 0,
            'parent_payment_id'=> !empty($_POST['parent_payment_id']) ? (int)$_POST['parent_payment_id'] : null,
            'settlement_type'  => ($paymentType === 'emi_due_settlement') ? 'emi_due_settlement' : null,
            'bulk_emi_discount'=> isset($_POST['bulk_emi_discount']) ? (float)$_POST['bulk_emi_discount'] : 0,
            'handover_to'      => !empty($_POST['handover_to']) ? (int)$_POST['handover_to'] : null,
            'created_by'       => $_SESSION['user']['id'] ?? 1
        ];

        // EMI schedule data (only if payment type is EMI, not settlement)
        $emiScheduleData = null;
        if ($transactionData['payment_type'] === 'emi') {
            $emiScheduleData = [
                'due_emi_amount' => $transactionData['due_emi_amount']
            ];
        }

        $isTestingMode = !empty($_POST['is_testing_mode']);

        require_once dirname(__DIR__) . '/services/SmsService.php';
        $smsService = new SmsService($this->db);

        if (!$isTestingMode) {
            $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
            $clientPhone   = !empty($clientDetails['phone']) ? trim($clientDetails['phone']) : (!empty($clientDetails['phone_number']) ? trim($clientDetails['phone_number']) : '');
            $phoneCheck    = $smsService->validatePhoneNumber($clientPhone);

            if (!$phoneCheck['valid']) {
                // Do not block payment. It will gracefully show a warning instead.
                // setError("Cannot process payment: " . $phoneCheck['message'] . ". Please update the client's phone number first or enable Testing Mode.");
                // header("Location: {$GLOBALS['base_url']}/client-cash-in-form?booking_id={$bookingId}");
                // exit;
            }
        }

        // Route to correct model method
        if ($transactionData['payment_type'] === 'emi_due_settlement') {
            // Use addTransaction (not WithEMI) so settlement_type & parent_payment_id are handled
            $result = $this->clientModel->addTransaction($transactionData, null);
        } else {
            $result = $this->clientModel->addTransactionWithEMI($transactionData, $emiScheduleData);
        }

        if ($result['success']) {
            $smsMsg = "";
            if (!empty($result['payment_id'])) {
                $smsResult = $smsService->sendPaymentReceivedSms($result['payment_id'], $isTestingMode);
                if ($isTestingMode) {
                    $smsMsg = " (Testing Mode: SMS Skipped)";
                } elseif (!empty($smsResult['success'])) {
                    $smsMsg = " & SMS dispatched to client (" . htmlspecialchars($clientPhone ?? '') . ")!";
                } else {
                    $errMsg = !empty($smsResult['response']) ? $smsResult['response'] : ($smsResult['message'] ?? 'Gateway Error');
                    $smsMsg = " (Warning: SMS dispatch failed for " . htmlspecialchars($clientPhone ?? '') . ": " . htmlspecialchars($errMsg) . ")";
                }
            }
            setSuccess($result['message'] . $smsMsg);
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

        // Get management users for handover dropdown
        $managementUsers = [];
        $mgmtQuery = $this->db->query("SELECT id, full_name, email FROM users WHERE role_id IN (1, 3)");
        if ($mgmtQuery) {
            while ($row = $mgmtQuery->fetch_assoc()) {
                $managementUsers[] = $row;
            }
        }

        // DEBUG: Check what data is being fetched
        error_log("Transaction Data: " . print_r($transaction, true));
        error_log("EMI Schedule: " . print_r($emiSchedule, true));

        $this->view("accounts/cashIn/client-cash-in-edit-form", [
            "current_route" => "client-cash-in-edit-form",
            "page_title" => "Edit Transaction - " . $clientDetails['client_name'],
            "clientDetails" => $clientDetails,
            "transactionDetails" => $transaction,
            "emiSchedule" => $emiSchedule,
            "managementUsers" => $managementUsers
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

        // ── Handle file uploads: start from existing files sent back via hidden inputs ──
        $mergedPaths = [];
        if (!empty($_POST['existing_files']) && is_array($_POST['existing_files'])) {
            foreach ($_POST['existing_files'] as $ep) {
                $ep = trim($ep);
                if ($ep !== '') $mergedPaths[] = $ep;
            }
        }
        // Append any newly uploaded files
        if (!empty($_FILES['proof_files']['name'][0])) {
            $uploadDir    = dirname(dirname(__DIR__)) . '/public/uploads/transactions/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
            $maxSize      = 5 * 1024 * 1024;
            foreach ($_FILES['proof_files']['tmp_name'] as $idx => $tmpName) {
                if (!is_uploaded_file($tmpName)) continue;
                if (!in_array(mime_content_type($tmpName), $allowedTypes)) continue;
                if ($_FILES['proof_files']['size'][$idx] > $maxSize) continue;
                $ext  = pathinfo(basename($_FILES['proof_files']['name'][$idx]), PATHINFO_EXTENSION);
                $name = 'proof_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($tmpName, $uploadDir . $name)) {
                    $mergedPaths[] = 'uploads/transactions/' . $name;
                }
            }
        }

        $bankId = !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null;
        $bankName = $_POST['bank_name'] ?? null;
        if ($bankId && empty($bankName)) {
            $bRow = $this->db->query("SELECT CONCAT(bank_name, ' - ', branch_name) as label FROM banks WHERE id = {$bankId}");
            if ($bRow) { $bData = $bRow->fetch_assoc(); $bankName = $bData['label'] ?? ''; }
        }

        // Prepare transaction data
        $transactionData = [
            'payment_id'       => $paymentId,
            'booking_id'       => $bookingId,
            'payment_type'     => $_POST['payment_type'] ?? 'emi',
            'amount'           => $_POST['amount'] ?? 0,
            'payment_method'   => $_POST['payment_method'] ?? 'cash',
            'payment_status'   => 'pending', // Always pending until verified
            'payment_date'     => $_POST['payment_date'] ?? date('Y-m-d'),
            'transaction_ref'  => $_POST['transaction_ref'] ?? null,
            'bank_id'          => $bankId,
            'bank_name'        => $bankName,
            'bank_account_no'  => $_POST['bank_account_no'] ?? null,
            'cheque_no'        => $_POST['cheque_no'] ?? null,
            'online_type'      => $_POST['online_type'] ?? null,
            'attachment_paths' => !empty($mergedPaths) ? $mergedPaths : null,
            'updated_by'       => $_SESSION['user']['id'] ?? 1,
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
                    $redirectFail = !empty($_POST['redirect_to']) ? "{$GLOBALS['base_url']}/" . ltrim($_POST['redirect_to'], '/') : "{$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}";
                    header("Location: {$redirectFail}");
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

        // Handle Handover Approval Initiation
        if (!empty($_POST['handover_to'])) {
            $transactionData['handover_to'] = (int)$_POST['handover_to'];
        }

        // Update transaction with EMI schedule if applicable
        $result = $this->clientModel->updateTransactionWithEMI($transactionData, $emiScheduleData);

        error_log("Update Result: " . print_r($result, true));

        $redirectUrl = !empty($_POST['redirect_to']) 
            ? "{$GLOBALS['base_url']}/" . ltrim($_POST['redirect_to'], '/')
            : "{$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}";

        if ($result['success']) {
            setSuccess($result['message'] . " (Payment updated successfully)");
            header("Location: {$redirectUrl}");
            exit;
        } else {
            setError($result['message']);
            header("Location: {$redirectUrl}");
            exit;
        }
    } else {
        setError("Invalid request method");
        header("Location: {$GLOBALS['base_url']}/clients-cash-in");
        exit;
    }
}

    /**
     * Delete a transaction (and its EMI schedule row if applicable).
     * Accessible from both Accounts and CED history pages.
     */
    public function deleteTransaction()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $paymentId = isset($_GET['payment_id']) ? (int) $_GET['payment_id'] : 0;
        $bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;

        // Determine redirect based on role
        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower($_SESSION['user']['role'] ?? '');
        $isCED = ($roleId === 5);
        $isAccounts = in_array($roleId, [1, 3, 6, 30]);
        $isManagementUser = ($roleId === 1 || $roleId === 3 || strpos($roleName, 'management') !== false || strpos($roleName, 'admin') !== false);
        
        $redirectUrl = ($isCED || $isManagementUser)
            ? "{$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}"
            : "{$GLOBALS['base_url']}/clients-cash-in-history?booking_id={$bookingId}";

        if (!$paymentId || !$bookingId) {
            setError("Invalid request: missing payment or booking ID.");
            header("Location: " . ($bookingId ? $redirectUrl : "{$GLOBALS['base_url']}/clients-cashin-view"));
            exit;
        }

        if (!$isCED && !$isAccounts && !$isManagementUser) {
            setError("You do not have permission to delete transactions.");
            header("Location: {$redirectUrl}");
            exit;
        }

        // CED can only delete their own unverified entries
        if ($isCED) {
            $tx = $this->clientModel->getTransactionById($paymentId);
            if (!$tx) {
                setError("Transaction not found.");
                header("Location: {$redirectUrl}");
                exit;
            }
            if (($tx['verification_status'] ?? '') === 'verified') {
                setError("Cannot delete a verified transaction. Contact Accounts.");
                header("Location: {$redirectUrl}");
                exit;
            }
        }

        $result = $this->clientModel->deleteTransactionWithCleanup($paymentId, $bookingId);

        if ($result['success']) {
            setSuccess($result['message']);
        } else {
            setError($result['message']);
        }

        header("Location: {$redirectUrl}");
        exit;
    }

    public function getClientDetails($bookingId)
    {
        header('Content-Type: application/json');
        $client = $this->clientModel->getClientByBookingId($bookingId);
        echo json_encode($client);
        exit;
    }

    /**
     * Delete a full booking transaction and associated payments.
     * Only accessible for Management roles (e.g. Admin, Super Admin, Management)
     */
    public function deleteBooking()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        
        // Allowed roles: 1 (Admin), 3 (Super Admin), 33 (Management)
        if (!in_array($roleId, [1, 3, 33])) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete bookings.']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        // Get JSON payload
        $input = json_decode(file_get_contents('php://input'), true);
        $bookingId = isset($input['booking_id']) ? (int) $input['booking_id'] : 0;

        if (!$bookingId) {
            echo json_encode(['success' => false, 'message' => 'Booking ID is required.']);
            exit;
        }

        // Get the flat_id associated with this booking
        $stmt = $this->db->prepare("SELECT flat_id FROM lead_bookings WHERE id = ?");
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Booking not found.']);
            exit;
        }
        
        $row = $result->fetch_assoc();
        $flatId = $row['flat_id'];
        $stmt->close();

        // Start transaction
        $this->db->begin_transaction();

        try {
            // 1. Delete all payments associated with this booking
            $stmt1 = $this->db->prepare("DELETE FROM lead_payments WHERE booking_id = ?");
            $stmt1->bind_param('i', $bookingId);
            $stmt1->execute();
            $stmt1->close();

            // 2. Delete the main booking
            $stmt2 = $this->db->prepare("DELETE FROM lead_bookings WHERE id = ?");
            $stmt2->bind_param('i', $bookingId);
            $stmt2->execute();
            $stmt2->close();

            // 3. Reset the flat status
            $stmt3 = $this->db->prepare("UPDATE flats SET status = 'available', is_booked = 0 WHERE id = ?");
            $stmt3->bind_param('i', $flatId);
            $stmt3->execute();
            $stmt3->close();

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Booking and all associated payments deleted successfully.']);
            exit;

        } catch (\Exception $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
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

    $paymentIds = array_filter(array_map('trim', explode(',', $paymentId)));
    $payments = [];
    $clientDetails = null;

    foreach ($paymentIds as $pid) {
        $pay = $this->clientModel->getTransactionById($pid);
        if ($pay) {
            if ($pay['payment_type'] === 'emi') {
                $emiSchedule = $this->clientModel->getEMIScheduleByPaymentId($pid);
                if ($emiSchedule && isset($emiSchedule['emi_no'])) {
                    $emiNo = $emiSchedule['emi_no'];
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
                    $pay['installment_label'] = $emiNo . $suffix . ' Installment';
                }
            }
            $payments[] = $pay;
            if (!$clientDetails) {
                $clientDetails = $this->clientModel->getClientByBookingId($pay['booking_id']);
            }
        }
    }

    if (empty($payments)) {
        setError("Payment(s) not found");
        header("Location: {$GLOBALS['base_url']}/clients-cash-in");
        exit;
    }

    if (!$clientDetails) {
        setError("Client not found");
        header("Location: {$GLOBALS['base_url']}/clients-cash-in");
        exit;
    }

    // Set $payment as the first one for header metadata compatibility
    $payment = $payments[0];
    $isBulk = count($payments) > 1;

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
    
    // Get management users for handover dropdown
    $managementUsers = [];
    $mgmtStmt = clone $this->db;
    $mgmtQuery = $mgmtStmt->query("SELECT id, full_name, email FROM users WHERE role_id IN (1, 3)");
    while ($row = $mgmtQuery->fetch_assoc()) {
        $managementUsers[] = $row;
    }

    // Prepare data for view
    $data = [
        'client' => $clientDetails,
        'summary' => $paymentSummary,
        'transactions' => $transactions,
        'remaining_land_share' => $paymentSummary['remaining_land_share'],
        'is_land_share_completed' => $paymentSummary['is_land_share_completed'],
        'management_users' => $managementUsers
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
        
        $isTestingMode = !empty($data['is_testing_mode']);

        require_once dirname(__DIR__) . '/services/SmsService.php';
        $smsService = new SmsService($this->db);

        if (!$isTestingMode) {
            $clientDetails = $accountModel->getClientByBookingId($data['booking_id']);
            $clientPhone   = !empty($clientDetails['phone']) ? trim($clientDetails['phone']) : (!empty($clientDetails['phone_number']) ? trim($clientDetails['phone_number']) : '');
            $phoneCheck    = $smsService->validatePhoneNumber($clientPhone);

            if (!$phoneCheck['valid']) {
                // Do not block payment. It will gracefully show a warning instead.
                // echo json_encode([
                //     'success' => false,
                //     'message' => 'Cannot process payment: ' . $phoneCheck['message'] . '. Please update the client\'s phone number first or enable Testing Mode.'
                // ]);
                // return;
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
        
        if (!empty($result['success']) && !empty($result['payment_id'])) {
            $smsService->sendPaymentReceivedSms($result['payment_id'], $isTestingMode);
        }

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
 * Show printable Discount Statement for a booking
 * GET: /discount-statement/{bookingId} or ?booking_id=
 */
public function discountStatement($bookingId = null)
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!$bookingId) {
        $bookingId = $_GET['booking_id'] ?? null;
    }

    if (!$bookingId) {
        die("Booking ID is required");
    }

    $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
    if (!$clientDetails) {
        die("Client not found for booking ID: " . $bookingId);
    }

    $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($bookingId);

    // Fetch bulk EMI discount transactions
    $bulkDiscountTxns = [];
    $discStmt = $this->db->prepare("
        SELECT lp.id, lp.payment_type, lp.amount, lp.bulk_emi_discount, lp.bulk_pay_amount, 
               lp.payment_date, COALESCE(lp.emi_no, es.emi_no) as emi_no,
               u.full_name as added_by_name
        FROM lead_payments lp
        LEFT JOIN users u ON lp.created_by = u.id
        LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
        WHERE lp.booking_id = ? AND lp.bulk_emi_discount > 0
        ORDER BY lp.payment_date DESC, lp.id DESC
    ");
    $discStmt->bind_param('i', $bookingId);
    $discStmt->execute();
    $discResult = $discStmt->get_result();
    while ($dRow = $discResult->fetch_assoc()) {
        $bulkDiscountTxns[] = $dRow;
    }

    // Use absolute path from root
    $root = dirname(dirname(__DIR__));
    $viewPath = $root . '/app/views/accounts/cashIn/discount-statement.php';

    if (!file_exists($viewPath)) {
        die("View file not found at: " . $viewPath);
    }

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

        // ── ACCOUNTS-ONLY GATE ────────────────────────────────────────────────
        // role_id 1 = Super Admin, 3 = Admin, 6 = Accounts
        $userRoleId     = (int)($_SESSION['user']['role_id'] ?? 0);
        $allowedRoleIds = [1, 3, 6, 30];
        if (!in_array($userRoleId, $allowedRoleIds)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Accounts module access only']);
            exit;
        }
        // ─────────────────────────────────────────────────────────────────────

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

        $forceVerifyNote = trim($_POST['force_verify_note'] ?? '');

        // Check handover status first to prevent race conditions
        $payQuery = $this->db->prepare("
            SELECT p.handover_status, p.payment_type, p.due_emi_amount, b.id as booking_id, c.full_name as client_name, pr.name as project_name, f.flat_name
            FROM lead_payments p 
            LEFT JOIN lead_bookings b ON p.booking_id = b.id
            LEFT JOIN leads c ON b.lead_id = c.id
            LEFT JOIN flats f ON b.flat_id = f.id
            LEFT JOIN projects pr ON f.project_id = pr.id
            WHERE p.id = ?
        ");
        $payQuery->bind_param("i", $paymentId);
        $payQuery->execute();
        $paymentData = $payQuery->get_result()->fetch_assoc();

        if (!$paymentData) {
            echo json_encode(['success' => false, 'message' => 'Payment not found']);
            exit;
        }

        if ($action === 'verify' && $paymentData['handover_status'] === 'pending') {
            echo json_encode(['success' => false, 'message' => 'Cannot verify: Handover is pending management approval.']);
            exit;
        }

        if ($action === 'verify' && $paymentData['handover_status'] === 'rejected' && empty($forceVerifyNote)) {
            echo json_encode(['success' => false, 'message' => 'A note is required to force-verify a rejected handover.']);
            exit;
        }

        $newStatus = ($action === 'verify') ? 'verified' : 'rejected';

        $updateFields = ["verification_status = ?", "verified_by = ?", "verified_at = NOW()"];
        $params = [$newStatus, $userId];
        $types = "si";

        // Also update payment_status
        if ($action === 'verify') {
            $updateFields[] = "payment_status = CASE WHEN payment_type = 'emi' AND due_emi_amount > 0 THEN 'partially_paid' ELSE 'paid' END";
        } elseif ($action === 'reject') {
            $updateFields[] = "payment_status = 'failed'";
        }

        if ($action === 'reject') {
            $updateFields[] = "rejection_reason = ?";
            $params[] = $rejectionReason;
            $types .= "s";
        }
        
        if ($action === 'verify' && !empty($forceVerifyNote)) {
            $updateFields[] = "force_verify_note = ?";
            $params[] = $forceVerifyNote;
            $types .= "s";
        }
        
        $params[] = $paymentId;
        $params[] = $paymentId;
        $types .= "ii";
        $sql = "UPDATE lead_payments SET " . implode(", ", $updateFields) . " WHERE (id = ? OR bulk_parent_id = ?) AND verification_status = 'pending'";
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'DB prepare error']);
            exit;
        }

        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
            exit;
        }

        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Payment not found or already processed']);
            exit;
        }

        // ====== RESTORE PARENT DUE ON SETTLEMENT REJECTION ======
        if ($action === 'reject') {
            $checkSettlementStmt = $this->db->prepare("SELECT settlement_type, parent_payment_id, amount FROM lead_payments WHERE id = ?");
            if ($checkSettlementStmt) {
                $checkSettlementStmt->bind_param("i", $paymentId);
                $checkSettlementStmt->execute();
                $settlementRow = $checkSettlementStmt->get_result()->fetch_assoc();
                if ($settlementRow && $settlementRow['settlement_type'] === 'emi_due_settlement' && !empty($settlementRow['parent_payment_id'])) {
                    $parentId = (int)$settlementRow['parent_payment_id'];
                    $settlementAmount = (float)$settlementRow['amount'];
                    $restoreParentStmt = $this->db->prepare("
                        UPDATE lead_payments 
                        SET due_emi_amount = due_emi_amount + ?, payment_status = 'partially_paid' 
                        WHERE id = ?
                    ");
                    if ($restoreParentStmt) {
                        $restoreParentStmt->bind_param("di", $settlementAmount, $parentId);
                        $restoreParentStmt->execute();
                    }
                }
            }
        }
        // =========================================================

        // ====== SYNC BOOKING & PAYMENT STATUS ======
        if ($action === 'verify') {
            $getBookingIdStmt = $this->db->prepare("SELECT booking_id FROM lead_payments WHERE id = ?");
            if ($getBookingIdStmt) {
                $getBookingIdStmt->bind_param("i", $paymentId);
                $getBookingIdStmt->execute();
                $bookingRes = $getBookingIdStmt->get_result();
                if ($bookingRow = $bookingRes->fetch_assoc()) {
                    $bookingId = (int)$bookingRow['booking_id'];

                    require_once dirname(__DIR__) . '/controllers/PaymentBookingController.php';
                    $paymentController = new PaymentBookingController($this->db);
                    $newBookingStatus = $paymentController->updateBookingPaymentStatus($bookingId);

                    $paymentStatusMap = [
                        'paid'    => 'paid',
                        'partial' => 'partially_paid',
                        'unpaid'  => 'pending',
                    ];
                    $newPaymentStatus = $paymentStatusMap[$newBookingStatus] ?? 'paid';

                    $updatePaymentStmt = $this->db->prepare("
                        UPDATE lead_payments
                        SET payment_status = ?
                        WHERE id = ?
                    ");
                    if ($updatePaymentStmt) {
                        $updatePaymentStmt->bind_param('si', $newPaymentStatus, $paymentId);
                        $updatePaymentStmt->execute();
                    }

                    if ($newBookingStatus === 'paid') {
                        $paymentController->handleFlatBooking($bookingId);
                    }

                    // ====== AUTO POSTING JOURNAL VOUCHER (Batch 2/3) ======
                    require_once dirname(__DIR__) . '/services/AutoPostingService.php';
                    $autoService = new AutoPostingService($this->db);

                    $payInfoStmt = $this->db->prepare("SELECT lp.*, lb.project_id, lb.lead_id FROM lead_payments lp LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id WHERE lp.id = ?");
                    if ($payInfoStmt) {
                        $payInfoStmt->bind_param("i", $paymentId);
                        $payInfoStmt->execute();
                        $pData = $payInfoStmt->get_result()->fetch_assoc();
                        if ($pData && (float)$pData['amount'] > 0) {
                            $methodLower = strtolower($pData['payment_method'] ?? '');
                            $bankNameStr = $pData['bank_name'] ?? ($pData['bank'] ?? '');
                            $bankAccId   = $autoService->getBankOrCashAccountId($methodLower, $bankNameStr);

                            $autoService->postSalesPaymentEntry(
                                $pData['id'],
                                $bankAccId,
                                $pData['amount'],
                                $pData['payment_type'],
                                "Verified Client Payment - " . ucfirst($pData['payment_type']) . " (Booking #" . $pData['booking_id'] . ")",
                                $pData['project_id'],
                                $pData['lead_id'],
                                $userId
                            );
                        }
                    }

                    // ====== TRIGGER 2: SMS VERIFIED NOTIFICATION ======
                    $sendSms = isset($_POST['send_sms']) ? filter_var($_POST['send_sms'], FILTER_VALIDATE_BOOLEAN) : false;
                    if ($sendSms) {
                        require_once dirname(__DIR__) . '/services/SmsService.php';
                        $smsService = new SmsService($this->db);
                        $smsService->sendPaymentVerifiedSms($paymentId);
                    }
                }
            }
        }
        // ===========================================

        $label = ($action === 'verify') ? 'verified' : 'rejected';

        // Fetch verifier's name so the front-end can show it without a page reload
        $verifierName = '';
        $nameStmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
        if ($nameStmt) {
            $nameStmt->bind_param("i", $userId);
            $nameStmt->execute();
            $nameResult = $nameStmt->get_result();
            $nameRow = $nameResult ? $nameResult->fetch_assoc() : null;
            $verifierName = $nameRow['full_name'] ?? '';
        }

        // Send email if bypassing a rejected handover
        if ($action === 'verify' && $paymentData['handover_status'] === 'rejected' && !empty($forceVerifyNote)) {
            // Find management emails to notify
            $adminQuery = $this->db->query("SELECT email FROM users WHERE role_id IN (1, 3)");
            $accountsUserName = $_SESSION['user']['full_name'] ?? 'Accounts User';
            
            while ($admin = $adminQuery->fetch_assoc()) {
                if (!empty($admin['email'])) {
                    $subject = "Alert: Force Verification of Rejected Handover (Payment #$paymentId)";
                    $body = "
                        <h3>Handover Rejection Overridden</h3>
                        <p>You rejected a request for:</p>
                        <ul>
                            <li><strong>Project:</strong> {$paymentData['project_name']}</li>
                            <li><strong>Flat:</strong> {$paymentData['flat_name']}</li>
                            <li><strong>Client:</strong> {$paymentData['client_name']}</li>
                        </ul>
                        <p>But this person (<strong>{$accountsUserName}</strong>) verified the transaction as paid.</p>
                        <p><strong>Note provided by the user:</strong></p>
                        <blockquote>" . nl2br(htmlspecialchars($forceVerifyNote)) . "</blockquote>
                    ";
                    
                    if (function_exists('sendGenericMail')) {
                        sendGenericMail($admin['email'], $subject, $body);
                    }
                }
            }
        }

        echo json_encode([
            'success'          => true,
            'message'          => "Payment {$label} successfully",
            'status'           => $newStatus,
            'verified_by_name' => $verifierName,
            'verified_at'      => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    /**
     * Verify multiple payments at once (Accounts team action)
     * POST: payment_ids (array), send_sms
     */
    public function bulkVerify()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $userRoleId     = (int)($_SESSION['user']['role_id'] ?? 0);
        $allowedRoleIds = [1, 3, 6, 30];
        if (!in_array($userRoleId, $allowedRoleIds)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $paymentIds = $_POST['payment_ids'] ?? [];
        $sendSms    = isset($_POST['send_sms']) ? filter_var($_POST['send_sms'], FILTER_VALIDATE_BOOLEAN) : false;
        $userId     = $_SESSION['user']['id'] ?? 0;

        if (empty($paymentIds) || !is_array($paymentIds)) {
            echo json_encode(['success' => false, 'message' => 'No payments selected']);
            exit;
        }

        $verifiedCount = 0;
        $paymentsByBookingForSms = [];

        require_once dirname(__DIR__) . '/controllers/PaymentBookingController.php';
        $paymentController = new PaymentBookingController($this->db);

        require_once dirname(__DIR__) . '/services/AutoPostingService.php';
        $autoService = new AutoPostingService($this->db);

        $smsService = null;
        if ($sendSms) {
            require_once dirname(__DIR__) . '/services/SmsService.php';
            $smsService = new SmsService($this->db);
        }

        $this->db->begin_transaction();

        try {
            foreach ($paymentIds as $pid) {
                $paymentId = (int)$pid;

                // 1. Fetch payment
                $payQuery = $this->db->prepare("SELECT p.*, b.id as booking_id, b.project_id, b.lead_id FROM lead_payments p INNER JOIN lead_bookings b ON p.booking_id = b.id WHERE p.id = ?");
                if (!$payQuery) continue;
                $payQuery->bind_param('i', $paymentId);
                $payQuery->execute();
                $payRes = $payQuery->get_result();
                $paymentData = $payRes->fetch_assoc();

                if (!$paymentData || $paymentData['handover_status'] === 'pending' || $paymentData['verification_status'] === 'verified') {
                    continue; 
                }
                
                // If handover is rejected, bulk verify cannot force verify it (requires note). Skip it.
                if ($paymentData['handover_status'] === 'rejected') {
                    continue;
                }

                // 2. Mark as verified
                $updStmt = $this->db->prepare("UPDATE lead_payments SET verification_status = 'verified', verified_by = ?, verified_at = NOW() WHERE (id = ? OR bulk_parent_id = ?) AND verification_status = 'pending'");
                $updStmt->bind_param("iii", $userId, $paymentId, $paymentId);
                $updStmt->execute();

                if ($updStmt->affected_rows > 0) {
                    $verifiedCount++;
                    $bookingId = (int)$paymentData['booking_id'];

                    // 3. Sync Booking Status
                    $newBookingStatus = $paymentController->updateBookingPaymentStatus($bookingId);
                    $paymentStatusMap = ['paid' => 'paid', 'partial' => 'partially_paid', 'unpaid' => 'pending'];
                    $newPaymentStatus = $paymentStatusMap[$newBookingStatus] ?? 'paid';

                    $updStatus = $this->db->prepare("UPDATE lead_payments SET payment_status = ? WHERE id = ?");
                    $updStatus->bind_param('si', $newPaymentStatus, $paymentId);
                    $updStatus->execute();

                    if ($newBookingStatus === 'paid') {
                        $paymentController->handleFlatBooking($bookingId);
                    }

                    // 4. Auto Posting Journal
                    if ((float)$paymentData['amount'] > 0) {
                        $methodLower = strtolower($paymentData['payment_method'] ?? '');
                        $bankNameStr = $paymentData['bank_name'] ?? ($paymentData['bank'] ?? '');
                        $bankAccId   = $autoService->getBankOrCashAccountId($methodLower, $bankNameStr);

                        $autoService->postSalesPaymentEntry(
                            $paymentData['id'],
                            $bankAccId,
                            $paymentData['amount'],
                            $paymentData['payment_type'],
                            "Verified Client Payment - " . ucfirst($paymentData['payment_type']) . " (Booking #" . $paymentData['booking_id'] . ")",
                            $paymentData['project_id'],
                            $paymentData['lead_id'],
                            $userId
                        );
                    }

                    // Collect data for bulk SMS
                    if ($sendSms) {
                        if (!isset($paymentsByBookingForSms[$bookingId])) {
                            $paymentsByBookingForSms[$bookingId] = [
                                'total_amount' => 0,
                                'payment_id' => $paymentId,
                                'types' => []
                            ];
                        }
                        $paymentsByBookingForSms[$bookingId]['total_amount'] += (float)$paymentData['amount'];
                        $typeStr = ucfirst(str_replace('_', ' ', $paymentData['payment_type']));
                        if (!in_array($typeStr, $paymentsByBookingForSms[$bookingId]['types'])) {
                            $paymentsByBookingForSms[$bookingId]['types'][] = $typeStr;
                        }
                    }
                }
            }

            // 5. Send Grouped SMS
            if ($sendSms && $smsService && !empty($paymentsByBookingForSms)) {
                foreach ($paymentsByBookingForSms as $bId => $smsData) {
                    $typesStr = implode(', ', $smsData['types']);
                    $smsService->sendPaymentVerifiedSms($smsData['payment_id'], false, $smsData['total_amount'], $typesStr);
                }
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => "Successfully verified $verifiedCount transactions."]);
        } catch (Exception $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX: Get all active banks for dynamic dropdown
     */
    public function getBanks()
    {
        header('Content-Type: application/json');
        $stmt = $this->db->prepare("
            SELECT id, bank_name, branch_name, account_number, routing_number
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

    /**
     * AJAX: Get attachment metadata for a single lead_payment (read-only).
     * Called by: CED history view "View Document" button.
     * Access: Any authenticated user — read-only, no DB writes.
     * Route:  GET /get-transaction-attachments?payment_id=X
     */
    public function getTransactionAttachments()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $paymentId = (int)($_GET['payment_id'] ?? 0);
        if (!$paymentId) {
            echo json_encode(['success' => false, 'message' => 'Missing payment_id']);
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT
                lp.id,
                lp.payment_type,
                lp.amount,
                lp.payment_date,
                lp.verification_status,
                lp.attachment_paths,
                lp.attachment_image,
                lp.rejection_reason,
                uv.full_name AS verified_by_name,
                lp.verified_at
            FROM lead_payments lp
            LEFT JOIN users uv ON uv.id = lp.verified_by
            WHERE lp.id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'DB prepare error']);
            exit;
        }

        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Payment not found']);
            exit;
        }

        // Normalise: merge JSON attachment_paths + legacy attachment_image column
        $files = [];
        if (!empty($row['attachment_paths'])) {
            $decoded = json_decode($row['attachment_paths'], true);
            if (is_array($decoded)) {
                $files = array_merge($files, $decoded);
            }
        }
        if (!empty($row['attachment_image'])) {
            $legacyPath = 'uploads/payments/' . $row['attachment_image'];
            if (!in_array($legacyPath, $files)) {
                $files[] = $legacyPath;
            }
        }

        echo json_encode([
            'success'              => true,
            'payment_id'          => $row['id'],
            'payment_type'        => $row['payment_type'],
            'amount'              => $row['amount'],
            'payment_date'        => $row['payment_date'],
            'verification_status' => $row['verification_status'] ?? 'pending',
            'rejection_reason'    => $row['rejection_reason'] ?? '',
            'verified_by_name'    => $row['verified_by_name'] ?? '',
            'verified_at'         => $row['verified_at'] ?? '',
            'files'               => $files,
            'file_count'          => count($files),
        ]);
        exit;
    }

    public function saveTransferNote()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $bookingId = $_POST['booking_id'] ?? null;
        $transferDate = $_POST['transfer_date'] ?? null;
        $transferNote = $_POST['transfer_note'] ?? null;
        $transferStatus = isset($_POST['transfer_status']) ? (int)$_POST['transfer_status'] : 0;
        $updatedBy = $_SESSION['user']['id'] ?? null;
        $sourcePage = $_POST['source_page'] ?? 'accounts'; // accounts or ced

        if (!$bookingId || !$updatedBy) {
            setError('Invalid request or user not authenticated.');
            $redirectUrl = $sourcePage === 'ced' 
                ? "{$GLOBALS['base_url']}/clients-cash-in-history-view?booking_id={$bookingId}"
                : "{$GLOBALS['base_url']}/clients-cash-in-history?booking_id={$bookingId}";
            header("Location: {$redirectUrl}");
            exit;
        }

        // Empty strings to null
        $transferDate = !empty($transferDate) ? $transferDate : null;
        $transferNote = !empty($transferNote) ? $transferNote : null;

        $success = $this->clientModel->saveTransferNote($bookingId, $transferDate, $transferNote, $transferStatus, $updatedBy);

        if ($success) {
            setSuccess('Transfer note updated successfully.');
        } else {
            setError('Failed to update transfer note.');
        }

        $redirectUrl = $sourcePage === 'ced' 
            ? "{$GLOBALS['base_url']}/clients-cash-in-history-view?booking_id={$bookingId}"
            : "{$GLOBALS['base_url']}/clients-cash-in-history?booking_id={$bookingId}";
        
        header("Location: {$redirectUrl}");
        exit;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ─── CSV BULK IMPORT ENDPOINTS ────────────────────────────────────────
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Show the CSV import form page
     */
    public function showCSVImportForm()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->view("accounts/cashIn/csv-import-form", [
            "current_route" => "csv-import-form",
            "page_title"    => "CSV Bulk Payment Import",
            "preview_data"  => null,
            "errors"        => null,
            "summary"       => null
        ]);
    }

    /**
     * Handle CSV upload and show preview
     */
    public function previewCSVImport()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/csv-import-form");
            exit;
        }

        // Validate file upload
        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError("Please select a valid CSV file to upload.");
            header("Location: {$GLOBALS['base_url']}/csv-import-form");
            exit;
        }

        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            setError("Only CSV files are allowed.");
            header("Location: {$GLOBALS['base_url']}/csv-import-form");
            exit;
        }

        // Move to temp location
        $uploadDir = __DIR__ . '/../../public/uploads/csv_imports/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tempName = 'import_' . date('YmdHis') . '_' . uniqid() . '.csv';
        $tempPath = $uploadDir . $tempName;

        if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
            setError("Failed to upload file. Please try again.");
            header("Location: {$GLOBALS['base_url']}/csv-import-form");
            exit;
        }

        // Parse and validate CSV
        $result = $this->clientModel->parseAndValidateCSV($tempPath);

        // Store ONLY the file path & filename in session (NOT the parsed data —
        // large arrays can exceed session storage and silently corrupt the session)
        $_SESSION['csv_import_file'] = $tempPath;
        $_SESSION['csv_import_filename'] = $file['name'];

        $this->view("accounts/cashIn/csv-import-form", [
            "current_route" => "csv-import-preview",
            "page_title"    => "CSV Import Preview - " . $file['name'],
            "preview_data"  => $result['valid_rows'],
            "errors"        => $result['errors'],
            "summary"       => $result['summary'],
            "filename"      => $file['name']
        ]);
    }

    /**
     * Execute the confirmed CSV import
     */
    public function executeCSVImport()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/csv-import-form");
            exit;
        }

        // Retrieve file path from session (lightweight — no large data stored)
        $tempFile = $_SESSION['csv_import_file'] ?? null;
        $filename = $_SESSION['csv_import_filename'] ?? 'unknown.csv';

        if (!$tempFile || !file_exists($tempFile)) {
            setError("Import session expired or file not found. Please re-upload the CSV file.");
            header("Location: {$GLOBALS['base_url']}/csv-import-form");
            exit;
        }

        // Re-parse the CSV from the saved file (avoids session bloat)
        $parseResult = $this->clientModel->parseAndValidateCSV($tempFile);
        $validRows = $parseResult['valid_rows'] ?? [];
        $parseErrors = $parseResult['errors'] ?? [];

        if (empty($validRows)) {
            setError("No valid rows to import. Please upload a corrected CSV file.");
            header("Location: {$GLOBALS['base_url']}/csv-import-form");
            exit;
        }

        $createdBy = $_SESSION['user']['id'] ?? 1;
        $totalRows = count($validRows) + count($parseErrors);

        // Pre-create import log entry to link payment rows
        $csvImportLogId = $this->clientModel->logCSVImport(
            $filename, $totalRows, 0, 0, [], $createdBy
        );

        $successCount = 0;
        $errorCount = 0;
        $importResults = [];
        $importErrors = [];

        foreach ($validRows as $row) {
            $result = $this->clientModel->processCSVImportRow($row, $createdBy, $csvImportLogId ?: null);

            if ($result['success']) {
                $successCount++;
                $importResults[] = [
                    'row_num'    => $row['row_num'],
                    'client'     => $row['resolved_client'],
                    'project'    => $row['project_name'],
                    'flat'       => $row['flat_name'],
                    'amount'     => $row['amount'],
                    'type'       => $row['payment_type'],
                    'payment_id' => $result['payment_id'],
                    'booking_id' => $row['booking_id'],
                    'message'    => $result['message']
                ];
            } else {
                $errorCount++;
                $importErrors[] = [
                    'row_num'  => $row['row_num'],
                    'client'   => $row['client_name'],
                    'project'  => $row['project_name'],
                    'flat'     => $row['flat_name'],
                    'amount'   => $row['amount'],
                    'type'     => $row['payment_type'],
                    'message'  => $result['message']
                ];
            }
        }

        // Merge parse errors with import errors for the final log update
        $allErrors = array_merge(
            array_map(fn($e) => [
                'row' => $e['row'],
                'type' => 'parse_error',
                'messages' => $e['messages'] ?? [$e['message'] ?? 'Unknown error']
            ], $parseErrors),
            array_map(fn($e) => [
                'row' => $e['row_num'],
                'type' => 'import_error',
                'messages' => [$e['message']]
            ], $importErrors)
        );

        // Update the import log status and error details
        if ($csvImportLogId) {
            $status = ($errorCount + count($parseErrors) === 0) ? 'completed' : (($successCount > 0) ? 'completed' : 'failed');
            $errJson = json_encode($allErrors);
            $updLog = $this->db->prepare("UPDATE csv_import_logs SET success_count = ?, error_count = ?, error_details = ?, status = ? WHERE id = ?");
            if ($updLog) {
                $totalErrCount = $errorCount + count($parseErrors);
                $updLog->bind_param('iissi', $successCount, $totalErrCount, $errJson, $status, $csvImportLogId);
                $updLog->execute();
                $updLog->close();
            }
        }

        // Clean up: delete the temp CSV file
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        unset(
            $_SESSION['csv_import_file'],
            $_SESSION['csv_import_filename']
        );

        $this->view("accounts/cashIn/csv-import-results", [
            "current_route"  => "csv-import-results",
            "page_title"     => "CSV Import Results",
            "filename"       => $filename,
            "success_count"  => $successCount,
            "error_count"    => $errorCount + count($parseErrors),
            "total_rows"     => $totalRows,
            "import_results" => $importResults,
            "import_errors"  => $importErrors,
            "parse_errors"   => $parseErrors
        ]);
    }

    /**
     * Download CSV template with headers and sample data
     */
    public function downloadCSVTemplate()
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="payment_import_template.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Header row (19 columns — payment_status auto-calculated by backend)
        fputcsv($output, [
            'client_name', 'client_phone', 'project_name', 'flat_name',
            'amount', 'payment_date', 'payment_type', 'payment_method',
            'verification_status', 'transaction_ref',
            'bank_name', 'bank_account_no', 'cheque_no', 'online_type',
            'payment_details', 'bulk_emi_discount', 'emi_due_date', 'emi_no',
            'service_fee'
        ]);

        // Sample rows
        fputcsv($output, [
            'Rony Hasan', '01736936727', 'Armonia', 'Unit-A2',
            '100000', '2026-05-01', 'development_charge', 'bank',
            'verified', 'TRX-001',
            'EBL', '1041260066908', '', '',
            'Development charge payment', '0', '', '',
            '0'
        ]);

        fputcsv($output, [
            'Rony Hasan', '01736936727', 'Armonia', 'Unit-A2',
            '50000', '2026-05-15', 'emi', 'bank',
            '', 'TRX-002',
            'EBL', '1041260066908', '', '',
            'EMI Payment May 2026', '0', '2026-06-15', '1',
            '1500'
        ]);

        fputcsv($output, [
            'Kamal Ahmed', '01812345678', 'Orchid', 'Unit-B3',
            '150000', '2026-05-20', 'emi', 'cash',
            'verified', '',
            '', '', '', '',
            'Bulk EMI Payment', '10000', '2026-07-15', '3',
            '4500'
        ]);

        // Sample: EMI Due Settlement
        fputcsv($output, [
            'Rony Hasan', '01736936727', 'Armonia', 'Unit-A2',
            '12917', '2026-06-01', 'emi_due_settlement', 'cash',
            'verified', 'TRX-003',
            '', '', '', '',
            'EMI Due Settlement', '0', '', '',
            '500'
        ]);

        fclose($output);
        exit;
    }

    public function assignHandover()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $handoverToId = (int)($_POST['handover_to'] ?? 0);
        $createdBy = $_SESSION['user']['id'] ?? 1;

        if (!$paymentId || !$handoverToId) {
            echo json_encode(['success' => false, 'message' => 'Missing payment ID or handover employee']);
            exit;
        }

        try {
            $result = $this->clientModel->initiateHandover($paymentId, $handoverToId, $createdBy);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function saveEmiFollowup()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $emiNo = isset($_POST['emi_no']) ? (int)$_POST['emi_no'] : 0;
        $note = trim($_POST['note'] ?? '');
        $activityDate = trim($_POST['activity_date'] ?? '');
        $activityMode = trim($_POST['activity_mode'] ?? '');
        $activityReason = trim($_POST['activity_reason'] ?? '');
        $userId = $_SESSION['user']['id'] ?? 0;

        if (!$bookingId || empty($note) || empty($activityDate) || empty($activityMode) || empty($activityReason)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $stmt = $this->db->prepare("INSERT INTO emi_followups (booking_id, emi_no, note, activity_date, activity_mode, activity_reason, added_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssi", $bookingId, $emiNo, $note, $activityDate, $activityMode, $activityReason, $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        exit;
    }

    public function getEmiFollowups()
    {
        header('Content-Type: application/json');
        $bookingId = (int)($_GET['booking_id'] ?? 0);
        $filterByEmi = isset($_GET['filter_by_emi']) && $_GET['filter_by_emi'] == '1';
        $emiNo = isset($_GET['emi_no']) ? (int)$_GET['emi_no'] : null;

        if (!$bookingId) {
            echo json_encode(['success' => false, 'data' => []]);
            exit;
        }

        $sql = "
            SELECT f.*, u.full_name as author_name 
            FROM emi_followups f 
            LEFT JOIN users u ON f.added_by = u.id 
            WHERE f.booking_id = ?
        ";
        
        if ($filterByEmi && $emiNo !== null) {
            $sql .= " AND f.emi_no = " . (int)$emiNo;
        }
        
        $sql .= " ORDER BY f.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        if ($res) {
            $data = $res->fetch_all(MYSQLI_ASSOC);
            foreach ($data as &$row) {
                $row['created_at_formatted'] = date('M d, Y h:i A', strtotime($row['created_at']));
            }
        }

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    /**
     * Trash a transaction
     */
    public function trashTransaction()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $paymentId = isset($_GET['payment_id']) ? (int) $_GET['payment_id'] : 0;
        $bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;

        if (!$paymentId || !$bookingId) {
            setError("Invalid request: missing payment or booking ID.");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $isAccounts = in_array($roleId, [1, 3, 6, 30]);
        $redirectUrl = "{$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}";

        if (!$isAccounts) {
            setError("You do not have permission to trash transactions.");
            header("Location: {$redirectUrl}");
            exit;
        }

        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $result = $this->clientModel->trashTransaction($paymentId, $bookingId, $userId);

        if ($result['success']) {
            setSuccess($result['message']);
        } else {
            setError($result['message']);
        }

        header("Location: {$redirectUrl}");
        exit;
    }

    /**
     * Restore a trashed transaction
     */
    public function restoreTransaction()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $paymentId = isset($_GET['payment_id']) ? (int) $_GET['payment_id'] : 0;
        $bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;

        if (!$paymentId || !$bookingId) {
            setError("Invalid request: missing payment or booking ID.");
            header("Location: {$GLOBALS['base_url']}/trash-client-payment-list");
            exit;
        }

        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $isAccounts = in_array($roleId, [1, 3, 6, 30]);

        if (!$isAccounts) {
            setError("You do not have permission to restore transactions.");
            header("Location: {$GLOBALS['base_url']}/trash-client-payment-list");
            exit;
        }

        $result = $this->clientModel->restoreTransaction($paymentId, $bookingId);

        if ($result['success']) {
            setSuccess($result['message']);
        } else {
            setError($result['message']);
        }

        header("Location: {$GLOBALS['base_url']}/trash-client-payment-list");
        exit;
    }

    /**
     * View trashed client payments list
     */
    public function trashList()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $isAccounts = in_array($roleId, [1, 3, 6, 30]);

        if (!$isAccounts) {
            setError("You do not have permission to view trash.");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        $trashedTransactions = $this->clientModel->getTrashedTransactions();

        $this->view("accounts/cashIn/trash-client-payment-list", [
            "current_route" => "trash-client-payment-list",
            "page_title" => "Trash Client Payment",
            "trashedTransactions" => $trashedTransactions
        ]);
    }

    public function updateManagementDiscount()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');

        $isManagement = ($userRole === 1 || $userRole === 3 || strpos($roleName, 'management') !== false || strpos($roleName, 'admin') !== false);

        if (!$isManagement) {
            setError("Permission denied! Only Management can approve/update booking discount.");
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/clients-cashin-view"));
            exit;
        }

        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $approvedDiscount = floatval($_POST['approved_discount'] ?? 0);

        if ($bookingId <= 0) {
            setError("Invalid booking ID.");
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/clients-cashin-view"));
            exit;
        }

        $res = $this->clientModel->updateManagementBookingDiscount($bookingId, $approvedDiscount, $userId);

        if ($res['success']) {
            setSuccess("Booking discount successfully updated and approved! Land share target and remaining balances recalculated.");
        } else {
            setError($res['message'] ?? "Failed to update discount.");
        }

        header("Location: {$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}");
        exit;
    }
}