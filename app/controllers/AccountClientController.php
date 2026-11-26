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

    /**
     * Resolve the active web public directory (prefers projectRoot/public when present).
     */
    protected function getWebPublicDir(): string
    {
        $projectRoot = str_replace('\\', '/', dirname(dirname(__DIR__)));
        if (is_dir($projectRoot . '/public')) {
            return $projectRoot . '/public';
        }
        if (!empty($_SERVER['SCRIPT_FILENAME']) && is_dir(dirname($_SERVER['SCRIPT_FILENAME']))) {
            return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'])), '/');
        }
        return $projectRoot . '/public';
    }

    /**
     * Unified, safe multi-file upload handler for transaction proof documents (images + PDFs).
     */
    protected function handleProofFileUploads(array $filesField, string $prefix = 'proof_', array &$errors = []): array
    {
        $uploadedPaths = [];
        if (empty($filesField['name']) || !is_array($filesField['name'])) {
            return $uploadedPaths;
        }

        $hasSelectedFile = false;
        foreach ($filesField['name'] as $idx => $rawName) {
            $err = $filesField['error'][$idx] ?? UPLOAD_ERR_NO_FILE;
            if ($err !== UPLOAD_ERR_NO_FILE && trim((string)$rawName) !== '') {
                $hasSelectedFile = true;
                break;
            }
        }
        if (!$hasSelectedFile) {
            return $uploadedPaths;
        }

        $uploadDir = rtrim($this->getWebPublicDir(), '/') . '/uploads/transactions/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        if (!is_dir($uploadDir)) {
            $errors[] = 'Server upload directory could not be created (public/uploads/transactions).';
            error_log('[handleProofFileUploads] Failed to create directory: ' . $uploadDir);
            return $uploadedPaths;
        }
        if (!is_writable($uploadDir)) {
            @chmod($uploadDir, 0755);
        }

        $allowedImageMimes = [
            'image/jpeg', 'image/jpg', 'image/pjpeg',
            'image/png', 'image/x-png',
            'image/gif',
            'image/webp',
        ];
        $allowedPdfMimes = [
            'application/pdf', 'application/x-pdf', 'application/acrobat',
            'applications/vnd.pdf', 'text/pdf', 'text/x-pdf',
        ];
        $blockedMimes = [
            'text/x-php', 'application/x-php', 'application/x-httpd-php',
            'text/html', 'application/x-executable', 'application/x-sh',
        ];
        $allowedExts = ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp', 'pdf'];
        $mimeToExt = [
            'image/jpeg'        => 'jpg',
            'image/jpg'         => 'jpg',
            'image/pjpeg'       => 'jpg',
            'image/png'         => 'png',
            'image/x-png'       => 'png',
            'image/gif'         => 'gif',
            'image/webp'        => 'webp',
            'application/pdf'   => 'pdf',
            'application/x-pdf' => 'pdf',
        ];
        $maxSize = 20 * 1024 * 1024; // 20 MB per file

        foreach ($filesField['name'] as $idx => $rawName) {
            $origName = basename(trim((string)$rawName));
            $err = $filesField['error'][$idx] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_NO_FILE || $origName === '') {
                continue;
            }

            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                $iniMax = ini_get('upload_max_filesize') ?: '2M';
                $errors[] = "File '{$origName}' exceeds server upload limit ({$iniMax}).";
                error_log("[handleProofFileUploads] Size limit exceeded for {$origName} (err={$err}, upload_max_filesize={$iniMax})");
                continue;
            }
            if ($err !== UPLOAD_ERR_OK) {
                $errors[] = "File '{$origName}' failed to upload (error code {$err}).";
                error_log("[handleProofFileUploads] Upload error {$err} for {$origName}");
                continue;
            }

            $tmpName = $filesField['tmp_name'][$idx] ?? '';
            if (empty($tmpName) || !is_uploaded_file($tmpName)) {
                $errors[] = "Temporary upload file missing for '{$origName}'.";
                continue;
            }

            if (($filesField['size'][$idx] ?? 0) > $maxSize) {
                $errors[] = "File '{$origName}' exceeds maximum allowed size of 20 MB.";
                continue;
            }

            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if ($ext === 'jfif') {
                $ext = 'jpg';
            }

            $head = (string)@file_get_contents($tmpName, false, null, 0, 1024);
            if (stripos($head, '<?php') !== false || stripos($head, '<?=') !== false) {
                $errors[] = "File '{$origName}' contains disallowed script content.";
                continue;
            }

            $mime = '';
            if (function_exists('finfo_open')) {
                $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mime = strtolower(trim((string)@finfo_file($finfo, $tmpName)));
                    @finfo_close($finfo);
                }
            } elseif (function_exists('mime_content_type')) {
                $mime = strtolower(trim((string)@mime_content_type($tmpName)));
            }

            if ($mime !== '' && in_array($mime, $blockedMimes, true)) {
                $errors[] = "File '{$origName}' has an unsupported file type ({$mime}).";
                continue;
            }

            $hasPdfMagic = (strpos($head, '%PDF-') !== false);
            $extValid    = in_array($ext, $allowedExts, true);
            $isPdfValid  = ($ext === 'pdf' && ($hasPdfMagic || in_array($mime, $allowedPdfMimes, true) || $mime === '' || $mime === 'application/octet-stream'))
                           || in_array($mime, $allowedPdfMimes, true);
            $isImgValid  = in_array($mime, $allowedImageMimes, true)
                           || (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) && ($mime === '' || $mime === 'application/octet-stream' || @getimagesize($tmpName) !== false));

            if (!$isPdfValid && !$isImgValid) {
                $errors[] = "File '{$origName}' must be a valid JPG, PNG, GIF, WEBP image or PDF.";
                error_log("[handleProofFileUploads] Rejected {$origName} (ext={$ext}, mime={$mime})");
                continue;
            }

            if (!$extValid) {
                $ext = $hasPdfMagic ? 'pdf' : ($mimeToExt[$mime] ?? '');
                if ($ext === '') {
                    $errors[] = "Could not determine safe file extension for '{$origName}'.";
                    continue;
                }
            }

            $name = $prefix . uniqid() . '.' . $ext;
            $destPath = $uploadDir . $name;
            if (@move_uploaded_file($tmpName, $destPath)) {
                @chmod($destPath, 0644);
                $uploadedPaths[] = 'uploads/transactions/' . $name;

                // Mirror between VPS Staging & Production uploads if both coexist on the same server
                foreach (['/var/www/html/TPHL-ERP/public/uploads', '/var/www/staging/TPHL-ERP/public/uploads'] as $vpsUploadsBase) {
                    if (@is_dir($vpsUploadsBase) && @is_writable($vpsUploadsBase)) {
                        $mirrorDir = $vpsUploadsBase . '/transactions/';
                        if (rtrim(str_replace('\\', '/', $mirrorDir), '/') !== rtrim(str_replace('\\', '/', $uploadDir), '/')) {
                            if (!@is_dir($mirrorDir)) {
                                @mkdir($mirrorDir, 0755, true);
                            }
                            if (@is_dir($mirrorDir) && @is_writable($mirrorDir)) {
                                @copy($destPath, $mirrorDir . $name);
                                @chmod($mirrorDir . $name, 0644);
                            }
                        }
                    }
                }
            } else {
                $errors[] = "Server could not save '{$origName}' (check permissions on public/uploads/transactions).";
                error_log('[handleProofFileUploads] move_uploaded_file failed for ' . $destPath);
            }
        }

        return $uploadedPaths;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_GET['ajax']) && $_GET['ajax'] == '1');

        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int) $_GET['project_id'] : null;
        $verificationStatus = isset($_GET['verification_status']) && $_GET['verification_status'] !== '' ? $_GET['verification_status'] : null;
        $search = trim($_GET['search'] ?? '');
        $hasConflict = !empty($_GET['has_conflict']);

        $dateType = trim($_GET['date_type'] ?? '');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');
        if (!in_array($dateType, ['booking_date', 'last_payment_date'])) {
            $dateType = null;
        }
        if (empty($startDate)) $startDate = null;
        if (empty($endDate)) $endDate = null;

        $paymentType = trim($_GET['payment_type'] ?? '');
        $allowedPaymentTypes = ['full_payment', 'land_share', 'development_charge', 'shop_service_charge', 'construction_fee'];
        if (!in_array($paymentType, $allowedPaymentTypes, true)) {
            $paymentType = '';
        }

        if (isset($_GET['print']) && $_GET['print'] == '1') {
            $this->printClientsStatement();
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(5, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        // Get total count & paginated clients
        $totalClients = $this->clientModel->getTotalClientsCount($selectedProjectId, $verificationStatus, $search, $hasConflict, $dateType, $startDate, $endDate, $paymentType);
        $totalPages = $totalClients > 0 ? (int)ceil($totalClients / $limit) : 1;
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
        }

        $clients = $this->clientModel->getAllClients($selectedProjectId, $verificationStatus, $search, $limit, $offset, $hasConflict, $dateType, $startDate, $endDate, $paymentType);
        $conflictedUnits = $this->clientModel->getConflictedUnits();

        if ($isAjax) {
            $tableHtml = $this->renderCashInRowsHtml($clients, $conflictedUnits, $paymentType);
            $tableHeadHtml = $this->renderCashInTableHeadHtml($paymentType);
            $paginationHtml = $this->generateAjaxPagination($page, $totalPages, $totalClients, $limit, 'loadCashInPage');
            $startEntry = $totalClients > 0 ? (($page - 1) * $limit) + 1 : 0;
            $endEntry = min($totalClients, $page * $limit);
            $infoText = "Showing {$startEntry} to {$endEntry} of {$totalClients} entries";

            $paymentStats = $this->clientModel->getPaymentStatistics($selectedProjectId);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'page' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $totalClients,
                'total_clients' => $totalClients,
                'full_payment_completed' => (int)($paymentStats['full_payment_completed'] ?? 0),
                'approved_bookings' => (int)($paymentStats['approved_bookings'] ?? 0),
                'units_booked' => (int)($paymentStats['units_booked'] ?? $paymentStats['total_units_booked'] ?? $paymentStats['approved_bookings'] ?? 0),
                'limit' => $limit,
                'payment_type' => $paymentType,
                'table_head_html' => $tableHeadHtml,
                'table_html' => $tableHtml,
                'pagination_html' => $paginationHtml,
                'info_text' => $infoText
            ]);
            exit;
        }

        // Get payment statistics
        $paymentStats = $this->clientModel->getPaymentStatistics($selectedProjectId);

        // Get Collection Summary & Project Breakdown
        $collectionStats = $this->clientModel->getCollectionStatsSummary($selectedProjectId);
        $projectCollectionBreakdown = $this->clientModel->getProjectCollectionBreakdown();

        // Get all projects for dropdown (same as flat-lists)
        $projectModel = new Project($this->db);
        $projects = $projectModel->getAll();
        if (empty($projects)) {
            $projects = $this->clientModel->getProjectsForCED();
        }

        $assignedProjectIds = [];

        $this->view("accounts/cashIn/clients", [
            "current_route" => "clients-cash-in",
            "page_title" => "Clients Management - Cash In",
            "clients" => $clients,
            "totalClients" => $totalClients,
            "totalPages" => $totalPages,
            "currentPage" => $page,
            "limit" => $limit,
            "search" => $search,
            "paymentType" => $paymentType,
            "paymentStats" => $paymentStats,
            "collectionStats" => $collectionStats,
            "projectCollectionBreakdown" => $projectCollectionBreakdown,
            "projects" => $projects,
            "assignedProjectIds" => $assignedProjectIds,
            "selectedProjectId" => $selectedProjectId,
            "selectedVerificationStatus" => $verificationStatus,
            "dateType" => $dateType,
            "startDate" => $startDate,
            "endDate" => $endDate,
            "hasConflict" => $hasConflict,
            "conflictedUnits" => $conflictedUnits,
            "conflictedBookingsDetail" => $this->clientModel->getConflictedBookingsDetail()
        ]);
    }

public function indexTwo($bookingId = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Get booking_id or lead_id from URL parameter or query string
    $inputLeadId = (int)($_GET['lead_id'] ?? 0);
    if (!$bookingId && $inputLeadId > 0) {
        $leadStmt = $this->db->prepare("SELECT id FROM lead_bookings WHERE lead_id = ? ORDER BY id DESC LIMIT 1");
        if ($leadStmt) {
            $leadStmt->bind_param("i", $inputLeadId);
            $leadStmt->execute();
            $lRes = $leadStmt->get_result();
            if ($lRow = $lRes->fetch_assoc()) {
                $bookingId = (int)$lRow['id'];
            }
            $leadStmt->close();
        }
        if (!$bookingId) {
            $bookingId = $inputLeadId;
        }
    }

    if (!$bookingId) {
        $bookingId = (int)($_GET['booking_id'] ?? 0);
    }

    if (!$bookingId) {
        setError("No land share or lead ID provided");
        header("Location: {$GLOBALS['base_url']}/clients-cash-in");
        exit;
    }

    // Get client details
    $clientDetails = $this->clientModel->getClientByBookingId($bookingId);

    // Fallback: If not found by booking_id, check if $bookingId was actually a lead_id
    if (!$clientDetails) {
        $leadStmt = $this->db->prepare("SELECT id FROM lead_bookings WHERE lead_id = ? ORDER BY id DESC LIMIT 1");
        if ($leadStmt) {
            $leadStmt->bind_param("i", $bookingId);
            $leadStmt->execute();
            $lRes = $leadStmt->get_result();
            if ($lRow = $lRes->fetch_assoc()) {
                $actualBookingId = (int)$lRow['id'];
                $clientDetails = $this->clientModel->getClientByBookingId($actualBookingId);
                if ($clientDetails) {
                    $bookingId = $actualBookingId;
                }
            }
            $leadStmt->close();
        }
    }

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
                'bulk_emi_discount' => $clientDetails['discount_amount'] ?? 0,
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
    
    // Always ensure transactions are strictly sorted in chronological business order
    if (is_array($transactions) && method_exists($this->clientModel, 'sortTransactions')) {
        $this->clientModel->sortTransactions($transactions);
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

    // Get sales agents for booking info edit modal (Sales Manager, Sales Team Head, Sales Executive, Secondary Roles & Admin) with employee_id
    $salesUsers = [];
    $salesRes = $this->db->query("SELECT u.id, u.full_name, r1.name as role_name, r2.name as sec_role_name, 
                                         COALESCE(NULLIF(e1.employee_id, ''), NULLIF(e2.employee_id, ''), NULLIF(u.employee_id, '')) as employee_id 
                                  FROM users u 
                                  LEFT JOIN roles r1 ON u.role_id = r1.id 
                                  LEFT JOIN roles r2 ON u.secondary_role_id = r2.id 
                                  LEFT JOIN employees e1 ON e1.user_id = u.id
                                  LEFT JOIN employees e2 ON u.employee_id = e2.id
                                  WHERE (r1.name LIKE '%sales%' OR r1.name LIKE '%sale%' 
                                     OR r2.name LIKE '%sales%' OR r2.name LIKE '%sale%'
                                     OR u.role_id IN (1, 3, 5, 8, 9, 20, 33)
                                     OR u.secondary_role_id IN (5, 8, 9))
                                  ORDER BY u.full_name ASC");
    if ($salesRes) {
        $salesUsers = $salesRes->fetch_all(MYSQLI_ASSOC);
    }

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
        "salesUsers"      => $salesUsers,
        "accountModel"    => $this->clientModel,
        "datewiseSummary" => $this->clientModel->getDatewiseBatchPaymentSummary($bookingId)
    ]);
}

    /**
     * Dedicated Full Page: EMI Tracking & Statement Breakdown
     * Route: /client-emi-tracking/{bookingId}
     */
    public function clientEmiTracking($bookingId = null)
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

        $bookingId = (int)$bookingId;
        $clientDetails = $this->clientModel->getClientByBookingId($bookingId);

        if (!$clientDetails) {
            setError("Client booking not found");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        if (!empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1) {
            require_once dirname(__DIR__) . '/models/AccountShopClient.php';
            $this->clientModel = new AccountShopClient($this->db);
            $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
        }

        $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($bookingId);
        $virtualEmiSchedule = $this->clientModel->getVirtualEmiSchedule($bookingId);
        $outstandingDues = method_exists($this->clientModel, 'getOutstandingEmiDues')
            ? $this->clientModel->getOutstandingEmiDues($bookingId)
            : [];

        $this->view("accounts/cashIn/client-emi-tracking", [
            "current_route"      => "client-emi-tracking",
            "page_title"         => "EMI Tracking & Statement - " . htmlspecialchars($clientDetails['client_name'] ?? 'Client'),
            "clientDetails"      => $clientDetails,
            "paymentSummary"     => $paymentSummary,
            "virtualEmiSchedule" => $virtualEmiSchedule,
            "outstandingDues"    => $outstandingDues,
            "accountModel"       => $this->clientModel,
            "bookingId"          => $bookingId
        ]);
    }

    /**
     * Dedicated printable view for EMI Maturity Schedule
     */
    public function printEmiSchedule($bookingId = null)
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

        $bookingId = (int)$bookingId;
        $clientDetails = $this->clientModel->getClientByBookingId($bookingId);

        if (!$clientDetails) {
            setError("Client booking not found");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        if (!empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1) {
            require_once dirname(__DIR__) . '/models/AccountShopClient.php';
            $this->clientModel = new AccountShopClient($this->db);
            $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
        }

        $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($bookingId);
        $virtualEmiSchedule = $this->clientModel->getVirtualEmiSchedule($bookingId);

        $this->view("accounts/cashIn/client-emi-schedule-print", [
            "current_route"      => "client-emi-schedule-print",
            "page_title"         => "EMI Maturity Schedule - " . htmlspecialchars($clientDetails['client_name'] ?? 'Client'),
            "clientDetails"      => $clientDetails,
            "paymentSummary"     => $paymentSummary,
            "virtualEmiSchedule" => $virtualEmiSchedule,
            "bookingId"          => $bookingId
        ], false);
    }

    /**
     * Update booking info (booking_date, sales_by_id) from clients-cash-in-history
     * Cascades:
     * - lead_bookings: updates booking_date and sales_by_id
     * - lead_payments: updates payment_date for booking_money and registration
     * - leads: updates assigned_id (and team_id if available)
     * Permission: Accounts (6), Accounts Head (30), Accounts Executive (36), Admin (1), Super Admin (3)
     */
    public function updateBookingInfo()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_POST['is_ajax']) && $_POST['is_ajax'] == '1');

        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $canEdit = in_array($roleId, [1, 3, 6, 30, 36]) 
                   || (function_exists('isAccountsHead') && isAccountsHead()) 
                   || (function_exists('isAccountsExecutive') && isAccountsExecutive())
                   || strpos($roleName, 'account') !== false 
                   || strpos($roleName, 'admin') !== false;

        if (!$canEdit) {
            $msg = "You are not authorized to update booking info.";
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/clients-cash-in"));
            exit;
        }

        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $bookingDate = trim($_POST['booking_date'] ?? '');
        $salesById = (int)($_POST['sales_by_id'] ?? 0);
        $newTotalEmi = isset($_POST['total_emi_no']) && trim($_POST['total_emi_no']) !== '' ? (int)$_POST['total_emi_no'] : null;

        if ($bookingId <= 0 && $leadId > 0) {
            $stmtLead = $this->db->prepare("SELECT id FROM lead_bookings WHERE lead_id = ? ORDER BY id DESC LIMIT 1");
            if ($stmtLead) {
                $stmtLead->bind_param("i", $leadId);
                $stmtLead->execute();
                $resLead = $stmtLead->get_result();
                if ($rowLead = $resLead->fetch_assoc()) {
                    $bookingId = (int)$rowLead['id'];
                }
                $stmtLead->close();
            }
        }

        if ($bookingId <= 0) {
            $msg = "Invalid booking ID.";
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/clients-cash-in"));
            exit;
        }

        if (empty($bookingDate) || !strtotime($bookingDate)) {
            $msg = "Please select a valid booking date.";
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}"));
            exit;
        }

        if ($salesById <= 0) {
            $msg = "Please select a valid sales agent.";
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}"));
            exit;
        }

        if ($newTotalEmi !== null) {
            // Check completed EMIs for this booking so user cannot set total_emi_no less than already paid EMIs
            $checkPaidStmt = $this->db->prepare("
                SELECT COUNT(DISTINCT es.emi_no) as completed_cnt 
                FROM emi_schedules es
                INNER JOIN lead_payments lp ON lp.id = es.lead_payment_id
                WHERE lp.is_trashed = 0 AND lp.booking_id = ? 
                  AND lp.payment_status IN ('paid','partially_paid') 
                  AND lp.verification_status = 'verified'
            ");
            $completedCnt = 0;
            if ($checkPaidStmt) {
                $checkPaidStmt->bind_param("i", $bookingId);
                $checkPaidStmt->execute();
                $completedCnt = (int)($checkPaidStmt->get_result()->fetch_assoc()['completed_cnt'] ?? 0);
                $checkPaidStmt->close();
            }

            if ($newTotalEmi < $completedCnt) {
                $msg = "Total EMI ({$newTotalEmi}) cannot be less than already completed EMIs ({$completedCnt}).";
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $msg]);
                    exit;
                }
                setError($msg);
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}"));
                exit;
            }
        }

        $formattedBookingDate = date('Y-m-d', strtotime($bookingDate));

        // Get existing booking details
        $stmtB = $this->db->prepare("SELECT id, lead_id, booking_date, sales_by_id, total_emi_no, emi_updated_by FROM lead_bookings WHERE id = ?");
        $stmtB->bind_param("i", $bookingId);
        $stmtB->execute();
        $booking = $stmtB->get_result()->fetch_assoc();
        $stmtB->close();

        // Fallback: If not found by booking_id, check if $bookingId was a lead_id
        if (!$booking) {
            $stmtLeadB = $this->db->prepare("SELECT id, lead_id, booking_date, sales_by_id, total_emi_no, emi_updated_by FROM lead_bookings WHERE lead_id = ? ORDER BY id DESC LIMIT 1");
            if ($stmtLeadB) {
                $stmtLeadB->bind_param("i", $bookingId);
                $stmtLeadB->execute();
                $booking = $stmtLeadB->get_result()->fetch_assoc();
                $stmtLeadB->close();
                if ($booking) {
                    $bookingId = (int)$booking['id'];
                }
            }
        }

        if (!$booking) {
            $msg = "Booking not found.";
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/clients-cash-in"));
            exit;
        }

        $leadId = (int)($booking['lead_id'] ?? 0);
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);

        // Fetch user info for sales agent (team_id, name)
        $uStmt = $this->db->prepare("SELECT id, team_id, full_name FROM users WHERE id = ?");
        $uStmt->bind_param("i", $salesById);
        $uStmt->execute();
        $uRow = $uStmt->get_result()->fetch_assoc();
        $uStmt->close();

        $teamId = $uRow['team_id'] ?? null;
        if (empty($teamId)) {
            $eQ = $this->db->query("SELECT team_id FROM employees WHERE user_id = {$salesById} LIMIT 1");
            if ($eQ && $eR = $eQ->fetch_assoc()) {
                $teamId = !empty($eR['team_id']) ? (int)$eR['team_id'] : null;
            }
        }
        $agentName = $uRow['full_name'] ?? 'Selected Agent';

        $this->db->begin_transaction();
        try {
            // 1. Update lead_bookings (booking_date, sales_by_id, team_id, and optionally total_emi_no + audit)
            if ($newTotalEmi !== null) {
                $oldTotalEmi = isset($booking['total_emi_no']) && $booking['total_emi_no'] !== null ? (int)$booking['total_emi_no'] : null;
                $emiActuallyChanged = ($oldTotalEmi === null || $oldTotalEmi !== $newTotalEmi || empty($booking['emi_updated_by']));

                if ($emiActuallyChanged) {
                    $updB = $this->db->prepare("UPDATE lead_bookings SET booking_date = ?, sales_by_id = ?, team_id = COALESCE(?, team_id), total_emi_no = ?, emi_updated_by = ?, emi_updated_at = NOW(), updated_at = NOW() WHERE id = ?");
                    if (!$updB) {
                        throw new Exception("Prepare lead_bookings update failed: " . $this->db->error);
                    }
                    $updB->bind_param("siiiii", $formattedBookingDate, $salesById, $teamId, $newTotalEmi, $currentUserId, $bookingId);
                    $updB->execute();
                    $updB->close();
                } else {
                    $updB = $this->db->prepare("UPDATE lead_bookings SET booking_date = ?, sales_by_id = ?, team_id = COALESCE(?, team_id), total_emi_no = ?, updated_at = NOW() WHERE id = ?");
                    if (!$updB) {
                        throw new Exception("Prepare lead_bookings update failed: " . $this->db->error);
                    }
                    $updB->bind_param("siiii", $formattedBookingDate, $salesById, $teamId, $newTotalEmi, $bookingId);
                    $updB->execute();
                    $updB->close();
                }

                // Also sync total_emi_no in lead_payments for this booking
                $updEmiP = $this->db->prepare("UPDATE lead_payments SET total_emi_no = ?, updated_by = ? WHERE booking_id = ?");
                if ($updEmiP) {
                    $updEmiP->bind_param("iii", $newTotalEmi, $currentUserId, $bookingId);
                    $updEmiP->execute();
                    $updEmiP->close();
                }
            } else {
                $updB = $this->db->prepare("UPDATE lead_bookings SET booking_date = ?, sales_by_id = ?, team_id = COALESCE(?, team_id), updated_at = NOW() WHERE id = ?");
                if (!$updB) {
                    throw new Exception("Prepare lead_bookings update failed: " . $this->db->error);
                }
                $updB->bind_param("siii", $formattedBookingDate, $salesById, $teamId, $bookingId);
                $updB->execute();
                $updB->close();
            }

            // 2. Update lead_payments (booking_money payment_date only)
            $updP = $this->db->prepare("UPDATE lead_payments SET payment_date = ?, updated_by = ?, updated_at = NOW() WHERE booking_id = ? AND payment_type = 'booking_money'");
            if (!$updP) {
                throw new Exception("Prepare lead_payments update failed: " . $this->db->error);
            }
            $updP->bind_param("sii", $formattedBookingDate, $currentUserId, $bookingId);
            $updP->execute();
            $updP->close();

            // 3. Update leads (assigned_id and team_id for this lead_id)
            if ($leadId > 0) {
                if (!empty($teamId)) {
                    $updL = $this->db->prepare("UPDATE leads SET assigned_id = ?, team_id = ?, initial_team_id = COALESCE(initial_team_id, ?), updated_at = NOW() WHERE id = ?");
                    if (!$updL) {
                        throw new Exception("Prepare leads update failed: " . $this->db->error);
                    }
                    $updL->bind_param("iiii", $salesById, $teamId, $teamId, $leadId);
                } else {
                    $updL = $this->db->prepare("UPDATE leads SET assigned_id = ?, updated_at = NOW() WHERE id = ?");
                    if (!$updL) {
                        throw new Exception("Prepare leads update failed: " . $this->db->error);
                    }
                    $updL->bind_param("ii", $salesById, $leadId);
                }
                $updL->execute();
                $updL->close();
            }

            $this->db->commit();

            $successMsg = "Booking info updated successfully! Booking date set to " . date('d M, Y', strtotime($formattedBookingDate)) . ", Sales Agent updated to " . htmlspecialchars($agentName) . ($newTotalEmi !== null ? ", Total EMI set to {$newTotalEmi}." : ".");

            if ($isAjax) {
                echo json_encode([
                    'success' => true,
                    'message' => $successMsg,
                    'booking_date' => $formattedBookingDate,
                    'formatted_booking_date' => date('d M, Y', strtotime($formattedBookingDate)),
                    'sales_by_id' => $salesById,
                    'sales_by_name' => $agentName
                ]);
                exit;
            }

            $_SESSION['flash_success'] = $successMsg;
            $redirectUrl = $_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}";
            header("Location: {$redirectUrl}");
            exit;

        } catch (Exception $e) {
            $this->db->rollback();
            $errorMsg = "Error updating booking info: " . $e->getMessage();
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            setError($errorMsg);
            $redirectUrl = $_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}";
            header("Location: {$redirectUrl}");
            exit;
        }
    }

    /**
     * Update Shop Service Charge directly in flats table from History/Ledger views
     */
    public function updateShopServiceCharge()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_POST['is_ajax']) && $_POST['is_ajax'] == '1');

        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $canEdit = in_array($roleId, [1, 3, 6, 20, 30, 32, 33, 36]) 
                   || (function_exists('isAccountsHead') && isAccountsHead()) 
                   || (function_exists('isAccountsExecutive') && isAccountsExecutive())
                   || strpos($roleName, 'account') !== false 
                   || strpos($roleName, 'admin') !== false
                   || hasPermission("CED Manage")
                   || hasPermission("Accounts Manage");

        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? ($bookingId > 0 ? "{$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}" : "{$GLOBALS['base_url']}/clients-cashin-view");

        if (!$canEdit) {
            $msg = "You are not authorized to update shop service charge.";
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: {$redirectUrl}");
            exit;
        }

        $flatId = (int)($_POST['flat_id'] ?? 0);
        if ($flatId <= 0 && $bookingId > 0) {
            $stmtFlat = $this->db->prepare("SELECT flat_id FROM lead_bookings WHERE id = ?");
            if ($stmtFlat) {
                $stmtFlat->bind_param("i", $bookingId);
                $stmtFlat->execute();
                $resFlat = $stmtFlat->get_result();
                if ($rowFlat = $resFlat->fetch_assoc()) {
                    $flatId = (int)$rowFlat['flat_id'];
                }
                $stmtFlat->close();
            }
        }

        if ($flatId <= 0) {
            $msg = "Invalid flat ID.";
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: {$redirectUrl}");
            exit;
        }

        $newAmount = isset($_POST['shop_service_charge']) && trim($_POST['shop_service_charge']) !== '' 
            ? (float)str_replace(',', '', $_POST['shop_service_charge']) 
            : 0.00;

        if ($newAmount < 0) {
            $newAmount = 0.00;
        }

        $stmt = $this->db->prepare("UPDATE flats SET shop_service_charge = ? WHERE id = ?");
        if (!$stmt) {
            $msg = "Database prepare error: " . $this->db->error;
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: {$redirectUrl}");
            exit;
        }

        $stmt->bind_param("di", $newAmount, $flatId);
        $updated = $stmt->execute();
        $stmt->close();

        if ($updated) {
            $msg = "Shop service charge updated successfully to TK. " . number_format($newAmount, 2);
            if ($isAjax) {
                echo json_encode(['success' => true, 'message' => $msg, 'new_amount' => $newAmount]);
                exit;
            }
            setSuccess($msg);
            header("Location: {$redirectUrl}");
            exit;
        } else {
            $msg = "Failed to update shop service charge.";
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: {$redirectUrl}");
            exit;
        }
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

    /**
     * Get effective project filter for CED / Client Experience agent.
     * Unlocked: CED agents can view and filter across all projects.
     */
    protected function getEffectiveCedProjectFilter(?int $selectedProjectId, &$assignedProjectIds = [])
    {
        $assignedProjectIds = [];
        return $selectedProjectId;
    }

    public function indexView()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int) $_GET['project_id'] : null;
        $assignedProjectIds = [];
        $effectiveProjectId = $this->getEffectiveCedProjectFilter($selectedProjectId, $assignedProjectIds);

        // Handle AJAX request for Due EMIs modal
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'due_modal') {
            $modalProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
            $modalAssignedIds = [];
            $effectiveModalProjectId = $this->getEffectiveCedProjectFilter($modalProjectId, $modalAssignedIds);
            $search = trim($_GET['search'] ?? '');
            $dueList = $this->clientModel->getDueClientsList($effectiveModalProjectId, $search, 200);
            $rowsHtml = $this->renderDueModalRowsHtml($dueList);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'count' => count($dueList),
                'rows_html' => $rowsHtml
            ]);
            exit;
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_GET['ajax']) && $_GET['ajax'] == '1');

        $verificationStatus = isset($_GET['verification_status']) && $_GET['verification_status'] !== '' ? $_GET['verification_status'] : null;
        $search = trim($_GET['search'] ?? '');
        $hasConflict = !empty($_GET['has_conflict']);

        $dateType = trim($_GET['date_type'] ?? '');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');
        if (!in_array($dateType, ['booking_date', 'last_payment_date'])) {
            $dateType = null;
        }
        if (empty($startDate)) $startDate = null;
        if (empty($endDate)) $endDate = null;

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(5, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        // Get total count & paginated clients
        $totalClients = $this->clientModel->getTotalClientsCount($effectiveProjectId, $verificationStatus, $search, $hasConflict, $dateType, $startDate, $endDate);
        $totalPages = $totalClients > 0 ? (int)ceil($totalClients / $limit) : 1;
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
        }

        $clients = $this->clientModel->getAllClients($effectiveProjectId, $verificationStatus, $search, $limit, $offset, $hasConflict, $dateType, $startDate, $endDate);

        // Process virtual EMI schedule and dues ONLY for current page's clients
        $today = new \DateTime();
        foreach ($clients as &$client) {
            $bookingId = (int)($client['booking_id'] ?? 0);
            $schedule = $bookingId > 0 ? $this->clientModel->getVirtualEmiSchedule($bookingId) : [];
            $client['current_month_status'] = $this->calculateCurrentMonthStatus($schedule, $client['last_payment_date'] ?? null);
            $client['total_emi_count'] = count($schedule) > 0 ? count($schedule) : 36;
            $client['total_followup_count'] = $bookingId > 0 ? $this->clientModel->getTotalFollowupCount($bookingId) : 0;

            // Calculate Due EMIs for this client
            $clientDueEmis = [];
            $clientDueTotal = 0;

            // 1. Partial dues from lead_payments
            $outstandingDues = $bookingId > 0 ? $this->clientModel->getOutstandingEmiDues($bookingId) : [];
            if (!empty($outstandingDues)) {
                foreach ($outstandingDues as $od) {
                    $emiNo = (int)($od['emi_no'] ?? 0);
                    $dueDateStr = $od['payment_date'] ?? null;
                    $dueAmt = (float)($od['due_emi_amount'] ?? 0);
                    if ($dueAmt > 0) {
                        $isExpired = false;
                        $daysDiff = 0;
                        $monthsDiff = 0;
                        if (!empty($dueDateStr)) {
                            $dObj = new \DateTime($dueDateStr);
                            if ($dObj < $today) {
                                $isExpired = true;
                                $diff = $today->diff($dObj);
                                $daysDiff = $diff->days;
                                $monthsDiff = ($diff->y * 12) + $diff->m;
                            }
                        }
                        $clientDueEmis[$emiNo] = [
                            'emi_no' => $emiNo,
                            'due_date' => $dueDateStr,
                            'due_amount' => $dueAmt,
                            'paid_amount' => (float)($od['amount'] ?? 0),
                            'type_label' => 'Partial Balance Due',
                            'badge_class' => 'bg-warning text-dark',
                            'is_expired' => $isExpired,
                            'days_expired' => $daysDiff,
                            'months_expired' => $monthsDiff,
                        ];
                        $clientDueTotal += $dueAmt;
                    }
                }
            }

            // 2. Schedule Overdue EMIs
            if (!empty($schedule)) {
                foreach ($schedule as $vEmi) {
                    $emiNo = (int)$vEmi['emi_no'];
                    $maturity = $vEmi['maturity_status'] ?? '';
                    $dueAmt = (float)($vEmi['due_amount'] ?? 0);
                    $dueDateStr = $vEmi['due_date'] ?? null;

                    $isExpired = false;
                    $daysDiff = 0;
                    $monthsDiff = 0;
                    if (!empty($dueDateStr)) {
                        $dObj = new \DateTime($dueDateStr);
                        if ($dObj < $today) {
                            $isExpired = true;
                            $diff = $today->diff($dObj);
                            $daysDiff = $diff->days;
                            $monthsDiff = ($diff->y * 12) + $diff->m;
                        }
                    }

                    if (($maturity === 'due' || ($maturity === 'pending' && $isExpired)) && $dueAmt > 0) {
                        if (isset($clientDueEmis[$emiNo])) {
                            if (!empty($dueDateStr)) {
                                $clientDueEmis[$emiNo]['due_date'] = $dueDateStr;
                            }
                            $clientDueEmis[$emiNo]['months_expired'] = $vEmi['months_overdue'] ?? $clientDueEmis[$emiNo]['months_expired'];
                            $clientDueEmis[$emiNo]['is_expired'] = true;
                            if ($daysDiff > 0) $clientDueEmis[$emiNo]['days_expired'] = $daysDiff;
                        } else {
                            $clientDueEmis[$emiNo] = [
                                'emi_no' => $emiNo,
                                'due_date' => $dueDateStr,
                                'due_amount' => $dueAmt,
                                'paid_amount' => (float)($vEmi['paid_amount'] ?? 0),
                                'type_label' => $maturity === 'due' ? 'Expired / Overdue' : 'Due This Month',
                                'badge_class' => 'bg-danger text-white',
                                'is_expired' => $isExpired,
                                'days_expired' => $daysDiff,
                                'months_expired' => $vEmi['months_overdue'] ?? $monthsDiff,
                            ];
                            $clientDueTotal += $dueAmt;
                        }
                    } elseif (isset($clientDueEmis[$emiNo]) && ($maturity === 'future' || ($maturity === 'pending' && !$isExpired))) {
                        // CED rescheduled this EMI to a future date — no longer overdue
                        $clientDueTotal -= $clientDueEmis[$emiNo]['due_amount'];
                        unset($clientDueEmis[$emiNo]);
                    }
                }
            }
            ksort($clientDueEmis);

            $client['due_emis'] = $clientDueEmis;
            $client['due_count'] = count($clientDueEmis);
            $client['total_due_amount'] = $clientDueTotal;
            $client['due_summary_text'] = !empty($clientDueEmis) 
                ? implode(', ', array_map(fn($n) => "EMI #{$n}", array_keys($clientDueEmis)))
                : '';
        }
        unset($client);

        if ($isAjax) {
            $tableHtml = $this->renderCedRowsHtml($clients);
            $paginationHtml = $this->generateAjaxPagination($page, $totalPages, $totalClients, $limit, 'loadCedClientsPage');
            $startEntry = $totalClients > 0 ? (($page - 1) * $limit) + 1 : 0;
            $endEntry = min($totalClients, $page * $limit);
            $infoText = "Showing {$startEntry} to {$endEntry} of {$totalClients} entries";

            $paymentStats = $this->clientModel->getPaymentStatistics($effectiveProjectId);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'page' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $totalClients,
                'total_clients' => $totalClients,
                'full_payment_completed' => (int)($paymentStats['full_payment_completed'] ?? 0),
                'approved_bookings' => (int)($paymentStats['approved_bookings'] ?? 0),
                'units_booked' => (int)($paymentStats['units_booked'] ?? $paymentStats['total_units_booked'] ?? $paymentStats['approved_bookings'] ?? 0),
                'limit' => $limit,
                'table_html' => $tableHtml,
                'pagination_html' => $paginationHtml,
                'info_text' => $infoText
            ]);
            exit;
        }

        // Fast statistics for top cards
        $dueStats = $this->clientModel->getDueStatsSummary($effectiveProjectId);
        $paymentStats = $this->clientModel->getPaymentStatistics($effectiveProjectId);
        $collectionStats = $this->clientModel->getCollectionStatsSummary($effectiveProjectId);
        $projectCollectionBreakdown = $this->clientModel->getProjectCollectionBreakdown(!empty($assignedProjectIds) ? $assignedProjectIds : null);
        $projectModel = new Project($this->db);
        $projects = $projectModel->getAll();
        if (empty($projects)) {
            $projects = $this->clientModel->getProjectsForCED();
        }
        if (!empty($assignedProjectIds)) {
            $projects = array_values(array_filter($projects, fn($p) => in_array((int)$p['id'], $assignedProjectIds, true)));
        }

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
            "totalClients" => $totalClients,
            "totalPages" => $totalPages,
            "currentPage" => $page,
            "limit" => $limit,
            "search" => $search,
            "paymentStats" => $paymentStats,
            "collectionStats" => $collectionStats,
            "projectCollectionBreakdown" => $projectCollectionBreakdown,
            "projects" => $projects,
            "assignedProjectIds" => $assignedProjectIds,
            "selectedProjectId" => $selectedProjectId,
            "selectedVerificationStatus" => $verificationStatus,
            "dateType" => $dateType,
            "startDate" => $startDate,
            "endDate" => $endDate,
            "selectedProjectName" => $selectedProjectName,
            "flatStats" => $flatStats,
            "bookedFlats" => $bookedFlats,
            "availableFlats" => $availableFlats,
            "dueStats" => $dueStats,
            "conflictedUnits" => $this->clientModel->getConflictedUnits(),
            "conflictedBookingsDetail" => $this->clientModel->getConflictedBookingsDetail()
        ]);
    }

    /**
     * Render Agent Name and Booking Date(s) HTML (supporting multiple bookings)
     */
    protected function renderAgentAndBookingDatesHtml($client)
    {
        $agentName = htmlspecialchars($client['agent_name'] ?? 'N/A');
        $bookingItems = !empty($client['all_booking_dates']) ? explode(';;', $client['all_booking_dates']) : [];
        $uniqueBookings = [];

        foreach ($bookingItems as $bItem) {
            $parts = explode('||', $bItem);
            $dStr = !empty($parts[0]) ? date('d M, Y', strtotime($parts[0])) : '';
            if (!$dStr) continue;
            $flatName = trim($parts[1] ?? '');
            $projName = trim($parts[2] ?? '');
            $uniqueBookings[] = [
                'date' => $dStr,
                'flat' => $flatName,
                'project' => $projName
            ];
        }

        if (count($uniqueBookings) > 1) {
            $datesHtml = '<div class="d-flex flex-column gap-1 mt-1">';
            $visibleBookings = array_slice($uniqueBookings, 0, 3);
            $hiddenBookings = array_slice($uniqueBookings, 3);

            foreach ($visibleBookings as $ub) {
                $tooltip = htmlspecialchars($ub['project'] . ($ub['flat'] ? ' - ' . $ub['flat'] : ''));
                $datesHtml .= '<div class="text-nowrap" style="font-size: 11.5px;" title="' . $tooltip . '">';
                $datesHtml .= '<i class="fa-light fa-calendar-days me-1 text-primary"></i>' . $ub['date'];
                if (!empty($ub['flat'])) {
                    $datesHtml .= ' <span class="badge bg-light text-secondary border px-1" style="font-size: 9.5px; font-weight: 500;">' . htmlspecialchars($ub['flat']) . '</span>';
                }
                $datesHtml .= '</div>';
            }

            if (!empty($hiddenBookings)) {
                $collapseId = 'collapse_bd_' . ($client['lead_id'] ?? rand(1000, 9999)) . '_' . rand(100, 999);
                $datesHtml .= '<div class="collapse" id="' . $collapseId . '">';
                foreach ($hiddenBookings as $ub) {
                    $tooltip = htmlspecialchars($ub['project'] . ($ub['flat'] ? ' - ' . $ub['flat'] : ''));
                    $datesHtml .= '<div class="text-nowrap mt-1" style="font-size: 11.5px;" title="' . $tooltip . '">';
                    $datesHtml .= '<i class="fa-light fa-calendar-days me-1 text-primary"></i>' . $ub['date'];
                    if (!empty($ub['flat'])) {
                        $datesHtml .= ' <span class="badge bg-light text-secondary border px-1" style="font-size: 9.5px; font-weight: 500;">' . htmlspecialchars($ub['flat']) . '</span>';
                    }
                    $datesHtml .= '</div>';
                }
                $datesHtml .= '</div>';
                $datesHtml .= '<a href="javascript:void(0)" class="extra-small text-primary text-decoration-none fw-semibold" style="font-size: 10px;" data-bs-toggle="collapse" data-bs-target="#' . $collapseId . '">+' . count($hiddenBookings) . ' more</a>';
            }
            $datesHtml .= '</div>';
        } else {
            $bDateFormatted = !empty($client['booking_date']) ? date('d M, Y', strtotime($client['booking_date'])) : 'N/A';
            $datesHtml = '<small class="text-muted text-nowrap"><i class="fa-light fa-calendar-days me-1 text-primary"></i>' . $bDateFormatted . '</small>';
        }

        return '<div><div class="fw-semibold text-dark">' . $agentName . '</div>' . $datesHtml . '</div>';
    }

    /**
     * Render HTML rows for clients-cash-in table
     */
    public function renderCashInRowsHtml($clients, $conflictedUnits = [], $paymentType = '')
    {
        $colspan = !empty($paymentType) ? 12 : 10;
        if (empty($clients)) {
            return '<tr><td colspan="' . $colspan . '" class="text-center py-4 text-muted"><i class="fa-solid fa-inbox me-2"></i>No clients found matching your search or filters.</td></tr>';
        }
        $penaltiesByLead = $this->clientModel->computeLiveAccruedPenaltiesByLeadIds(array_column($clients, 'lead_id'));
        $html = '';
        foreach ($clients as $client) {
            $typeLastPayment = null;
            $typePendingCount = 0;
            $typeLatestVer = null;
            $typePaidAmt = 0;

            if ($paymentType === 'construction_fee') {
                $typeLastPayment  = $client['construction_last_payment_date'] ?? null;
                $typePendingCount = (int)($client['construction_pending_payments'] ?? 0);
                $typeLatestVer    = $client['construction_latest_verification_status'] ?? null;
                $typePaidAmt      = (float)($client['construction_paid'] ?? 0);
            } elseif ($paymentType === 'land_share') {
                $typeLastPayment  = $client['land_share_last_payment_date'] ?? null;
                $typePendingCount = (int)($client['land_share_pending_payments'] ?? 0);
                $typeLatestVer    = $client['land_share_latest_verification_status'] ?? null;
                $typePaidAmt      = (float)($client['land_share_paid'] ?? 0);
            } elseif ($paymentType === 'development_charge') {
                $typeLastPayment  = $client['dev_charge_last_payment_date'] ?? null;
                $typePendingCount = (int)($client['dev_charge_pending_payments'] ?? 0);
                $typeLatestVer    = $client['dev_charge_latest_verification_status'] ?? null;
                $typePaidAmt      = (float)($client['dev_charge_paid'] ?? 0);
            } elseif ($paymentType === 'shop_service_charge') {
                $typeLastPayment  = $client['shop_service_charge_last_payment_date'] ?? null;
                $typePendingCount = (int)($client['shop_service_charge_pending_payments'] ?? 0);
                $typeLatestVer    = $client['shop_service_charge_latest_verification_status'] ?? null;
                $typePaidAmt      = (float)($client['shop_service_charge_paid'] ?? 0);
            } else {
                $typeLastPayment  = $client['last_payment_date'] ?? null;
                $typePendingCount = (int)($client['total_pending_payments'] ?? 0);
                $typeLatestVer    = $client['latest_verification_status'] ?? null;
                $typePaidAmt      = (float)($client['total_paid'] ?? 0);
            }

            $isPending    = ($typePendingCount > 0);
            $isNoPayment  = ($typePaidAmt <= 0 && empty($typeLastPayment));
            $isRejected   = !$isPending && !$isNoPayment && ($typeLatestVer === 'rejected');
            $isVerified   = !$isPending && !$isNoPayment && ($typeLatestVer !== 'rejected');

            $rowClass     = $isPending ? 'table-pending-highlight' : ($isRejected ? 'table-danger' : '');
            $badgeClass   = $isVerified ? 'bg-success' : ($isRejected ? 'bg-danger' : ($isPending ? 'bg-warning text-dark' : 'bg-secondary'));
            $badgeIcon    = $isVerified ? 'fa-check-circle' : ($isRejected ? 'fa-times-circle' : ($isPending ? 'fa-clock' : 'fa-minus'));
            $verText      = $isVerified ? 'Verified' : ($isRejected ? 'Rejected' : ($isPending ? 'Pending' : 'No Payment'));

            $clientHasConflict = false;
            if (!empty($conflictedUnits) && !empty($client['project_flats_combined'])) {
                $allProps = explode(';;', $client['project_flats_combined']);
                foreach ($allProps as $ap) {
                    $apParts = explode('||', $ap);
                    $chkK = strtolower(trim($apParts[0] ?? '')) . '||' . strtolower(trim($apParts[1] ?? ''));
                    if (isset($conflictedUnits[$chkK])) {
                        $clientHasConflict = true;
                        break;
                    }
                }
            }

            $html .= '<tr class="' . $rowClass . ' ' . ($clientHasConflict ? 'row-conflicted-unit' : '') . '" data-has-conflict="' . ($clientHasConflict ? '1' : '0') . '" ' . ($clientHasConflict ? 'style="background-color: #fff9f9;"' : '') . '>';
            $clientNameVal = !empty(trim($client['client_name'] ?? '')) ? htmlspecialchars(trim($client['client_name'])) : '<span class="text-muted fst-italic">N/A</span>';
            $html .= '<td class="col-sticky-name">' . $clientNameVal;
            if ($clientHasConflict) {
                $html .= '<span class="badge bg-danger text-white ms-1" style="font-size: 10px;" title="This client has a double-booked unit"><i class="fa-solid fa-triangle-exclamation"></i> Conflict</span>';
            }
            $html .= '</td>';

            // Merged Phone & Email (Sticky)
            $rawPhone = !empty($client['phone']) ? $client['phone'] : (!empty($client['phone_number']) ? $client['phone_number'] : '');
            $cleanPhone = preg_replace('/^p:/i', '', trim($rawPhone));
            $phoneVal = ($cleanPhone !== '' && $cleanPhone !== 'N/A') ? htmlspecialchars($cleanPhone) : '';
            $emailVal = !empty($client['email']) && $client['email'] !== 'N/A' ? htmlspecialchars($client['email']) : '';
            $html .= '<td class="col-sticky-contact">';
            $html .= '<div class="client-contact-cell">';
            if ($phoneVal) {
                $html .= '<div class="contact-phone text-nowrap fw-semibold"><a href="tel:' . $phoneVal . '" class="text-dark text-decoration-none"><i class="fa-solid fa-phone text-primary me-1" style="font-size: 11px;"></i>' . $phoneVal . '</a></div>';
            } else {
                $html .= '<div class="contact-phone text-muted small text-nowrap"><i class="fa-solid fa-phone text-muted me-1" style="font-size: 11px;"></i>N/A</div>';
            }
            if ($emailVal) {
                $html .= '<div class="contact-email text-muted small text-truncate" style="max-width: 175px;" title="' . $emailVal . '"><a href="mailto:' . $emailVal . '" class="text-muted text-decoration-none"><i class="fa-solid fa-envelope me-1" style="font-size: 10px;"></i>' . $emailVal . '</a></div>';
            }
            $html .= '</div>';
            $html .= '</td>';
            
            // Project & Flats chips
            $html .= '<td><div class="d-flex flex-wrap gap-2">';
            if (!empty($client['project_flats_combined'])) {
                $properties = explode(';;', $client['project_flats_combined']);
                $totalProps = count($properties);
                $visibleProps = array_slice($properties, 0, 3);
                $hiddenProps = array_slice($properties, 3);
                $clientId = $client['lead_id'] ?? uniqid();

                foreach ($visibleProps as $prop) {
                    $parts = explode('||', $prop);
                    $pName = $parts[0] ?? '';
                    $fName = $parts[1] ?? '';
                    $cKey = strtolower(trim($pName)) . '||' . strtolower(trim($fName));
                    $isDup = isset($conflictedUnits[$cKey]);
                    $dupInfo = $isDup ? $conflictedUnits[$cKey] : null;

                    $html .= '<div class="property-chip" ' . ($isDup ? 'style="border: 1px solid #dc3545; background: #fff5f5;"' : '') . '>';
                    $html .= '<span class="project-name" title="' . htmlspecialchars($pName) . '"><i class="fa-solid fa-building me-1"></i>' . htmlspecialchars($pName) . '</span>';
                    if (!empty($fName)) {
                        $html .= '<span class="flat-name ' . ($isDup ? 'text-danger fw-bold' : '') . '" title="Flat/Unit">' . htmlspecialchars($fName) . '</span>';
                    }
                    if ($isDup) {
                        $html .= '<a href="javascript:void(0)" class="badge bg-danger text-white ms-1 text-decoration-none" style="font-size: 10px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#conflictResolutionModal" title="Double Booked with: ' . htmlspecialchars($dupInfo['client_names'] ?? '') . ' (' . ($dupInfo['booking_count'] ?? '') . ' Bookings) - Click to Manage"><i class="fa-solid fa-triangle-exclamation me-1"></i>Double Booked (' . ($dupInfo['booking_count'] ?? '') . ')</a>';
                    }
                    $html .= '</div>';
                }

                if ($totalProps > 3) {
                    $collapseId = "collapse_props_" . $clientId;
                    $html .= '<div class="collapse w-100" id="' . $collapseId . '"><div class="d-flex flex-wrap gap-2 mt-1">';
                    foreach ($hiddenProps as $prop) {
                        $parts = explode('||', $prop);
                        $pName = $parts[0] ?? '';
                        $fName = $parts[1] ?? '';
                        $cKey = strtolower(trim($pName)) . '||' . strtolower(trim($fName));
                        $isDup = isset($conflictedUnits[$cKey]);
                        $dupInfo = $isDup ? $conflictedUnits[$cKey] : null;

                        $html .= '<div class="property-chip" ' . ($isDup ? 'style="border: 1px solid #dc3545; background: #fff5f5;"' : '') . '>';
                        $html .= '<span class="project-name" title="' . htmlspecialchars($pName) . '"><i class="fa-solid fa-building me-1"></i>' . htmlspecialchars($pName) . '</span>';
                        if (!empty($fName)) {
                            $html .= '<span class="flat-name ' . ($isDup ? 'text-danger fw-bold' : '') . '" title="Flat/Unit">' . htmlspecialchars($fName) . '</span>';
                        }
                        if ($isDup) {
                            $html .= '<a href="javascript:void(0)" class="badge bg-danger text-white ms-1 text-decoration-none" style="font-size: 10px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#conflictResolutionModal" title="Double Booked with: ' . htmlspecialchars($dupInfo['client_names'] ?? '') . ' (' . ($dupInfo['booking_count'] ?? '') . ' Bookings) - Click to Manage"><i class="fa-solid fa-triangle-exclamation me-1"></i>Double Booked (' . ($dupInfo['booking_count'] ?? '') . ')</a>';
                        }
                        $html .= '</div>';
                    }
                    $html .= '</div></div>';
                    $html .= '<button class="btn btn-sm btn-link text-decoration-none p-0 fw-bold mt-1 w-100 text-start view-more-btn collapsed" style="font-size: 11.5px; color: #1d4ed8;" type="button" data-bs-toggle="collapse" data-bs-target="#' . $collapseId . '" aria-expanded="false" aria-controls="' . $collapseId . '">';
                    $html .= '<span class="show-more-text">View ' . ($totalProps - 3) . ' more units <i class="fa-solid fa-caret-down ms-1"></i></span>';
                    $html .= '<span class="show-less-text d-none">View less <i class="fa-solid fa-caret-up ms-1"></i></span>';
                    $html .= '</button>';
                }
            } else {
                $html .= '<span class="badge bg-light text-muted border">No Properties</span>';
            }
            $html .= '</div></td>';

            $html .= '<td>' . $this->renderAgentAndBookingDatesHtml($client) . '</td>';

            if (!empty($paymentType)) {
                $targetVal = 0;
                $paidVal = 0;
                $dueVal = 0;
                if ($paymentType === 'full_payment') {
                    $targetVal = (float)($client['total_price_target'] ?? $client['total_agreement_value'] ?? 0);
                    $paidVal = (float)($client['total_paid'] ?? 0);
                    $dueVal = (float)($client['overall_due'] ?? 0);
                } elseif ($paymentType === 'land_share') {
                    $targetVal = (float)($client['total_land_share_target'] ?? 0);
                    $paidVal = (float)($client['land_share_paid'] ?? 0);
                    $dueVal = (float)($client['land_share_due'] ?? 0);
                } elseif ($paymentType === 'development_charge') {
                    $targetVal = (float)($client['total_dev_charge_target'] ?? 0);
                    $paidVal = (float)($client['dev_charge_paid'] ?? 0);
                    $dueVal = (float)($client['dev_charge_due'] ?? 0);
                } elseif ($paymentType === 'shop_service_charge') {
                    $targetVal = (float)($client['total_shop_service_charge_target'] ?? 0);
                    $paidVal = (float)($client['shop_service_charge_paid'] ?? 0);
                    $dueVal = (float)($client['shop_service_charge_due'] ?? 0);
                } elseif ($paymentType === 'construction_fee') {
                    $targetVal = (float)($client['total_construction_target'] ?? 0);
                    $paidVal = (float)($client['construction_paid'] ?? 0);
                    $dueVal = (float)($client['construction_due'] ?? 0);
                }
                $rowDiscount = 0;
                $discLabel = 'Disc';
                if ($paymentType === 'full_payment') {
                    $rowDiscount = (float)($client['total_discount'] ?? 0) + (float)($client['total_bulk_emi_discount'] ?? 0);
                    $discLabel = 'Disc';
                } elseif ($paymentType === 'land_share') {
                    $rowDiscount = (float)($client['total_discount'] ?? 0);
                    $discLabel = 'Disc';
                } elseif ($paymentType === 'construction_fee') {
                    $rowDiscount = (float)($client['total_bulk_emi_discount'] ?? 0);
                    $discLabel = 'EMI Disc';
                }
                $html .= '<td class="fw-bold text-dark text-nowrap">TK.' . number_format($targetVal, 2);
                if ($rowDiscount > 0) {
                    $html .= '<div style="font-size: 10.5px; color: #d97706; font-weight: 500;">(' . $discLabel . ': Tk. ' . number_format($rowDiscount, 2) . ')</div>';
                }
                $html .= '</td>';
                $html .= '<td class="text-nowrap"><span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11.5px;">TK.' . number_format($paidVal, 2) . '</span></td>';
                $html .= '<td class="text-nowrap"><span class="badge ' . ($dueVal > 0 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-muted border') . ' px-2 py-1" style="font-size: 11.5px;">TK.' . number_format($dueVal, 2) . '</span></td>';
            } else {
                $html .= '<td>TK.' . number_format($client['total_paid'] ?? 0, 2) . '</td>';
            }

            $leadPenalty = (float)($penaltiesByLead[(int)($client['lead_id'] ?? 0)] ?? 0);
            if ($leadPenalty > 0) {
                $html .= '<td class="text-nowrap"><span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 11.5px;"><i class="fa-solid fa-triangle-exclamation me-1"></i>TK.' . number_format($leadPenalty, 2) . '</span></td>';
            } else {
                $html .= '<td class="text-muted text-nowrap small">—</td>';
            }

            $html .= '<td>' . ($typeLastPayment ? date('d M, Y', strtotime($typeLastPayment)) : 'No Payment') . '</td>';
            $html .= '<td><span class="badge ' . $badgeClass . '"><i class="fa-light ' . $badgeIcon . ' me-1"></i>' . $verText . '</span></td>';
            $html .= '<td><button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openProfileModal(' . (int)$client['lead_id'] . ')" title="View Client Profile" style="font-size:12px;"><i class="fa-light fa-id-card me-1"></i>Profile</button></td>';
            $escapedName = htmlspecialchars(trim(preg_replace('/\s+/', ' ', (string)($client['client_name'] ?? ''))), ENT_QUOTES);
            $stmtBtn = !empty($client['booking_id']) ? '<a href="' . $GLOBALS['base_url'] . '/client-statement/' . (int)$client['booking_id'] . '" target="_blank" class="table__icon" style="color: #4f46e5; border: 1px solid #c7d2fe; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;" title="Print Client Statement"><i class="fa-solid fa-print"></i></a>' : '';
            $html .= '<td><div class="d-flex align-items-center justify-content-start gap-1">' . $stmtBtn . '<button class="table__icon download" onclick="openProjectsModal(' . (int)$client['lead_id'] . ', \'' . $escapedName . '\')" title="View Projects"><i class="fa-regular fa-eye"></i></button></div></td>';
            $html .= '</tr>';
        }
        return $html;
    }

    /**
     * Render table header row HTML based on payment type
     */
    public function renderCashInTableHeadHtml($paymentType = '')
    {
        $targetLabel = 'Total Value';
        if ($paymentType === 'land_share') {
            $targetLabel = 'Land Share Value';
        } elseif ($paymentType === 'development_charge') {
            $targetLabel = 'Dev Charge Total';
        } elseif ($paymentType === 'shop_service_charge') {
            $targetLabel = 'Shop Service Charge';
        } elseif ($paymentType === 'construction_fee') {
            $targetLabel = 'Construction Value';
        }

        $html = '<th class="col-sticky-name">Client Name</th>';
        $html .= '<th class="col-sticky-contact">Phone &amp; Email</th>';
        $html .= '<th>Project &amp; Flats</th>';
        $html .= '<th>Booking Agent(s) &amp; Date</th>';

        if (!empty($paymentType)) {
            $html .= '<th class="text-nowrap">' . $targetLabel . ' (TK.)</th>';
            $html .= '<th class="text-nowrap">Total Paid (TK.)</th>';
            $html .= '<th class="text-nowrap">Total Due (TK.)</th>';
        } else {
            $html .= '<th>Total Paid</th>';
        }

        $html .= '<th class="text-nowrap">Current Penalty</th>';
        $html .= '<th>Last Payment</th>';
        $html .= '<th>Verification</th>';
        $html .= '<th>Profile</th>';
        $html .= '<th>Action</th>';

        return $html;
    }

    /**
     * Print filtered statement of clients cash in
     */
    public function printClientsStatement()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int) $_GET['project_id'] : null;
        $printAssignedIds = [];
        $effectivePrintProjectId = $this->getEffectiveCedProjectFilter($selectedProjectId, $printAssignedIds);
        $verificationStatus = isset($_GET['verification_status']) && $_GET['verification_status'] !== '' ? $_GET['verification_status'] : null;
        $search = trim($_GET['search'] ?? '');
        $hasConflict = !empty($_GET['has_conflict']);
        $paymentType = trim($_GET['payment_type'] ?? '');
        $allowedPaymentTypes = ['full_payment', 'land_share', 'development_charge', 'shop_service_charge', 'construction_fee'];
        if (!in_array($paymentType, $allowedPaymentTypes, true)) {
            $paymentType = '';
        }

        $dateType = trim($_GET['date_type'] ?? '');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');
        if (!in_array($dateType, ['booking_date', 'last_payment_date'])) {
            $dateType = null;
        }
        if (empty($startDate)) $startDate = null;
        if (empty($endDate)) $endDate = null;

        // Fetch matching clients (up to 1500 for printing)
        $clients = $this->clientModel->getAllClients($effectivePrintProjectId, $verificationStatus, $search, null, 0, $hasConflict, $dateType, $startDate, $endDate, $paymentType);

        // Project Name
        $projectName = 'All Projects';
        if ($selectedProjectId) {
            $pStmt = $this->db->prepare("SELECT name FROM projects WHERE id = ?");
            if ($pStmt) {
                $pStmt->bind_param("i", $selectedProjectId);
                $pStmt->execute();
                $pRes = $pStmt->get_result();
                if ($pRow = $pRes->fetch_assoc()) {
                    $projectName = $pRow['name'];
                }
                $pStmt->close();
            }
        }

        // Totals (with complete Verified vs Pending & Net vs Verified Due tracking)
        $grandTarget = 0;
        $grandPaid = 0;
        $grandPaidVerified = 0;
        $grandPaidUnverified = 0;
        $grandDue = 0;
        $grandDueVerified = 0;
        $grandDiscount = 0;
        $grandEmiDiscount = 0;

        foreach ($clients as $c) {
            $grandDiscount += (float)($c['total_discount'] ?? 0);
            $grandEmiDiscount += (float)($c['total_bulk_emi_discount'] ?? 0);

            $pVerified = (float)($c['total_paid_verified'] ?? 0);
            $pUnverified = (float)($c['total_paid_unverified'] ?? 0);
            $pAll = (float)($c['total_paid'] ?? 0);
            $dNet = (float)($c['overall_due'] ?? 0);
            $dVerified = (float)($c['overall_due_verified'] ?? ($dNet + $pUnverified));

            if ($paymentType === 'full_payment' || empty($paymentType)) {
                $grandTarget += (float)($c['total_price_target'] ?? $c['total_agreement_value'] ?? 0);
                $grandPaid += $pAll;
                $grandPaidVerified += $pVerified;
                $grandPaidUnverified += $pUnverified;
                $grandDue += $dNet;
                $grandDueVerified += $dVerified;
            } elseif ($paymentType === 'land_share') {
                $grandTarget += (float)($c['total_land_share_target'] ?? 0);
                $grandPaid += (float)($c['land_share_paid'] ?? 0);
                $grandPaidVerified += (float)($c['land_share_paid_verified'] ?? $c['land_share_paid'] ?? 0);
                $grandPaidUnverified += (float)($c['land_share_paid_unverified'] ?? 0);
                $grandDue += (float)($c['land_share_due'] ?? 0);
                $grandDueVerified += (float)($c['land_share_due_verified'] ?? $c['land_share_due'] ?? 0);
            } elseif ($paymentType === 'development_charge') {
                $grandTarget += (float)($c['total_dev_charge_target'] ?? 0);
                $grandPaid += (float)($c['dev_charge_paid'] ?? 0);
                $grandPaidVerified += (float)($c['dev_charge_paid_verified'] ?? $c['dev_charge_paid'] ?? 0);
                $grandPaidUnverified += (float)($c['dev_charge_paid_unverified'] ?? 0);
                $grandDue += (float)($c['dev_charge_due'] ?? 0);
                $grandDueVerified += (float)($c['dev_charge_due_verified'] ?? $c['dev_charge_due'] ?? 0);
            } elseif ($paymentType === 'shop_service_charge') {
                $grandTarget += (float)($c['total_shop_service_charge_target'] ?? 0);
                $grandPaid += (float)($c['shop_service_charge_paid'] ?? 0);
                $grandPaidVerified += (float)($c['shop_service_charge_paid_verified'] ?? $c['shop_service_charge_paid'] ?? 0);
                $grandPaidUnverified += (float)($c['shop_service_charge_paid_unverified'] ?? 0);
                $grandDue += (float)($c['shop_service_charge_due'] ?? 0);
                $grandDueVerified += (float)($c['shop_service_charge_due_verified'] ?? $c['shop_service_charge_due'] ?? 0);
            } elseif ($paymentType === 'construction_fee') {
                $grandTarget += (float)($c['total_construction_target'] ?? 0);
                $grandPaid += (float)($c['construction_paid'] ?? 0);
                $grandPaidVerified += (float)($c['construction_paid_verified'] ?? $c['construction_paid'] ?? 0);
                $grandPaidUnverified += (float)($c['construction_paid_unverified'] ?? 0);
                $grandDue += (float)($c['construction_due'] ?? 0);
                $grandDueVerified += (float)($c['construction_due_verified'] ?? $c['construction_due'] ?? 0);
            }
        }

        $viewData = [
            'clients' => $clients,
            'paymentType' => $paymentType,
            'projectName' => $projectName,
            'selectedProjectId' => $selectedProjectId,
            'verificationStatus' => $verificationStatus,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dateType' => $dateType,
            'grandTarget' => $grandTarget,
            'grandPaid' => $grandPaid,
            'grandPaidVerified' => $grandPaidVerified,
            'grandPaidUnverified' => $grandPaidUnverified,
            'grandDue' => $grandDue,
            'grandDueVerified' => $grandDueVerified,
            'grandDiscount' => $grandDiscount,
            'grandEmiDiscount' => $grandEmiDiscount
        ];

        extract($viewData);
        include __DIR__ . '/../views/accounts/cashIn/clients-statement-print.php';
        exit;
    }

    /**
     * Render HTML rows for clients-cashin-view table
     */
    public function renderCedRowsHtml($clients)
    {
        if (empty($clients)) {
            return '<tr><td colspan="11" class="text-center py-4 text-muted"><i class="fa-solid fa-inbox me-2"></i>No clients found matching your search or filters.</td></tr>';
        }
        $penaltiesByLead = $this->clientModel->computeLiveAccruedPenaltiesByLeadIds(array_column($clients, 'lead_id'));
        $html = '';
        foreach ($clients as $client) {
            $html .= '<tr>';
            $clientNameVal = !empty(trim($client['client_name'] ?? '')) ? htmlspecialchars(trim($client['client_name'])) : '<span class="text-muted fst-italic">N/A</span>';
            $html .= '<td class="col-sticky-name">' . $clientNameVal . '</td>';

            // Merged Phone & Email (Sticky)
            $rawPhone = !empty($client['phone']) ? $client['phone'] : (!empty($client['phone_number']) ? $client['phone_number'] : '');
            $cleanPhone = preg_replace('/^p:/i', '', trim($rawPhone));
            $phoneVal = ($cleanPhone !== '' && $cleanPhone !== 'N/A') ? htmlspecialchars($cleanPhone) : '';
            $emailVal = !empty($client['email']) && $client['email'] !== 'N/A' ? htmlspecialchars($client['email']) : '';
            $html .= '<td class="col-sticky-contact">';
            $html .= '<div class="client-contact-cell">';
            if ($phoneVal) {
                $html .= '<div class="contact-phone text-nowrap fw-semibold"><a href="tel:' . $phoneVal . '" class="text-dark text-decoration-none"><i class="fa-solid fa-phone text-primary me-1" style="font-size: 11px;"></i>' . $phoneVal . '</a></div>';
            } else {
                $html .= '<div class="contact-phone text-muted small text-nowrap"><i class="fa-solid fa-phone text-muted me-1" style="font-size: 11px;"></i>N/A</div>';
            }
            if ($emailVal) {
                $html .= '<div class="contact-email text-muted small text-truncate" style="max-width: 175px;" title="' . $emailVal . '"><a href="mailto:' . $emailVal . '" class="text-muted text-decoration-none"><i class="fa-solid fa-envelope me-1" style="font-size: 10px;"></i>' . $emailVal . '</a></div>';
            }
            $html .= '</div>';
            $html .= '</td>';

            $html .= '<td>' . $this->renderAgentAndBookingDatesHtml($client) . '</td>';

            // Project & flats chips
            $html .= '<td><div class="d-flex flex-wrap gap-2">';
            if (!empty($client['project_flats_combined'])) {
                $properties = explode(';;', $client['project_flats_combined']);
                $totalProps = count($properties);
                $visibleProps = array_slice($properties, 0, 3);
                $hiddenProps = array_slice($properties, 3);
                $clientId = $client['lead_id'] ?? uniqid();

                foreach ($visibleProps as $prop) {
                    $parts = explode('||', $prop);
                    $pName = $parts[0] ?? '';
                    $fName = $parts[1] ?? '';
                    $html .= '<div class="property-chip">';
                    $html .= '<span class="project-name" title="' . htmlspecialchars($pName) . '"><i class="fa-solid fa-building me-1"></i>' . htmlspecialchars($pName) . '</span>';
                    if (!empty($fName)) {
                        $html .= '<span class="flat-name" title="Flat/Unit">' . htmlspecialchars($fName) . '</span>';
                    }
                    $html .= '</div>';
                }

                if ($totalProps > 3) {
                    $collapseId = "collapse_props_" . $clientId;
                    $html .= '<div class="collapse w-100" id="' . $collapseId . '"><div class="d-flex flex-wrap gap-2 mt-1">';
                    foreach ($hiddenProps as $prop) {
                        $parts = explode('||', $prop);
                        $pName = $parts[0] ?? '';
                        $fName = $parts[1] ?? '';
                        $html .= '<div class="property-chip">';
                        $html .= '<span class="project-name" title="' . htmlspecialchars($pName) . '"><i class="fa-solid fa-building me-1"></i>' . htmlspecialchars($pName) . '</span>';
                        if (!empty($fName)) {
                            $html .= '<span class="flat-name" title="Flat/Unit">' . htmlspecialchars($fName) . '</span>';
                        }
                        $html .= '</div>';
                    }
                    $html .= '</div></div>';
                    $html .= '<button class="btn btn-sm btn-link text-decoration-none p-0 fw-bold mt-1 w-100 text-start view-more-btn collapsed" style="font-size: 11.5px; color: #1d4ed8;" type="button" data-bs-toggle="collapse" data-bs-target="#' . $collapseId . '" aria-expanded="false" aria-controls="' . $collapseId . '">';
                    $html .= '<span class="show-more-text">View ' . ($totalProps - 3) . ' more units <i class="fa-solid fa-caret-down ms-1"></i></span>';
                    $html .= '<span class="show-less-text d-none">View less <i class="fa-solid fa-caret-up ms-1"></i></span>';
                    $html .= '</button>';
                }
            } else {
                $html .= '<span class="badge bg-light text-muted border">No Properties</span>';
            }
            $html .= '</div></td>';

            $html .= '<td>TK.' . number_format($client['total_agreement_value'] ?? 0, 2) . '</td>';
            $html .= '<td>TK.' . number_format($client['total_paid'] ?? 0, 2) . '</td>';
            if (!empty($client['last_payment_date'])) {
                $formattedDate = date('d M, Y', strtotime($client['last_payment_date']));
                $creatorHtml = '';
                if (!empty($client['last_payment_creator_name'])) {
                    $creatorHtml = '<div class="extra-small text-muted mt-0.5 text-nowrap" style="font-size: 11px;"><i class="fa-solid fa-user-pen text-primary me-1"></i>By: ' . htmlspecialchars($client['last_payment_creator_name']) . '</div>';
                }
                $html .= '<td><span class="fw-semibold text-dark text-nowrap">' . $formattedDate . '</span>' . $creatorHtml . '</td>';
            } else {
                $html .= '<td><span class="text-muted extra-small">No Payment</span></td>';
            }

            // Current Month / Due status
            $cms = $client['current_month_status'] ?? 'N/A';
            $hasDueEmis = !empty($client['due_emis']);
            $html .= '<td>';
            if ($hasDueEmis) {
                $html .= '<span class="badge bg-danger text-white mb-1" title="Due EMIs: ' . htmlspecialchars($client['due_summary_text']) . '">';
                $html .= '<i class="fa-solid fa-triangle-exclamation me-1"></i>Due: ' . htmlspecialchars($client['due_summary_text']) . '</span>';
                $html .= '<small class="d-block text-danger fw-bold" style="font-size: 11px;">Tk. ' . number_format($client['total_due_amount'] ?? 0, 2) . '</small>';
            } elseif ($cms === 'paid') {
                $html .= '<span class="badge bg-success">Paid</span>';
            } elseif ($cms === 'matured') {
                $html .= '<span class="badge bg-info text-dark" style="background-color: #0dcaf0; color: #000;">Matured</span>';
            } elseif ($cms === 'pending') {
                $html .= '<span class="badge bg-warning text-dark">Pending</span>';
            } elseif ($cms === 'due') {
                $html .= '<span class="badge bg-danger">Due</span>';
            } elseif ($cms === 'future') {
                $html .= '<span class="badge bg-secondary">Future</span>';
            } else {
                $html .= '<span class="text-muted">-</span>';
            }
            $html .= '</td>';

            // Current Penalty (Live Accrued 5% Overdue Penalty)
            $cedLeadPenalty = (float)($penaltiesByLead[(int)($client['lead_id'] ?? 0)] ?? 0);
            if ($cedLeadPenalty > 0) {
                $html .= '<td class="text-nowrap"><span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 11.5px;"><i class="fa-solid fa-triangle-exclamation me-1"></i>TK.' . number_format($cedLeadPenalty, 2) . '</span></td>';
            } else {
                $html .= '<td class="text-muted text-nowrap small">—</td>';
            }

            $html .= '<td><button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openProfileModal(' . (int)$client['lead_id'] . ')" title="View Client Profile" style="font-size:12px;"><i class="fa-light fa-id-card me-1"></i>Profile</button></td>';

            $escapedName = htmlspecialchars(trim(preg_replace('/\s+/', ' ', (string)($client['client_name'] ?? ''))), ENT_QUOTES);
            $html .= '<td><div class="d-flex align-items-center justify-content-start gap-2">';
            $html .= '<button class="table__icon download" onclick="openProjectsModal(' . (int)$client['lead_id'] . ', \'' . $escapedName . '\')" title="View Projects"><i class="fa-regular fa-eye"></i></button>';

            if (!empty($client['booking_id'])) {
                $fCount = (int)($client['total_followup_count'] ?? 0);
                $html .= '<button type="button" onclick="openClientEmiFollowupModal(' . (int)$client['booking_id'] . ', \'' . $escapedName . '\', ' . (int)($client['total_emi_count'] ?? 36) . ', ' . ((int)$client['lead_id'] ?: 'null') . ')" class="btn btn-sm btn-outline-primary py-1 px-2 text-nowrap" style="font-size:11px;" title="Add / View Follow Up Notes">';
                $html .= '<i class="fa-light fa-comment-dots me-1"></i>Follow Up';
                $html .= '<span class="badge ' . ($fCount > 0 ? 'bg-primary' : 'bg-secondary') . ' rounded-pill ms-1 followup-count-badge" data-booking-id="' . (int)$client['booking_id'] . '" style="font-size:10px;">' . $fCount . '</span>';
                $html .= '</button>';
            }
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        return $html;
    }

    /**
     * Render HTML rows for Due EMIs modal
     */
    public function renderDueModalRowsHtml($dueClientsList)
    {
        if (empty($dueClientsList)) {
            return '<tr><td colspan="8" class="text-center py-4 text-muted"><i class="fa-solid fa-check-circle text-success me-2"></i>No clients found with overdue or due EMIs.</td></tr>';
        }
        $html = '';
        foreach ($dueClientsList as $dIdx => $dClient) {
            $html .= '<tr class="due-client-row">';
            $html .= '<td class="text-center text-muted fw-bold">' . ($dIdx + 1) . '</td>';
            $html .= '<td class="fw-bold text-dark due-client-name">' . htmlspecialchars($dClient['client_name']) . '</td>';
            $html .= '<td class="due-client-phone"><a href="tel:' . htmlspecialchars($dClient['phone']) . '" class="text-decoration-none text-primary"><i class="fa-solid fa-phone me-1" style="font-size: 11px;"></i>' . htmlspecialchars($dClient['phone']) . '</a></td>';
            
            $html .= '<td class="due-client-project">';
            if (!empty($dClient['project_flats'])) {
                $props = explode(';;', $dClient['project_flats']);
                foreach ($props as $prop) {
                    $parts = explode('||', $prop);
                    $pName = $parts[0] ?? '';
                    $fName = $parts[1] ?? '';
                    $html .= '<div class="property-chip mb-1"><span class="project-name"><i class="fa-solid fa-building me-1"></i>' . htmlspecialchars($pName) . '</span>';
                    if (!empty($fName)) {
                        $html .= '<span class="flat-name">' . htmlspecialchars($fName) . '</span>';
                    }
                    $html .= '</div>';
                }
            } else {
                $html .= '<span class="text-muted">-</span>';
            }
            $html .= '</td>';

            $html .= '<td><div class="d-flex flex-wrap gap-1">';
            foreach ($dClient['due_emis'] as $dEmi) {
                $html .= '<span class="badge ' . ($dEmi['badge_class'] ?? 'bg-danger text-white') . ' py-1 px-2" style="font-size: 11px;" title="' . ($dEmi['type_label'] ?? '') . ': Tk. ' . number_format($dEmi['due_amount'], 2) . '">';
                $html .= 'EMI #' . $dEmi['emi_no'] . ': Tk. ' . number_format($dEmi['due_amount'], 0);
                $html .= '</span>';
            }
            $html .= '</div></td>';

            $html .= '<td>';
            foreach ($dClient['due_emis'] as $dEmi) {
                $html .= '<div class="small text-nowrap" style="font-size: 11px;"><strong class="text-dark">#' . $dEmi['emi_no'] . ':</strong> ';
                $html .= !empty($dEmi['due_date']) ? date('d M, Y', strtotime($dEmi['due_date'])) : 'N/A';
                if (!empty($dEmi['is_expired'])) {
                    $html .= ' <span class="text-danger fw-semibold">(' . (int)$dEmi['days_expired'] . 'd ago)</span>';
                } else {
                    $html .= ' <span class="text-warning fw-semibold">(this month)</span>';
                }
                $html .= '</div>';
            }
            $html .= '</td>';

            $html .= '<td class="text-end fw-bold text-danger font-monospace fs-14">Tk. ' . number_format($dClient['total_due_amount'], 2) . '</td>';
            $html .= '<td class="text-center">';
            if (!empty($dClient['booking_id'])) {
                $html .= '<a href="' . $GLOBALS['base_url'] . '/clients-cash-in-history-view/' . (int)$dClient['booking_id'] . '" class="btn btn-xs btn-outline-primary py-1 px-2" title="View Cash In History & Pay"><i class="fa-solid fa-eye me-1"></i>View</a>';
            } else {
                $html .= '<span class="text-muted">-</span>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }
        return $html;
    }

    /**
     * Generate AJAX pagination controls
     */
    public function generateAjaxPagination($currentPage, $totalPages, $totalRecords, $perPage, $callbackFn = 'loadCashInPage')
    {
        $perPage = (int)$perPage;
        if ($perPage <= 0 || ($totalPages <= 1 && $totalRecords <= $perPage)) {
            return '';
        }

        $startEntry = $totalRecords > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
        $endEntry = min($totalRecords, $currentPage * $perPage);

        $html = '<div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2 w-100">';
        $html .= '<div class="text-muted fs-13 font-semibold pagination-info-text">';
        $html .= "Showing <span class='text-dark fw-bold'>{$startEntry}</span> to <span class='text-dark fw-bold'>{$endEntry}</span> of <span class='text-dark fw-bold'>{$totalRecords}</span> entries";
        $html .= '</div>';

        $html .= '<nav aria-label="Page navigation">';
        $html .= '<ul class="pagination pagination-sm mb-0 gap-1">';

        // Previous button
        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            $html .= "<li class='page-item'><a class='page-link ajax-page-link rounded-2 px-2.5' href='javascript:void(0);' onclick='{$callbackFn}({$prevPage}); return false;' data-page='{$prevPage}'><i class='fa-solid fa-chevron-left me-1'></i>Prev</a></li>";
        } else {
            $html .= "<li class='page-item disabled'><span class='page-link rounded-2 px-2.5 text-muted'><i class='fa-solid fa-chevron-left me-1'></i>Prev</span></li>";
        }

        // Page range calculation
        $pages = [];
        if ($totalPages <= 7) {
            for ($i = 1; $i <= $totalPages; $i++) $pages[] = $i;
        } else {
            if ($currentPage <= 4) {
                $pages = [1, 2, 3, 4, 5, '...', $totalPages];
            } elseif ($currentPage >= $totalPages - 3) {
                $pages = [1, '...', $totalPages - 4, $totalPages - 3, $totalPages - 2, $totalPages - 1, $totalPages];
            } else {
                $pages = [1, '...', $currentPage - 1, $currentPage, $currentPage + 1, '...', $totalPages];
            }
        }

        foreach ($pages as $p) {
            if ($p === '...') {
                $html .= "<li class='page-item disabled'><span class='page-link border-0 px-2'>...</span></li>";
            } elseif ($p == $currentPage) {
                $html .= "<li class='page-item active'><span class='page-link rounded-2 px-3 fw-bold bg-primary text-white border-primary'>{$p}</span></li>";
            } else {
                $html .= "<li class='page-item'><a class='page-link ajax-page-link rounded-2 px-3' href='javascript:void(0);' onclick='{$callbackFn}({$p}); return false;' data-page='{$p}'>{$p}</a></li>";
            }
        }

        // Next button
        if ($currentPage < $totalPages) {
            $nextPage = $currentPage + 1;
            $html .= "<li class='page-item'><a class='page-link ajax-page-link rounded-2 px-2.5' href='javascript:void(0);' onclick='{$callbackFn}({$nextPage}); return false;' data-page='{$nextPage}'>Next<i class='fa-solid fa-chevron-right ms-1'></i></a></li>";
        } else {
            $html .= "<li class='page-item disabled'><span class='page-link rounded-2 px-2.5 text-muted'>Next<i class='fa-solid fa-chevron-right ms-1'></i></span></li>";
        }

        $html .= '</ul>';
        $html .= '</nav>';
        $html .= '</div>';

        return $html;
    }
    public function renderCedDashboardRowsHtml($clients)
    {
        if (empty($clients)) {
            return '<tr><td colspan="10" class="text-center py-4 text-muted"><i class="fa-solid fa-inbox me-2"></i>No clients found.</td></tr>';
        }
        
        $html = '';
        foreach ($clients as $client) {
            $html .= '<tr>';
            $html .= '<td class="fw-semibold">' . htmlspecialchars($client['client_name'] ?? 'N/A') . '</td>';
            $html .= '<td>' . htmlspecialchars($client['phone'] ?? 'N/A') . '</td>';
            $html .= '<td>' . htmlspecialchars($client['project_name'] ?? 'N/A') . '</td>';
            
            $unitFloor = 'N/A';
            if (!empty($client['unit_name']) || !empty($client['flat_floor_no'])) {
                $unitFloor = 'UNIT ' . htmlspecialchars($client['unit_name'] ?? '') . ' - FL ' . htmlspecialchars($client['flat_floor_no'] ?? 'N/A');
            }
            $html .= '<td>' . $unitFloor . '</td>';
            
            $html .= '<td>' . htmlspecialchars($client['agent_name'] ?? 'N/A') . '</td>';
            $html .= '<td>TK.' . number_format($client['total_price'] ?? 0, 2) . '</td>';
            $html .= '<td>TK.' . number_format($client['total_paid'] ?? 0, 2) . '</td>';
            
            $lastPayment = '<span class="text-muted">No Payment</span>';
            if (!empty($client['last_payment_date'])) {
                $lastPayment = date('d M, Y', strtotime($client['last_payment_date']));
            }
            $html .= '<td>' . $lastPayment . '</td>';
            
            $cms = $client['current_month_status'] ?? 'N/A';
            $cmsHtml = '<span class="text-muted">-</span>';
            if ($cms === 'paid') $cmsHtml = '<span class="badge bg-success">Paid</span>';
            elseif ($cms === 'matured') $cmsHtml = '<span class="badge bg-info text-dark" style="background-color: #0dcaf0; color: #000;">Matured</span>';
            elseif ($cms === 'pending') $cmsHtml = '<span class="badge bg-warning text-dark">Pending</span>';
            elseif ($cms === 'due') $cmsHtml = '<span class="badge bg-danger">Due</span>';
            elseif ($cms === 'future') $cmsHtml = '<span class="badge bg-secondary">Future</span>';
            $html .= '<td>' . $cmsHtml . '</td>';
            
            $base_url = $GLOBALS['base_url'] ?? '';
            $html .= '<td>';
            $html .= '<a class="table__icon" href="' . $base_url . '/clients-cash-in-history-view/' . $client['booking_id'] . '" title="View History"><i class="fa-regular fa-eye"></i></a>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        return $html;
    }

    public function CedView()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_GET['ajax']) && $_GET['ajax'] == '1');

        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
        $assignedProjectIds = [];
        $effectiveProjectId = $this->getEffectiveCedProjectFilter($selectedProjectId, $assignedProjectIds);

        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(5, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        if ($isAjax) {
            $totalClients = $this->clientModel->getTotalBookingsCount($effectiveProjectId, $search);
            $totalPages = $totalClients > 0 ? (int)ceil($totalClients / $limit) : 1;
            
            if ($page > $totalPages && $totalPages > 0) {
                $page = $totalPages;
                $offset = ($page - 1) * $limit;
            }

            $clients = $this->clientModel->getAllBookings($effectiveProjectId, $search, $limit, $offset);

            foreach ($clients as &$client) {
                $schedule = $this->clientModel->getVirtualEmiSchedule($client['booking_id']);
                $client['current_month_status'] = $this->calculateCurrentMonthStatus($schedule, $client['last_payment_date'] ?? null);
            }
            unset($client);

            $tableHtml = $this->renderCedDashboardRowsHtml($clients);
            $paginationHtml = $this->generateAjaxPagination($page, $totalPages, $totalClients, $limit, 'loadCedDashboardPage');
            $startEntry = $totalClients > 0 ? (($page - 1) * $limit) + 1 : 0;
            $endEntry = min($totalClients, $page * $limit);
            $infoText = "Showing {$startEntry} to {$endEntry} of {$totalClients} entries";

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'page' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $totalClients,
                'limit' => $limit,
                'table_html' => $tableHtml,
                'pagination_html' => $paginationHtml,
                'info_text' => $infoText
            ]);
            exit;
        }

        $paymentStats = $this->clientModel->getPaymentStatistics($effectiveProjectId);
        $collectionData = $this->clientModel->getDashboardCollectionStats($effectiveProjectId);
        $collectionStats = $this->clientModel->getCollectionStatsSummary($effectiveProjectId);
        $dueTrackingStats = $this->clientModel->getClientDueTrackingStats($effectiveProjectId);
        $paymentStatusData = $this->clientModel->getPaymentStatusCounts($effectiveProjectId);

        // Projects list for dropdown filter
        $projectModel = new Project($this->db);
        $projects = $projectModel->getAll();
        if (empty($projects)) {
            $projects = $this->clientModel->getProjectsForCED();
        }
        if (!empty($assignedProjectIds)) {
            $projects = array_values(array_filter($projects, fn($p) => in_array((int)$p['id'], $assignedProjectIds, true)));
        }

        // For initial load, do not load clients. Let AJAX load them.
        $clients = [];

        $this->view("ced/ced-dashboard", [
            "current_route"      => "ced-dashboard",
            "page_title"         => "CED Dashboard",
            "clients"            => $clients,
            "paymentStats"       => $paymentStats,
            "collectionData"     => $collectionData,
            "collectionStats"    => $collectionStats,
            "dueTrackingStats"   => $dueTrackingStats,
            "paymentStatusData"  => $paymentStatusData,
            "projects"           => $projects,
            "selectedProjectId"  => $selectedProjectId,
            "effectiveProjectId" => $effectiveProjectId,
            "assignedProjectIds" => $assignedProjectIds
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
            if (!empty($emi['due_date'])) {
                $dueMonth = (int)date('Ym', strtotime((string)$emi['due_date']));
                if ($dueMonth === $currentMonth) {
                    $currentMonthStatus = $emi['maturity_status'];
                }
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

    // Fallback: If not found by booking_id, check if $bookingId was actually a lead_id
    if (!$clientDetails) {
        $leadStmt = $this->db->prepare("SELECT id FROM lead_bookings WHERE lead_id = ? ORDER BY id DESC LIMIT 1");
        if ($leadStmt) {
            $leadStmt->bind_param("i", $bookingId);
            $leadStmt->execute();
            $lRes = $leadStmt->get_result();
            if ($lRow = $lRes->fetch_assoc()) {
                $actualBookingId = (int)$lRow['id'];
                $clientDetails = $this->clientModel->getClientByBookingId($actualBookingId);
                if ($clientDetails) {
                    $bookingId = $actualBookingId;
                }
            }
            $leadStmt->close();
        }
    }

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

    if (!empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1) {
        require_once dirname(__DIR__) . '/models/AccountShopClient.php';
        $this->clientModel = new AccountShopClient($this->db);
        $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
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
    
    // Always ensure transactions are strictly sorted in chronological business order
    if (is_array($transactions) && method_exists($this->clientModel, 'sortTransactions')) {
        $this->clientModel->sortTransactions($transactions);
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

    $bRes = $this->db->query("SELECT id, bank_name, account_name, account_number, CONCAT(account_name, IF(account_number != '', CONCAT(' (', account_number, ')'), '')) as branch_name FROM company_bank_accounts WHERE status = 'active' ORDER BY is_default DESC, bank_name ASC");
    $banks = $bRes ? $bRes->fetch_all(MYSQLI_ASSOC) : [];

    // Get sales agents for booking info edit modal (Sales Manager, Sales Team Head, Sales Executive, Secondary Roles & Admin) with employee_id
    $salesUsers = [];
    $salesRes = $this->db->query("SELECT u.id, u.full_name, r1.name as role_name, r2.name as sec_role_name, 
                                         COALESCE(NULLIF(e1.employee_id, ''), NULLIF(e2.employee_id, ''), NULLIF(u.employee_id, '')) as employee_id 
                                  FROM users u 
                                  LEFT JOIN roles r1 ON u.role_id = r1.id 
                                  LEFT JOIN roles r2 ON u.secondary_role_id = r2.id 
                                  LEFT JOIN employees e1 ON e1.user_id = u.id
                                  LEFT JOIN employees e2 ON u.employee_id = e2.id
                                  WHERE (r1.name LIKE '%sales%' OR r1.name LIKE '%sale%' 
                                     OR r2.name LIKE '%sales%' OR r2.name LIKE '%sale%'
                                     OR u.role_id IN (1, 3, 5, 8, 9, 20, 33)
                                     OR u.secondary_role_id IN (5, 8, 9))
                                  ORDER BY u.full_name ASC");
    if ($salesRes) {
        $salesUsers = $salesRes->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch pending or latest discount approval request for this booking
    $discountApprovalReq = null;
    $arStmt = $this->db->prepare("
        SELECT ar.*,
               u_req.full_name AS requester_name, e_req.employee_id AS requester_code,
               u_op.full_name AS op_head_name, e_op.employee_id AS op_head_code,
               u_app.full_name AS approver_name
        FROM approval_requests ar
        LEFT JOIN users u_req ON ar.requested_by = u_req.id
        LEFT JOIN employees e_req ON (e_req.user_id = u_req.id OR e_req.id = u_req.employee_id)
        LEFT JOIN users u_op ON ar.op_head_by = u_op.id
        LEFT JOIN employees e_op ON (e_op.user_id = u_op.id OR e_op.id = u_op.employee_id)
        LEFT JOIN users u_app ON ar.approved_by = u_app.id
        WHERE ar.source_table = 'lead_bookings' AND ar.source_id = ?
        ORDER BY ar.id DESC LIMIT 1
    ");
    if ($arStmt) {
        $arStmt->bind_param("i", $bookingId);
        $arStmt->execute();
        $discountApprovalReq = $arStmt->get_result()->fetch_assoc();
        $arStmt->close();
    }

    $penaltyHistory = $this->clientModel->getPenaltyHistoryByBookingId($bookingId);

    $this->view("ced/clients-cash-in-history-view", [
        "current_route" => "clients-cash-in-history-view",
        "page_title" => "Transaction History - " . $clientDetails['client_name'],
        "clientDetails" => $clientDetails,
        "transactions" => $transactions,
        "virtualEmiSchedule" => $virtualEmiSchedule,
        "paymentSummary" => $paymentSummary,
        "penaltyHistory" => $penaltyHistory,
        "refundDetails"   => $refundDetails,
        "refundPaidAmount"=> $refundPaidAmount,
        "banks"           => $banks,
        "salesUsers"      => $salesUsers,
        "accountModel"    => $this->clientModel,
        "datewiseSummary" => $this->clientModel->getDatewiseBatchPaymentSummary($bookingId),
        "discountApprovalReq" => $discountApprovalReq,
        "db"              => $this->db
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

    // Check if an active/pending refund exists for this booking
    $refundStmt = $this->db->prepare("SELECT id, status FROM client_refund_requests WHERE booking_id = ? AND status != 'rejected' ORDER BY id DESC LIMIT 1");
    if ($refundStmt) {
        $refundStmt->bind_param('i', $bookingId);
        $refundStmt->execute();
        $refundRow = $refundStmt->get_result()->fetch_assoc();
        if ($refundRow) {
            setError("A refund has been initiated for this booking. New cash-in transactions cannot be added.");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}");
            exit;
        }
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

    $registrationPaid = $this->clientModel->isRegistrationPaid($bookingId);

    // Calculate pending/unverified registration & land_share payments for form logic
    $pendingQuery = $this->db->prepare("
        SELECT SUM(amount) as pending_amount 
        FROM lead_payments 
        WHERE booking_id = ? AND is_trashed = 0 AND verification_status != 'rejected' AND payment_type IN ('registration', 'land_share', 'mutation_cost')
    ");
    $pendingQuery->bind_param("i", $bookingId);
    $pendingQuery->execute();
    $pendingRes = $pendingQuery->get_result()->fetch_assoc();
    $allRegistrationAndLandshare = (float)($pendingRes['pending_amount'] ?? 0);

    $bookingMoneyTotal = (float)($paymentSummary['booking_money_total'] ?? $clientDetails['booking_money_total'] ?? 0);
    $totalLandShare = (float)($paymentSummary['total_land_share'] ?? $clientDetails['total_land_share'] ?? 0);
    $discountAmount = (float)($paymentSummary['discount_amount'] ?? $clientDetails['discount_amount'] ?? 0);
    $agreementValue = (float)($paymentSummary['agreement_value'] ?? $clientDetails['agreement_value'] ?? 0);
    $effectiveBookingTarget = ($agreementValue > 0) ? max(0, $agreementValue - $discountAmount) : 300000;

    $effectiveLandShareTarget = max(0, $totalLandShare - $discountAmount);
    $landShareRemaining = max(0, (float)($paymentSummary['remaining_land_share_total'] ?? $paymentSummary['remaining_land_share'] ?? ($effectiveLandShareTarget - $bookingMoneyTotal - $allRegistrationAndLandshare)));
    $bookingDeduction = max($effectiveBookingTarget, $bookingMoneyTotal);
    $formRemainingRegistration = min($landShareRemaining, max(0, $effectiveLandShareTarget - $bookingDeduction - $allRegistrationAndLandshare));
    $formRemainingBooking = min($landShareRemaining, max(0, $effectiveBookingTarget - $bookingMoneyTotal));

    // Get management users for handover dropdown
    $managementUsers = [];
    $mgmtQuery = $this->db->query("SELECT id, full_name, email FROM users WHERE role_id IN (1, 3)");
    if ($mgmtQuery) {
        while ($row = $mgmtQuery->fetch_assoc()) {
            $managementUsers[] = $row;
        }
    }

    // Get active company bank accounts
    $companyBanks = [];
    $cbQuery = $this->db->query("SELECT id, bank_name, account_name, account_number, status FROM company_bank_accounts WHERE status = 'active' ORDER BY bank_name ASC");
    if ($cbQuery) {
        while ($row = $cbQuery->fetch_assoc()) {
            $companyBanks[] = $row;
        }
    }

    $this->view("ced/ced-cash-in-form", [
        "current_route"               => "ced-add-transaction-form",
        "page_title"                  => "CED Add Transaction - " . $clientDetails['client_name'],
        "clientDetails"               => $clientDetails,
        "bookingId"                   => $bookingId,
        "paymentSummary"              => $paymentSummary,
        "outstandingDues"             => $outstandingDues,
        "virtualEmiSchedule"          => $virtualEmiSchedule,
        "registrationPaid"            => $registrationPaid,
        "managementUsers"             => $managementUsers,
        "companyBanks"                => $companyBanks,
        "formRemainingRegistration"   => $formRemainingRegistration,
        "formRemainingBooking"        => $formRemainingBooking,
        "allRegistrationAndLandshare" => $allRegistrationAndLandshare,
        "isShop"                      => (!empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1)
    ]);
}

/**
 * CED: Process add-transaction POST
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

    // Check if an active/pending refund exists for this booking
    $refundStmt = $this->db->prepare("SELECT id, status FROM client_refund_requests WHERE booking_id = ? AND status != 'rejected' ORDER BY id DESC LIMIT 1");
    if ($refundStmt) {
        $refundStmt->bind_param('i', $bookingId);
        $refundStmt->execute();
        $refundRow = $refundStmt->get_result()->fetch_assoc();
        if ($refundRow) {
            setError("A refund has been initiated for this booking. New cash-in transactions cannot be added.");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}");
            exit;
        }
    }

    $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
    if (!empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1) {
        require_once dirname(__DIR__) . '/models/AccountShopClient.php';
        $this->clientModel = new AccountShopClient($this->db);
    }

    $paymentType = $_POST['payment_type'] ?? '';

    // Allow all standard payment types for CED just like Accounts
    $allowedCedPaymentTypes = [
        'booking_money',
        'registration',
        'mutation_cost',
        'land_share',
        'development_charge',
        'shop_service_charge',
        'emi',
        'emi_due_settlement',
        'penalty_settlement',
        'others'
    ];
    if (!in_array($paymentType, $allowedCedPaymentTypes)) {
        setError("Invalid payment type selected.");
        header("Location: {$GLOBALS['base_url']}/ced-add-transaction-form?booking_id={$bookingId}");
        exit;
    }

    if ($paymentType === 'others' && empty(trim($_POST['other_purpose'] ?? ''))) {
        setError("Please specify the purpose for the 'Others' payment.");
        header("Location: {$GLOBALS['base_url']}/ced-add-transaction-form?booking_id={$bookingId}");
        exit;
    }

    // Update agreement_value in lead_bookings if total_booking_money is provided (booking_money ONLY)
    if (isset($_POST['total_booking_money']) && is_numeric($_POST['total_booking_money']) && $paymentType === 'booking_money') {
        $totalBookingMoney = (float)$_POST['total_booking_money'];
        $updateStmt = $this->db->prepare("UPDATE lead_bookings SET agreement_value = ? WHERE id = ?");
        $updateStmt->bind_param("di", $totalBookingMoney, $bookingId);
        $updateStmt->execute();
    }

    // EMI due settlement is now auto-settled via unified waterfall
    if ($paymentType === 'emi_due_settlement') {
        $parentPaymentId = !empty($_POST['parent_payment_id']) ? (int)$_POST['parent_payment_id'] : null;
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
    $cedUploadErrors = [];
    $uploadedPaths = !empty($_FILES['proof_files']) ? $this->handleProofFileUploads($_FILES['proof_files'], 'proof_', $cedUploadErrors) : [];
    if (!empty($cedUploadErrors)) {
        setError('Document upload failed: ' . implode(' ', $cedUploadErrors));
        header("Location: {$GLOBALS['base_url']}/ced-add-transaction-form?booking_id={$bookingId}");
        exit;
    }

    // Handle waiver proof upload
    $waiverAttachmentPath = null;
    if (!empty($_FILES['waiver_file']['name'])) {
        $waiverDir = $this->getWebPublicDir() . '/uploads/waivers/';
        if (!is_dir($waiverDir)) mkdir($waiverDir, 0755, true);
        $tmpName = $_FILES['waiver_file']['tmp_name'];
        if (is_uploaded_file($tmpName)) {
            $ext = strtolower(pathinfo(basename($_FILES['waiver_file']['name']), PATHINFO_EXTENSION));
            if ($ext === 'jfif') $ext = 'jpg';
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'], true)) {
                $wName = 'waiver_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($tmpName, $waiverDir . $wName)) {
                    @chmod($waiverDir . $wName, 0644);
                    $waiverAttachmentPath = 'uploads/waivers/' . $wName;
                }
            }
        }
    }

    if ($paymentType === 'penalty_settlement') {
        $penaltyAmount = 0;
        $penaltyWaived = 0;
        $penaltyPaid   = max(0, (float)($_POST['amount'] ?? 0));
        $penaltyDue    = 0;
        $waiverReason  = null;
        $waiverStatus  = 'none';
    } elseif (!empty($_POST['no_penalty'])) {
        $penaltyAmount = 0;
        $penaltyWaived = 0;
        $penaltyPaid   = 0;
        $penaltyDue    = 0;
        $waiverReason  = null;
        $waiverStatus  = 'none';
    } else {
        $penaltyAmount = isset($_POST['penalty_amount']) ? (float)$_POST['penalty_amount'] : 0;
        $penaltyWaived = isset($_POST['penalty_waived']) ? (float)$_POST['penalty_waived'] : 0;
        $netPenalty    = round(max(0.0, $penaltyAmount - $penaltyWaived), 2);
        // Penalty collection is recorded strictly via separate 'penalty_settlement' vouchers;
        // regular EMI/other vouchers carry forward netPenalty into penalty_due.
        $penaltyPaid   = 0.0;
        $penaltyDue    = $netPenalty;
        $waiverReason  = $_POST['waiver_reason'] ?? null;
        $waiverStatus  = (!empty($_POST['apply_waiver']) && $penaltyWaived > 0) ? 'pending' : 'none';
    }

        $bankName = !empty($_POST['bank_name']) ? trim($_POST['bank_name']) : null;
        if (in_array($bankName, ['Other Bank', 'Other Country / Foreign Bank', 'Foreign Bank', 'Other Country Bank']) && !empty($_POST['custom_bank_name'])) {
            $bankName = trim($_POST['custom_bank_name']);
        }

        // Prepare transaction data
        $transactionData = [
            'booking_id'        => $bookingId,
            'payment_type'      => $paymentType,
            'other_purpose'     => ($paymentType === 'others') ? trim($_POST['other_purpose'] ?? '') : null,
            'amount'            => $_POST['amount'] ?? 0,
            'payment_method'    => $_POST['payment_method'] ?? 'cash',
            'payment_status'    => 'pending', // Always pending until verified
            'payment_date'      => $_POST['payment_date'] ?? date('Y-m-d'),
            'transaction_ref'   => $_POST['transaction_ref'] ?? null,
            'bank_id'           => !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null,
            'bank_name'         => $bankName,
            'bank_account_no'   => $_POST['bank_account_no'] ?? null,
            'cheque_no'         => $_POST['cheque_no'] ?? null,
            'online_type'       => $_POST['online_type'] ?? null,
        'attachment_paths'   => $uploadedPaths ?: null,
        'bulk_emi_discount' => isset($_POST['bulk_emi_discount']) ? (float)$_POST['bulk_emi_discount'] : 0,
        'is_emi_discount'    => !empty($_POST['apply_emi_discount']) ? 1 : 0,
        'is_previous_emi_discount' => !empty($_POST['is_previous_emi_discount']) ? 1 : 0,
        'due_emi_amount'     => isset($_POST['due_emi_amount']) ? (float)$_POST['due_emi_amount'] : 0,
        'parent_payment_id'  => !empty($_POST['parent_payment_id']) ? (int)$_POST['parent_payment_id'] : null,
        'settlement_type'    => !empty($_POST['settlement_type']) ? $_POST['settlement_type'] : null,
        'penalty_amount'    => $penaltyAmount,
        'penalty_paid'      => $penaltyPaid,
        'penalty_waived'    => $penaltyWaived,
        'penalty_due'       => $penaltyDue,
        'waiver_reason'     => $waiverReason,
        'waiver_attachment' => $waiverAttachmentPath,
        'waiver_status'     => $waiverStatus,
        'handover_to'       => !empty($_POST['handover_to']) ? (int)$_POST['handover_to'] : null,
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

    $sendSms = isset($_POST['send_sms']) ? filter_var($_POST['send_sms'], FILTER_VALIDATE_BOOLEAN) : false;

    require_once dirname(__DIR__) . '/services/SmsService.php';
    $smsService = new SmsService($this->db);

    if ($sendSms && !$isTestingMode) {
        $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
        $clientPhone   = !empty($clientDetails['phone']) ? trim($clientDetails['phone']) : (!empty($clientDetails['phone_number']) ? trim($clientDetails['phone_number']) : '');
        $phoneCheck    = $smsService->validatePhoneNumber($clientPhone);

        if (!$phoneCheck['valid']) {
            // Do not block payment. It will gracefully show a warning instead.
        }
    }

    // Route to correct model method
    if ($transactionData['payment_type'] === 'others') {
        // Use addTransaction for 'others' (incidental charges outside flat price)
        $result = $this->clientModel->addTransaction($transactionData, null);
    } else {
        $result = $this->clientModel->addTransactionWithEMI($transactionData, $emiScheduleData);
    }

    if ($result['success']) {
        $smsMsg = "";
        if (!empty($result['payment_id'])) {
            if ($sendSms) {
                $overrideAmount = $_POST['amount'] ?? null;
                $overridePaymentType = !empty($_POST['payment_type']) 
                    ? (($paymentType === 'others' && !empty($_POST['other_purpose'])) 
                        ? 'Others (' . trim($_POST['other_purpose']) . ')' 
                        : ucfirst(str_replace('_', ' ', $_POST['payment_type']))) 
                    : null;
                $smsResult = $smsService->sendPaymentReceivedSms($result['payment_id'], $isTestingMode, $overrideAmount, $overridePaymentType);
                if ($isTestingMode) {
                    $smsMsg = " (Testing Mode: SMS & Email Skipped)";
                } elseif (!empty($smsResult['success'])) {
                    $smsMsg = " & SMS dispatched to client!";
                } else {
                    $errMsg = !empty($smsResult['response']) ? $smsResult['response'] : ($smsResult['message'] ?? 'Gateway Error');
                    $smsMsg = " (Warning: SMS dispatch failed: " . htmlspecialchars($errMsg) . ")";
                }
            } else {
                $smsMsg = " (SMS & Email Notification Disabled)";
            }
        }
        
        // EMAIL SENDING LOGIC - Only send if SMS is enabled and testing mode is off
        if ($sendSms && !$isTestingMode) {
            $leadInfoQuery = $this->db->query("
                SELECT l.full_name, l.email, l.alt_email, l.`2nd_owner_email`, p.name AS project_name, f.flat_name
                FROM lead_bookings lb
                LEFT JOIN leads l ON lb.lead_id = l.id
                LEFT JOIN flats f ON lb.flat_id = f.id
                LEFT JOIN projects p ON f.project_id = p.id
                WHERE lb.id = " . (int)$bookingId . "
            ");
            if ($leadInfoQuery && $leadInfoQuery->num_rows > 0) {
                $leadInfo = $leadInfoQuery->fetch_assoc();
                
                require_once dirname(__DIR__) . '/core/MailServiceForPHPMailer.php';
                $mailService = new MailService();
                $mailData = [
                    'client_name' => $leadInfo['full_name'],
                    'amount' => (float)($transactionData['amount'] ?? 0),
                    'project_name' => $leadInfo['project_name'],
                    'flat_name' => $leadInfo['flat_name'],
                    'payment_date' => $transactionData['payment_date'] ?? date('Y-m-d')
                ];

                $emailSentCount = 0;
                
                if (!empty($leadInfo['email']) && filter_var($leadInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    if ($mailService->sendPaymentSuccessMail($leadInfo['email'], $mailData)) {
                        $emailSentCount++;
                    }
                }
                if (!empty($leadInfo['alt_email']) && filter_var($leadInfo['alt_email'], FILTER_VALIDATE_EMAIL)) {
                    if ($mailService->sendPaymentSuccessMail($leadInfo['alt_email'], $mailData)) {
                        $emailSentCount++;
                    }
                }

                if (!empty($leadInfo['2nd_owner_email']) && filter_var($leadInfo['2nd_owner_email'], FILTER_VALIDATE_EMAIL)) {
                    if ($mailService->sendPaymentSuccessMail($leadInfo['2nd_owner_email'], $mailData)) {
                        $emailSentCount++;
                    }
                }

                if ($emailSentCount > 0) {
                    $smsMsg .= " & Email sent!";
                } else {
                    $smsMsg .= " (Warning: Email dispatch failed or skipped)";
                }
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

    if (!empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1) {
        header("Location: {$GLOBALS['base_url']}/shop-client-cash-in-form?booking_id={$bookingId}");
        exit;
    }

    // SIMPLE CHECK: Is registration paid?
    $registrationPaid = $this->clientModel->isRegistrationPaid($bookingId);

    // Calculate pending/unverified payments for form logic
    $pendingQuery = $this->db->prepare("
        SELECT SUM(amount) as pending_amount 
        FROM lead_payments 
        WHERE booking_id = ? AND is_trashed = 0 AND verification_status != 'rejected' AND payment_type IN ('registration', 'land_share', 'mutation_cost')
    ");
    $pendingQuery->bind_param("i", $bookingId);
    $pendingQuery->execute();
    $pendingRes = $pendingQuery->get_result()->fetch_assoc();
    $allRegistrationAndLandshare = (float)($pendingRes['pending_amount'] ?? 0);

    $bookingMoneyTotal = (float)($clientDetails['booking_money_total'] ?? 0);
    $totalLandShare = (float)($clientDetails['total_land_share'] ?? 0);
    $discountAmount = (float)($clientDetails['discount_amount'] ?? 0);
    $agreementValue = (float)($clientDetails['agreement_value'] ?? 0);
    $effectiveBookingTarget = ($agreementValue > 0) ? max(0, $agreementValue - $discountAmount) : 300000;

    $effectiveLandShareTarget = max(0, $totalLandShare - $discountAmount);
    $landShareRemaining = max(0, (float)($clientDetails['remaining_land_share_total'] ?? $clientDetails['remaining_land_share'] ?? ($effectiveLandShareTarget - $bookingMoneyTotal - $allRegistrationAndLandshare)));
    $bookingDeduction = max($effectiveBookingTarget, $bookingMoneyTotal);
    $formRemainingRegistration = min($landShareRemaining, max(0, $effectiveLandShareTarget - $bookingDeduction - $allRegistrationAndLandshare));
    $formRemainingBooking = min($landShareRemaining, max(0, $effectiveBookingTarget - $bookingMoneyTotal));

    // Get management users for handover dropdown
    $managementUsers = [];
    $mgmtQuery = $this->db->query("SELECT id, full_name, email FROM users WHERE role_id IN (1, 3)");
    if ($mgmtQuery) {
        while ($row = $mgmtQuery->fetch_assoc()) {
            $managementUsers[] = $row;
        }
    }

    // Get active company bank accounts
    $companyBanks = [];
    $cbQuery = $this->db->query("SELECT id, bank_name, account_name, account_number, status FROM company_bank_accounts WHERE status = 'active' ORDER BY bank_name ASC");
    if ($cbQuery) {
        while ($row = $cbQuery->fetch_assoc()) {
            $companyBanks[] = $row;
        }
    }

    $this->view("accounts/cashIn/client-cash-in-form", [
        "current_route" => "client-cash-in-form",
        "page_title" => "Add Transaction - " . $clientDetails['client_name'],
        "clientDetails" => $clientDetails,
        "bookingId" => $bookingId,
        "registrationPaid" => $registrationPaid,
        "managementUsers" => $managementUsers,
        "companyBanks" => $companyBanks,
        "formRemainingRegistration" => $formRemainingRegistration,
        "formRemainingBooking" => $formRemainingBooking,
        "allRegistrationAndLandshare" => $allRegistrationAndLandshare
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

        // Update agreement_value if total_booking_money is provided (booking_money ONLY)
        if (isset($_POST['total_booking_money']) && is_numeric($_POST['total_booking_money']) && $paymentType === 'booking_money') {
            $totalBookingMoney = (float)$_POST['total_booking_money'];
            $updateStmt = $this->db->prepare("UPDATE lead_bookings SET agreement_value = ? WHERE id = ?");
            $updateStmt->bind_param("di", $totalBookingMoney, $bookingId);
            $updateStmt->execute();
        }
        
        // EMI due settlement is now auto-settled via unified waterfall
        if ($paymentType === 'emi_due_settlement') {
            $parentPaymentId = !empty($_POST['parent_payment_id']) ? (int)$_POST['parent_payment_id'] : null;
        }

        // Handle multi-file proof uploads (before insert so paths are stored atomically)
        $accUploadErrors = [];
        $uploadedPaths = !empty($_FILES['proof_files']) ? $this->handleProofFileUploads($_FILES['proof_files'], 'proof_', $accUploadErrors) : [];
        if (!empty($accUploadErrors)) {
            setError('Document upload failed: ' . implode(' ', $accUploadErrors));
            header("Location: {$GLOBALS['base_url']}/client-cash-in-form?booking_id={$bookingId}");
            exit;
        }

        $bankName = !empty($_POST['bank_name']) ? trim($_POST['bank_name']) : null;
        if (in_array($bankName, ['Other Bank', 'Other Country / Foreign Bank', 'Foreign Bank', 'Other Country Bank']) && !empty($_POST['custom_bank_name'])) {
            $bankName = trim($_POST['custom_bank_name']);
        }

        // Prepare transaction data
        $transactionData = [
            'booking_id'       => $bookingId,
            'payment_type'     => $paymentType,
            'other_purpose'    => ($paymentType === 'others') ? trim($_POST['other_purpose'] ?? '') : null,
            'amount'           => $_POST['amount'] ?? 0,
            'payment_method'   => $_POST['payment_method'] ?? 'cash',
            'payment_status'   => 'pending', // Always pending until verified
            'payment_date'     => $_POST['payment_date'] ?? date('Y-m-d'),
            'transaction_ref'  => $_POST['transaction_ref'] ?? null,
            'bank_id'          => !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null,
            'bank_name'        => $bankName,
            'bank_account_no'  => $_POST['bank_account_no'] ?? null,
            'cheque_no'        => $_POST['cheque_no'] ?? null,
            'online_type'      => $_POST['online_type'] ?? null,
            'attachment_paths' => $uploadedPaths ?: null,
            'due_emi_amount'   => isset($_POST['due_emi_amount']) ? (float)$_POST['due_emi_amount'] : 0,
            'parent_payment_id'=> !empty($_POST['parent_payment_id']) ? (int)$_POST['parent_payment_id'] : null,
            'settlement_type'  => ($paymentType === 'emi_due_settlement') ? 'emi_due_settlement' : null,
            'bulk_emi_discount'=> isset($_POST['bulk_emi_discount']) ? (float)$_POST['bulk_emi_discount'] : 0,
            'is_emi_discount'  => !empty($_POST['apply_emi_discount']) ? 1 : 0,
            'handover_to'      => !empty($_POST['handover_to']) ? (int)$_POST['handover_to'] : null,
            'is_previous_emi_discount' => !empty($_POST['is_previous_emi_discount']) ? 1 : 0,
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

        $sendSms = isset($_POST['send_sms']) ? filter_var($_POST['send_sms'], FILTER_VALIDATE_BOOLEAN) : false;

        require_once dirname(__DIR__) . '/services/SmsService.php';
        $smsService = new SmsService($this->db);

        if ($sendSms && !$isTestingMode) {
            $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
            $clientPhone   = !empty($clientDetails['phone']) ? trim($clientDetails['phone']) : (!empty($clientDetails['phone_number']) ? trim($clientDetails['phone_number']) : '');
            $phoneCheck    = $smsService->validatePhoneNumber($clientPhone);

            if (!$phoneCheck['valid']) {
                // Do not block payment. It will gracefully show a warning instead.
            }
        }

        // Route to correct model method
        if ($transactionData['payment_type'] === 'others') {
            // Use addTransaction for 'others' (incidental charges outside flat price)
            $result = $this->clientModel->addTransaction($transactionData, null);
        } else {
            $result = $this->clientModel->addTransactionWithEMI($transactionData, $emiScheduleData);
        }

        if ($result['success']) {
            $smsMsg = "";
            if (!empty($result['payment_id'])) {
                if ($sendSms) {
                    $overrideAmount = $_POST['amount'] ?? null;
                    $overridePaymentType = !empty($_POST['payment_type']) 
                        ? (($paymentType === 'others' && !empty($_POST['other_purpose'])) 
                            ? 'Others (' . trim($_POST['other_purpose']) . ')' 
                            : ucfirst(str_replace('_', ' ', $_POST['payment_type']))) 
                        : null;
                    $smsResult = $smsService->sendPaymentReceivedSms($result['payment_id'], $isTestingMode, $overrideAmount, $overridePaymentType);
                    if ($isTestingMode) {
                        $smsMsg = " (Testing Mode: SMS & Email Skipped)";
                    } elseif (!empty($smsResult['success'])) {
                        $smsMsg = " & SMS dispatched to client (" . htmlspecialchars($clientPhone ?? '') . ")!";
                    } else {
                        $errMsg = !empty($smsResult['response']) ? $smsResult['response'] : ($smsResult['message'] ?? 'Gateway Error');
                        $smsMsg = " (Warning: SMS dispatch failed for " . htmlspecialchars($clientPhone ?? '') . ": " . htmlspecialchars($errMsg) . ")";
                    }
                } else {
                    $smsMsg = " (SMS & Email Notification Disabled)";
                }
            }
            // EMAIL SENDING LOGIC - Only send if SMS is enabled and testing mode is off
            if ($sendSms && !$isTestingMode) {
                $leadInfoQuery = $this->db->query("
                    SELECT l.full_name, l.email, l.alt_email, l.`2nd_owner_email`, p.name AS project_name, f.flat_name
                    FROM lead_bookings lb
                    LEFT JOIN leads l ON lb.lead_id = l.id
                    LEFT JOIN flats f ON lb.flat_id = f.id
                    LEFT JOIN projects p ON f.project_id = p.id
                    WHERE lb.id = " . (int)$bookingId . "
                ");
                if ($leadInfoQuery && $leadInfoQuery->num_rows > 0) {
                    $leadInfo = $leadInfoQuery->fetch_assoc();

                    require_once dirname(__DIR__) . '/core/MailServiceForPHPMailer.php';
                    $mailService = new MailService();
                    $mailData = [
                        'client_name' => $leadInfo['full_name'],
                        'amount' => (float)($transactionData['amount'] ?? 0),
                        'project_name' => $leadInfo['project_name'],
                        'flat_name' => $leadInfo['flat_name'],
                        'payment_date' => $transactionData['payment_date'] ?? date('Y-m-d')
                    ];

                    $emailSentCount = 0;

                    if (!empty($leadInfo['email']) && filter_var($leadInfo['email'], FILTER_VALIDATE_EMAIL)) {
                        if ($mailService->sendPaymentSuccessMail($leadInfo['email'], $mailData)) {
                            $emailSentCount++;
                        }
                    }
                    if (!empty($leadInfo['alt_email']) && filter_var($leadInfo['alt_email'], FILTER_VALIDATE_EMAIL)) {
                        if ($mailService->sendPaymentSuccessMail($leadInfo['alt_email'], $mailData)) {
                            $emailSentCount++;
                        }
                    }

                    if (!empty($leadInfo['2nd_owner_email']) && filter_var($leadInfo['2nd_owner_email'], FILTER_VALIDATE_EMAIL)) {
                        if ($mailService->sendPaymentSuccessMail($leadInfo['2nd_owner_email'], $mailData)) {
                            $emailSentCount++;
                        }
                    }

                    if ($emailSentCount > 0) {
                        $smsMsg .= " & Email sent!";
                    } else {
                        $smsMsg .= " (Warning: Email dispatch failed or skipped)";
                    }
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
        $isShop = !empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1;
        if ($isShop) {
            require_once dirname(__DIR__) . '/models/AccountShopClient.php';
            $this->clientModel = new AccountShopClient($this->db);
            $clientDetails = $this->clientModel->getClientByBookingId($transaction['booking_id']);
        }
        $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($transaction['booking_id']);

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
            "paymentSummary" => $paymentSummary,
            "isShop" => $isShop,
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
        
        $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
        if (!empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1) {
            require_once dirname(__DIR__) . '/models/AccountShopClient.php';
            $this->clientModel = new AccountShopClient($this->db);
        }

        error_log("Session User ID: " . ($_SESSION['user']['id'] ?? 'null'));

        // Update agreement_value if total_booking_money is provided (booking_money ONLY)
        if (isset($_POST['total_booking_money']) && is_numeric($_POST['total_booking_money']) && ($_POST['payment_type'] ?? '') === 'booking_money') {
            $totalBookingMoney = (float)$_POST['total_booking_money'];
            $updateStmt = $this->db->prepare("UPDATE lead_bookings SET agreement_value = ? WHERE id = ?");
            $updateStmt->bind_param("di", $totalBookingMoney, $bookingId);
            $updateStmt->execute();
        }

        // Get the OLD transaction data to compare due amounts
        $oldTransaction = $this->clientModel->getTransactionById($paymentId);
        $oldEmiSchedule = null;
        $oldDueAmount = 0;
        
        if ($oldTransaction && $oldTransaction['payment_type'] === 'emi') {
            $oldEmiSchedule = $this->clientModel->getEMIScheduleByPaymentId($paymentId);
            $oldDueAmount = (float)($oldEmiSchedule['due_emi_amount'] ?? ($oldTransaction['due_emi_amount'] ?? 0));
        }

        error_log("Old Due Amount: " . $oldDueAmount);

        // ── Handle file uploads: start from existing files sent back via hidden inputs ──
        $mergedPaths = [];
        if (!empty($_POST['existing_files']) && is_array($_POST['existing_files'])) {
            foreach ($_POST['existing_files'] as $ep) {
                $ep = trim($ep);
                if ($ep !== '') $mergedPaths[] = $ep;
            }
        } elseif (empty($_POST['existing_files_present']) && !empty($oldTransaction['attachment_paths'])) {
            // Preserve existing attachments when submitted from modal without existing_files inputs
            $decodedOld = is_string($oldTransaction['attachment_paths'])
                ? json_decode($oldTransaction['attachment_paths'], true)
                : $oldTransaction['attachment_paths'];
            if (is_array($decodedOld)) {
                foreach ($decodedOld as $ep) {
                    $ep = trim((string)$ep);
                    if ($ep !== '') $mergedPaths[] = $ep;
                }
            }
        }
        // Append any newly uploaded files
        if (!empty($_FILES['proof_files']['name']) && is_array($_FILES['proof_files']['name'])) {
            $updUploadErrors = [];
            $newlyUploaded = $this->handleProofFileUploads($_FILES['proof_files'], 'proof_', $updUploadErrors);
            if (!empty($updUploadErrors)) {
                setError('Document upload failed: ' . implode(' ', $updUploadErrors));
                $editBackUrl = !empty($_POST['from_unverified_modal'])
                    ? (!empty($_POST['redirect_to']) ? "{$GLOBALS['base_url']}/" . ltrim($_POST['redirect_to'], '/') : "{$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}")
                    : "{$GLOBALS['base_url']}/edit-client-cash-in/{$paymentId}";
                header("Location: {$editBackUrl}");
                exit;
            }
            foreach ($newlyUploaded as $np) {
                $mergedPaths[] = $np;
            }
        }

        $bankId = !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null;
        $bankName = !empty($_POST['bank_name']) ? trim($_POST['bank_name']) : null;
        if (in_array($bankName, ['Other Bank', 'Other Country / Foreign Bank', 'Foreign Bank', 'Other Country Bank']) && !empty($_POST['custom_bank_name'])) {
            $bankName = trim($_POST['custom_bank_name']);
        }
        if ($bankId && empty($bankName)) {
            $bRow = $this->db->query("SELECT CONCAT(bank_name, ' - ', account_name) as label FROM company_bank_accounts WHERE id = {$bankId}");
            if ($bRow) { $bData = $bRow->fetch_assoc(); $bankName = $bData['label'] ?? ''; }
        }

        $postedPaymentType = $_POST['payment_type'] ?? 'emi';
        if ($postedPaymentType === 'others' && empty(trim($_POST['other_purpose'] ?? ''))) {
            setError("Please specify the purpose for the 'Others' payment.");
            $redirectFail = !empty($_POST['redirect_to']) ? "{$GLOBALS['base_url']}/" . ltrim($_POST['redirect_to'], '/') : "{$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}";
            header("Location: {$redirectFail}");
            exit;
        }

        // Prepare transaction data
        $transactionData = [
            'payment_id'       => $paymentId,
            'booking_id'       => $bookingId,
            'payment_type'     => $postedPaymentType,
            'other_purpose'    => ($postedPaymentType === 'others') ? trim($_POST['other_purpose'] ?? '') : null,
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

            // Calculate the additional payment made (only if due amount decreased AND amount field wasn't already modified and not from quick modal)
            if (empty($_POST['from_unverified_modal']) && $oldDueAmount > 0 && $newDueAmount < $oldDueAmount && abs((float)$transactionData['amount'] - (float)($oldTransaction['amount'] ?? 0)) < 0.01) {
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

        $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
        if (!empty($clientDetails['is_shop']) && $clientDetails['is_shop'] == 1) {
            require_once dirname(__DIR__) . '/models/AccountShopClient.php';
            $this->clientModel = new AccountShopClient($this->db);
        }

        // Determine redirect based on role
        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower($_SESSION['user']['role'] ?? '');
        $isCED = ($roleId === 5);
        $isAccounts = in_array($roleId, [1, 3, 6, 30]);
        $isManagementUser = ($roleId === 1 || $roleId === 3 || strpos($roleName, 'management') !== false || strpos($roleName, 'admin') !== false);
        
        $fromTrash = isset($_GET['from_trash']) ? (int) $_GET['from_trash'] : 0;
        $userId    = (int) ($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 1));

        $redirectUrl = $fromTrash
            ? "{$GLOBALS['base_url']}/trash-client-payment-list"
            : (($isCED || $isManagementUser)
                ? "{$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}"
                : "{$GLOBALS['base_url']}/clients-cash-in-history?booking_id={$bookingId}");

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
        if ($isCED && !$fromTrash) {
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

        if ($fromTrash === 1) {
            // Delete permanently from trash list - ONLY Management & Super Admin (Role 1 & 3)
            if (!$isManagementUser && !in_array($roleId, [1, 3])) {
                setError("Permission denied: Only Management can permanently delete transactions from the database.");
                header("Location: {$redirectUrl}");
                exit;
            }
            $result = $this->clientModel->deleteTransactionWithCleanup($paymentId, $bookingId);
        } else {
            // Safe delete: Move to trash so it can be viewed and restored from Trash list
            $result = $this->clientModel->trashTransaction($paymentId, $bookingId, $userId);
        }

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
        
        // Only Super Admin (3) is allowed to delete bookings
        if ($roleId !== 3) {
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

            // 3. Reset the flat status only if NO other active bookings exist for this flat
            $checkFlat = $this->db->prepare("
                SELECT COUNT(*) as active_cnt 
                FROM lead_bookings 
                WHERE flat_id = ? 
                  AND deleted_at IS NULL 
                  AND booking_status NOT IN ('refunded', 'cancelled')
                  AND (landshare_status IS NULL OR landshare_status != 'cancelled')
            ");
            $checkFlat->bind_param('i', $flatId);
            $checkFlat->execute();
            $actCnt = (int) ($checkFlat->get_result()->fetch_assoc()['active_cnt'] ?? 0);
            $checkFlat->close();

            if ($actCnt === 0) {
                $stmt3 = $this->db->prepare("UPDATE flats SET status = 'available', is_booked = 0 WHERE id = ?");
                $stmt3->bind_param('i', $flatId);
                $stmt3->execute();
                $stmt3->close();
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Booking and all associated payments deleted successfully.']);
            exit;

        } catch (\Exception $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Cancel a booking (used for resolving conflicts or client cancellations)
     */
    public function cancelBooking()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3, 6, 7, 30, 33, 35])) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to cancel bookings.']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $bookingId = isset($input['booking_id']) ? (int) $input['booking_id'] : 0;
        $reason = trim($input['reason'] ?? 'Cancelled double booking');

        if (!$bookingId) {
            echo json_encode(['success' => false, 'message' => 'Booking ID is required.']);
            exit;
        }

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

        $this->db->begin_transaction();
        try {
            $stmtUpdate = $this->db->prepare("
                UPDATE lead_bookings 
                SET booking_status = 'cancelled', 
                    landshare_status = 'cancelled',
                    remarks = CONCAT(COALESCE(remarks, ''), ' [Cancelled: ', ?, ']')
                WHERE id = ?
            ");
            $stmtUpdate->bind_param('si', $reason, $bookingId);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            // Check if any other active booking exists on this flat
            $checkFlat = $this->db->prepare("
                SELECT COUNT(*) as active_cnt 
                FROM lead_bookings 
                WHERE flat_id = ? 
                  AND deleted_at IS NULL 
                  AND booking_status NOT IN ('refunded', 'cancelled')
                  AND (landshare_status IS NULL OR landshare_status != 'cancelled')
            ");
            $checkFlat->bind_param('i', $flatId);
            $checkFlat->execute();
            $actCnt = (int) ($checkFlat->get_result()->fetch_assoc()['active_cnt'] ?? 0);
            $checkFlat->close();

            if ($actCnt === 0) {
                $stmt3 = $this->db->prepare("UPDATE flats SET status = 'available', is_booked = 0 WHERE id = ?");
                $stmt3->bind_param('i', $flatId);
                $stmt3->execute();
                $stmt3->close();
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Booking has been cancelled successfully.']);
            exit;
        } catch (\Exception $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to cancel booking: ' . $e->getMessage()]);
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
            // installment_label is now provided directly by the model (getTransactionById)

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

    // Auto-detect batch if single payment requested and it is part of a multi-payment batch
    $forceSingle = !empty($_GET['single']) || !empty($_GET['individual']);
    if (!$forceSingle && count($payments) === 1 && !empty($payments[0]['batch_id'])) {
        $batchPayments = $this->clientModel->getTransactionsByBatchId($payments[0]['batch_id'], $payments[0]['booking_id'] ?? null);
        // Exclude trashed or rejected payments
        $batchPayments = array_values(array_filter($batchPayments, function($p) {
            $isTrashed = !empty($p['is_trashed']) && (int)$p['is_trashed'] === 1;
            $isRejected = strtolower(trim($p['verification_status'] ?? '')) === 'rejected';
            return !$isTrashed && !$isRejected;
        }));

        if (count($batchPayments) > 1) {
            // Only auto-merge if all payments in the batch share the same payment_type (e.g. split bulk EMI),
            // or if batch receipt is explicitly requested via &batch=1.
            // For distinct payment types (e.g. Booking Money vs Registration Money), keep individual receipt.
            $types = array_unique(array_filter(array_map(fn($p) => $p['payment_type'] ?? '', $batchPayments)));
            $isAllEmi = count($types) === 1 && in_array(reset($types), ['emi', 'emi_due_settlement']);
            if ($isAllEmi || !empty($_GET['batch'])) {
                $payments = $batchPayments;
            }
        }
    }

    // Set $payment as the first one for header metadata compatibility
    $payment = $payments[0];
    $isBulk = count($payments) > 1;

    // Check if ALL payments in this batch/request are verified
    $allBatchVerified = true;
    foreach ($payments as $p) {
        if (strtolower(trim($p['verification_status'] ?? '')) !== 'verified') {
            $allBatchVerified = false;
            break;
        }
    }

    $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
    $userRoleName = strtolower($_SESSION['user']['role_name'] ?? '');
    $isCedUser = in_array($userRoleId, [7, 37, 39], true)
        || in_array((int)($_SESSION['user']['secondary_role_id'] ?? 0), [7, 37, 39], true)
        || strpos($userRoleName, 'client experience') !== false;

    // CED restriction: Money receipt cannot be accessed unless TOTAL batch_id payments are verified!
    if ($isCedUser && !$allBatchVerified) {
        die("<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='utf-8'>
    <title>Money Receipt Not Available</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif; background:#f8fafc; display:flex; align-items:center; justify-content:center; min-height:80vh; margin:0;'>
    <div style='background:#ffffff; border:1px solid #fee2e2; border-radius:12px; padding:35px; max-width:480px; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.06);'>
        <div style='width:56px; height:56px; background:#fef2f2; color:#ef4444; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:24px; margin-bottom:16px;'>
            <i class='fa-solid fa-lock'></i>
        </div>
        <h3 style='color:#1e293b; margin:0 0 8px 0; font-size:20px;'>Money Receipt Locked</h3>
        <p style='color:#64748b; font-size:13.5px; line-height:1.6; margin-bottom:20px;'>
            This money receipt cannot be viewed or printed by Client Experience (CED) because one or more payments in this transaction batch are still pending verification by Accounts.
        </p>
        <div style='background:#fefce8; border:1px solid #fef08a; padding:10px 14px; border-radius:6px; font-size:12.5px; color:#854d0e; margin-bottom:20px; text-align:left;'>
            <i class='fa-solid fa-circle-info me-1'></i> <strong>Rule:</strong> All payments under the same <code>batch_id</code> must be verified before the receipt is issued.
        </div>
        <button onclick='window.close(); history.back();' style='background:#0ea5e9; color:#ffffff; border:none; padding:9px 22px; border-radius:6px; font-weight:600; cursor:pointer;'>
            Close / Go Back
        </button>
    </div>
</body>
</html>");
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

public function verifyPublicReceipt($paymentId = null)
{
    global $db;
    if (!$db && isset($this->db)) {
        $db = $this->db;
    }

    if (!$paymentId) {
        $paymentId = $_GET['id'] ?? ($_GET['payment_id'] ?? null);
    }

    $rawIds = array_filter(array_map('trim', explode(',', (string)$paymentId)));
    $payments = [];
    $clientDetails = null;

    if (!empty($rawIds)) {
        if (count($rawIds) === 1) {
            $singlePay = $this->clientModel->getTransactionById((int)$rawIds[0]);
            if ($singlePay && (empty($singlePay['is_trashed']) || $singlePay['is_trashed'] == 0)) {
                if (!empty($singlePay['batch_id'])) {
                    $batchPayments = $this->clientModel->getTransactionsByBatchId($singlePay['batch_id'], $singlePay['booking_id'] ?? null);
                    $batchPayments = array_values(array_filter($batchPayments, function($p) {
                        $isTrashed = !empty($p['is_trashed']) && (int)$p['is_trashed'] === 1;
                        $isRejected = strtolower(trim($p['verification_status'] ?? '')) === 'rejected';
                        return !$isTrashed && !$isRejected;
                    }));
                    if (count($batchPayments) > 1) {
                        $types = array_unique(array_filter(array_map(fn($p) => $p['payment_type'] ?? '', $batchPayments)));
                        $isAllEmi = count($types) === 1 && in_array(reset($types), ['emi', 'emi_due_settlement']);
                        if ($isAllEmi || !empty($_GET['batch'])) {
                            $payments = $batchPayments;
                        } else {
                            $payments = [$singlePay];
                        }
                    } else {
                        $payments = [$singlePay];
                    }
                } else {
                    $payments = [$singlePay];
                }
            }
        } else {
            foreach ($rawIds as $pid) {
                $pay = $this->clientModel->getTransactionById((int)$pid);
                if ($pay && (empty($pay['is_trashed']) || $pay['is_trashed'] == 0)) {
                    $payments[] = $pay;
                }
            }
        }
    }

    $payment = null;
    if (!empty($payments)) {
        $actualPaidPayments = array_values(array_filter($payments, function($p) {
            return empty($p['is_emi_discount']) || (int)$p['is_emi_discount'] === 0;
        }));
        $payment = !empty($actualPaidPayments) ? $actualPaidPayments[0] : $payments[0];

        if (!empty($payment['booking_id'])) {
            $clientDetails = $this->clientModel->getClientByBookingId($payment['booking_id']);
        }
    }

    $root = dirname(dirname(__DIR__));
    $viewPath = $root . '/app/views/accounts/cashIn/public-verify-receipt.php';

    if (!file_exists($viewPath)) {
        die("Verification view not found.");
    }

    include $viewPath;
    exit;
}

public function downloadPublicReceiptPdf($paymentId = null)
{
    global $db;
    if (!$db && isset($this->db)) {
        $db = $this->db;
    }

    if (!$paymentId) {
        $paymentId = $_GET['id'] ?? ($_GET['payment_id'] ?? null);
    }

    $rawIds = array_filter(array_map('trim', explode(',', (string)$paymentId)));
    $payments = [];

    if (!empty($rawIds)) {
        if (count($rawIds) === 1) {
            $singlePay = $this->clientModel->getTransactionById((int)$rawIds[0]);
            if ($singlePay && (empty($singlePay['is_trashed']) || $singlePay['is_trashed'] == 0)) {
                if (!empty($singlePay['batch_id'])) {
                    $batchPayments = $this->clientModel->getTransactionsByBatchId($singlePay['batch_id'], $singlePay['booking_id'] ?? null);
                    $batchPayments = array_values(array_filter($batchPayments, function($p) {
                        $isTrashed = !empty($p['is_trashed']) && (int)$p['is_trashed'] === 1;
                        $isRejected = strtolower(trim($p['verification_status'] ?? '')) === 'rejected';
                        return !$isTrashed && !$isRejected;
                    }));
                    if (count($batchPayments) > 1) {
                        $types = array_unique(array_filter(array_map(fn($p) => $p['payment_type'] ?? '', $batchPayments)));
                        $isAllEmi = count($types) === 1 && in_array(reset($types), ['emi', 'emi_due_settlement']);
                        if ($isAllEmi || !empty($_GET['batch'])) {
                            $payments = $batchPayments;
                        } else {
                            $payments = [$singlePay];
                        }
                    } else {
                        $payments = [$singlePay];
                    }
                } else {
                    $payments = [$singlePay];
                }
            }
        } else {
            foreach ($rawIds as $pid) {
                $pay = $this->clientModel->getTransactionById((int)$pid);
                if ($pay && (empty($pay['is_trashed']) || $pay['is_trashed'] == 0)) {
                    $payments[] = $pay;
                }
            }
        }
    }

    if (empty($payments)) {
        die("Receipt record not found.");
    }

    $actualPaidPayments = array_values(array_filter($payments, function($p) {
        return empty($p['is_emi_discount']) || (int)$p['is_emi_discount'] === 0;
    }));
    $payment = !empty($actualPaidPayments) ? $actualPaidPayments[0] : $payments[0];

    $clientDetails = !empty($payment['booking_id']) 
        ? $this->clientModel->getClientByBookingId($payment['booking_id']) 
        : null;

    $isVerified = isset($payment['verification_status']) && strtolower(trim($payment['verification_status'])) === 'verified';

    // 1. Client Phone (Clean, no broken masking)
    $clientPhone = !empty($clientDetails['phone']) ? $clientDetails['phone'] : (!empty($clientDetails['phone_number']) ? $clientDetails['phone_number'] : (!empty($payment['phone']) ? $payment['phone'] : 'N/A'));
    if (!empty($clientDetails['2nd_owner_phone'])) {
        $clientPhone .= ', ' . trim($clientDetails['2nd_owner_phone']);
    }
    if ($clientPhone !== 'N/A' && strlen($clientPhone) === 10 && substr($clientPhone, 0, 1) === '1') {
        $clientPhone = '0' . $clientPhone;
    }

    // 2. Created By Resolution (sales_by_id or assigned_id)
    $salesPersonName = '';
    $salesPersonEmpId = '';
    if (!empty($payment['sales_by_name'])) {
        $salesPersonName = trim($payment['sales_by_name']);
        $salesPersonEmpId = trim($payment['sales_by_employee_id'] ?? '');
    } elseif (!empty($clientDetails['agent_name'])) {
        $salesPersonName = trim($clientDetails['agent_name']);
        $salesPersonEmpId = trim($clientDetails['agent_employee_id'] ?? '');
    }

    if (!empty($salesPersonName)) {
        $createdByName = $salesPersonName;
        $createdByEmpId = $salesPersonEmpId;
    } else {
        $createdByName = !empty($payment['created_by_name']) ? $payment['created_by_name'] : 'System';
        $createdByEmpId = !empty($payment['created_by_employee_id']) ? trim($payment['created_by_employee_id']) : '';
    }
    $createdByDisplay = $createdByName . (!empty($createdByEmpId) ? ' (' . $createdByEmpId . ')' : '');

    // 3. Verified By Resolution (Accounts roles -> officer name; Management roles -> Accounts)
    $vRoleId = (int)($payment['verified_by_role_id'] ?? 0);
    $vRoleName = trim($payment['verified_by_role_name'] ?? '');
    $vFullName = trim($payment['verified_by_name'] ?? '');
    $vEmpId = trim($payment['verified_by_employee_id'] ?? '');

    if ($isVerified) {
        $isAccountsRole = in_array($vRoleId, [6, 30, 36]) || (stripos($vRoleName, 'account') !== false);
        $isManagementRole = in_array($vRoleId, [1, 3]) || (stripos($vFullName, 'management') !== false) || (stripos($vRoleName, 'admin') !== false);

        if ($isAccountsRole) {
            $verifiedByName = !empty($vFullName) ? $vFullName : 'Accounts Officer';
            if (!empty($vEmpId)) {
                $verifiedByName .= ' (' . $vEmpId . ')';
            }
        } elseif ($isManagementRole || strtolower($vFullName) === 'management') {
            $verifiedByName = 'Accounts';
        } else {
            $verifiedByName = !empty($vFullName) ? $vFullName : 'Accounts';
            if (!empty($vEmpId)) {
                $verifiedByName .= ' (' . $vEmpId . ')';
            }
        }
    } else {
        $verifiedByName = 'Pending Verification';
    }

    // 4. Details
    $receiptNo = '#REC-' . str_pad($payment['id'], 6, '0', STR_PAD_LEFT);
    $paymentDateFormatted = date('d M, Y', strtotime($payment['payment_date'] ?? 'now'));
    $isDiscountItem = !empty($payment['is_emi_discount']) && (int)$payment['is_emi_discount'] === 1;
    $amountPaid = !empty($actualPaidPayments)
        ? array_sum(array_map(fn($p) => (float)($p['amount'] ?? 0), $actualPaidPayments))
        : ($isDiscountItem ? 0 : (float)($payment['amount'] ?? 0));
    $amountInWords = function_exists('amountInWords') ? amountInWords($amountPaid) : (class_exists('FormatHelper') ? FormatHelper::amountInWords($amountPaid) : '');

    $clientName = !empty($clientDetails['client_name']) ? $clientDetails['client_name'] : ($payment['client_name'] ?? 'N/A');
    if (!empty($clientDetails['2nd_lead_owner_name'])) {
        $clientName .= ' & ' . trim($clientDetails['2nd_lead_owner_name']);
    }
    $projectName = !empty($clientDetails['project_name']) ? $clientDetails['project_name'] : ($payment['project_name'] ?? 'N/A');
    $unitName = !empty($clientDetails['unit_name']) ? trim(preg_replace('/^unit\s+/i', '', $clientDetails['unit_name'])) : (!empty($payment['unit_name']) ? trim(preg_replace('/^unit\s+/i', '', $payment['unit_name'])) : 'N/A');
    $floorNo = !empty($clientDetails['flat_no']) ? trim(preg_replace('/^floor\s+/i', '', $clientDetails['flat_no'])) : (!empty($payment['flat_no']) ? trim(preg_replace('/^floor\s+/i', '', $payment['flat_no'])) : 'N/A');

    $paymentMethod = strtoupper($payment['payment_method'] ?? 'CASH');
    $isCash = ($paymentMethod === 'CASH');
    $bankDisplay = !empty($payment['company_bank_name']) ? $payment['company_bank_name'] : (!empty($payment['bank_name']) ? $payment['bank_name'] : ($isCash ? 'Cash in Hand / Office' : 'Official Accounts'));

    $paymentTypeRaw = strtolower($payment['payment_type'] ?? '');
    if (!empty($payment['is_emi_discount']) && $payment['is_emi_discount'] == 1) {
        $paymentTypeLabel = 'APPLY PREVIOUS DISCOUNT';
    } elseif ($paymentTypeRaw === 'emi') {
        $paymentTypeLabel = !empty($payment['installment_label']) ? strtoupper($payment['installment_label']) : 'EMI INSTALLMENT';
    } elseif ($paymentTypeRaw === 'booking_money') {
        $paymentTypeLabel = 'BOOKING MONEY';
    } elseif ($paymentTypeRaw === 'registration') {
        $paymentTypeLabel = 'REGISTRATION MONEY';
    } elseif ($paymentTypeRaw === 'development_charge') {
        $paymentTypeLabel = 'DEVELOPMENT CHARGE';
    } elseif ($paymentTypeRaw === 'shop_service_charge') {
        $paymentTypeLabel = 'SHOP SERVICE CHARGE';
    } elseif ($paymentTypeRaw === 'others') {
        $otherPurpose = !empty($payment['other_purpose']) ? trim($payment['other_purpose']) : '';
        $paymentTypeLabel = !empty($otherPurpose) ? 'OTHERS (' . strtoupper($otherPurpose) . ')' : 'OTHERS';
    } else {
        $paymentTypeLabel = strtoupper(str_replace('_', ' ', $payment['payment_type'] ?? 'PAYMENT'));
    }

    $verifiedDateFormatted = 'Awaiting Verification';
    if ($isVerified) {
        if (!empty($payment['verified_at'])) {
            $verifiedDateFormatted = date('d M, Y • h:i A', strtotime($payment['verified_at']));
        } elseif (!empty($payment['updated_at']) && $payment['updated_at'] !== '0000-00-00 00:00:00') {
            $verifiedDateFormatted = date('d M, Y • h:i A', strtotime($payment['updated_at']));
        } else {
            $verifiedDateFormatted = date('d M, Y • h:i A');
        }
    }

    $currentProjectId = (int)($clientDetails['project_id'] ?? ($payment['project_id'] ?? 0));
    $currentProjectName = trim($clientDetails['project_name'] ?? ($payment['project_name'] ?? ''));
    $currentCategoryId = (int)($clientDetails['category_id'] ?? ($payment['category_id'] ?? 0));
    $currentCategoryName = trim($clientDetails['category_name'] ?? ($payment['category_name'] ?? ''));

    if ($db && $currentProjectId > 0 && ($currentCategoryId <= 0 || empty($currentCategoryName) || empty($currentProjectName))) {
        try {
            $catStmt = $db->prepare("
                SELECT p.name AS project_name, p.category_id, pc.name AS category_name
                FROM projects p
                LEFT JOIN project_categories pc ON p.category_id = pc.id
                WHERE p.id = ? LIMIT 1
            ");
            if ($catStmt) {
                $catStmt->bind_param("i", $currentProjectId);
                $catStmt->execute();
                $catRes = $catStmt->get_result();
                if ($catRow = $catRes->fetch_assoc()) {
                    if (empty($currentProjectName)) $currentProjectName = trim($catRow['project_name'] ?? '');
                    if ($currentCategoryId <= 0) $currentCategoryId = (int)($catRow['category_id'] ?? 0);
                    if (empty($currentCategoryName)) $currentCategoryName = trim($catRow['category_name'] ?? '');
                }
                $catStmt->close();
            }
        } catch (Exception $e) {}
    }

    $isNatureProject = ($currentProjectId === 74) 
        || ($currentCategoryId === 9) 
        || (stripos($currentProjectName, 'The Nature') !== false) 
        || (stripos($currentProjectName, 'Nature') !== false);

    $isOlympusProject = ($currentCategoryId === 10) 
        || (stripos($currentCategoryName, 'Olympus') !== false) 
        || (stripos($currentProjectName, 'Olympus') !== false);

    if ($isOlympusProject) {
        $categoryBrandName = !empty($currentCategoryName) ? $currentCategoryName : "The Olympus Real Estate";
        $companyTitle   = $categoryBrandName;
        $brandDomain    = $categoryBrandName;
        $brandSlogan    = "REAL ESTATE";
        $companyAddress = "23/2, SEL HUQ Skypark, 4th floor, Opposite of Wonderland (Shyamoli), Dhaka";
        $companyPhone   = "01958-253302";
        $companyEmail   = "info@theolympusrealestate.com";
        $companyWebsite = "www.theolympusrealestate.com";
        $uploadsDir     = dirname(dirname(__DIR__)) . '/public/uploads/';
        $logoFilePath   = $uploadsDir . 'Olympus-logo.png';
        foreach (['Olympus-logo.png', 'Olympus-logo.jpeg', 'olympus-logo.png', 'olympus-logo.jpeg'] as $cand) {
            if (file_exists($uploadsDir . $cand)) {
                $logoFilePath = $uploadsDir . $cand;
                break;
            }
        }

        $themePrimary   = "#c58b42";
        $themeDark      = "#3e3b42";
        $themeDeep      = "#2c2930";
        $themeLight     = "#d49b56";
        $themeBgLight   = "#fdfbf7";
        $themeBadgeBg   = "#f7efe4";
        $themeBorder    = "#ebd8c1";
        $themeRgb       = [197, 139, 66];
    } elseif ($isNatureProject) {
        $companyTitle   = "The Nature";
        $brandDomain    = "The Nature";
        $brandSlogan    = "A LUXURY RESORT COMMUNITY";
        $companyAddress = "Marine Drive, Cox's Bazar | Corporate Office: Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";
        $companyPhone   = "+8801958253303";
        $companyEmail   = "accounts@dpremiumhomes.com";
        $logoFilePath   = dirname(dirname(__DIR__)) . '/public/uploads/nature-logo.png';

        $themePrimary   = "#0284c7";
        $themeDark      = "#0369a1";
        $themeDeep      = "#075985";
        $themeLight     = "#38bdf8";
        $themeBgLight   = "#f0f9ff";
        $themeBadgeBg   = "#e0f2fe";
        $themeBorder    = "#bae6fd";
        $themeRgb       = [2, 132, 199];
    } else {
        $companyTitle   = function_exists('getSetting') ? getSetting('website_title', 'The Premium Homes Ltd.') : 'The Premium Homes Ltd.';
        $brandDomain    = "Real Estate & Property Management";
        $brandSlogan    = "YOUR DREAM HOME AWAITS";
        $companyPhone   = function_exists('getSetting') ? getSetting('phone', '+8801958253303') : '+8801958253303';
        $companyEmail   = function_exists('getSetting') ? getSetting('email', 'accounts@dpremiumhomes.com') : 'accounts@dpremiumhomes.com';
        $companyAddress = "Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";

        $whiteLogo = function_exists('getSetting') ? (getSetting('white_logo') ?: (getSetting('dark_logo') ?: getSetting('logo'))) : null;
        $logoFileName = !empty($whiteLogo) ? $whiteLogo : '6948f14a9fb8c_1766388042.png';
        $logoFilePath = dirname(dirname(__DIR__)) . '/public/uploads/settings/' . $logoFileName;

        $themePrimary   = "#0b6638";
        $themeDark      = "#0b4022";
        $themeDeep      = "#0f3b25";
        $themeLight     = "#10b981";
        $themeBgLight   = "#f0fdf4";
        $themeBadgeBg   = "#ecfdf5";
        $themeBorder    = "#bbf7d0";
        $themeRgb       = [11, 102, 56];
    }

    // QR Code
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $token = substr(hash('sha256', $paymentId . '_' . ($payment['created_at'] ?? 'tphl') . '_TPHL_VERIFY_KEY_2026'), 0, 12);
    $allPdfIds = array_map(fn($p) => (int)$p['id'], !empty($actualPaidPayments) ? $actualPaidPayments : $payments);
    $pdfIdsParam = implode(',', array_unique($allPdfIds));
    $verificationUrl = $protocol . $host . $GLOBALS['base_url'] . '/verify-receipt/' . $pdfIdsParam . '?token=' . $token;

    $qrImageSrc = '';
    if (class_exists('TCPDF2DBarcode')) {
        try {
            $barcode = new TCPDF2DBarcode($verificationUrl, 'QRCODE,M');
            $qrPng = $barcode->getBarcodePngData(3, 3, $themeRgb);
            if ($qrPng && strlen($qrPng) > 50) {
                $qrImageSrc = 'data:image/png;base64,' . base64_encode($qrPng);
            }
        } catch (Exception $e) {}
    }

    // Build PDF via mPDF
    $tmpDir = dirname(dirname(__DIR__)) . '/tmp/mpdf';
    if (!is_dir($tmpDir)) {
        @mkdir($tmpDir, 0777, true);
    }

    $refNo = !empty($payment['reference_number']) ? $payment['reference_number'] : (!empty($payment['transaction_id']) ? $payment['transaction_id'] : 'N/A');
    $senderBankName = !empty($payment['bank_name']) && !in_array(strtolower($payment['bank_name']), ['0', 'n/a', 'none', 'cash']) ? $payment['bank_name'] : ($isCash ? 'Cash Payment' : 'N/A');
    $senderAccountNo = !empty($payment['bank_account_no']) && !in_array(strtolower(trim($payment['bank_account_no'])), ['0', '00', 'n/a', 'none', 'null']) ? trim($payment['bank_account_no']) : '';
    $receiverBankName = !empty($payment['company_bank_name']) ? $payment['company_bank_name'] : 'BRAC Bank PLC';
    $receiverHolderName = !empty($payment['company_account_name']) ? $payment['company_account_name'] : 'The Premium Homes Ltd.';
    $receiverAccountNo = !empty($payment['company_account_number']) ? $payment['company_account_number'] : '2073570070001';

    // Build PDF via mPDF (Single Customer Receipt for Client Download)
    $tmpDir = dirname(dirname(__DIR__)) . '/tmp/mpdf';
    if (!is_dir($tmpDir)) {
        @mkdir($tmpDir, 0777, true);
    }

    $mpdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'margin_left' => 12,
        'margin_right' => 12,
        'margin_top' => 10,
        'margin_bottom' => 10,
        'tempDir' => $tmpDir
    ]);

    $badgeText = $isVerified ? ($isCash ? 'Cash Verified' : 'Verified') : 'Subject to Verification';

    $statusHtml = $isVerified ? '
        <div style="background: ' . $themeBgLight . '; border: 1.5px solid ' . $themeBorder . '; border-radius: 6px; padding: 4px 10px; display: inline-block; text-align: left;">
            <span style="font-size: 8px; font-weight: bold; color: ' . $themeDark . '; text-transform: uppercase;">PAYMENT STATUS</span><br>
            <b style="font-size: 11px; color: ' . $themePrimary . ';">Verified &amp; Confirmed</b><br>
            <span style="font-size: 8px; color: ' . $themeDark . ';">Payment verified officially.</span>
        </div>' : '
        <div style="background: #fffbeb; border: 1.5px solid #fef3c7; border-radius: 6px; padding: 4px 10px; display: inline-block; text-align: left;">
            <span style="font-size: 8px; font-weight: bold; color: #b45309; text-transform: uppercase;">PAYMENT STATUS</span><br>
            <b style="font-size: 11px; color: #d97706;">Subject to Bank Verification</b><br>
            <span style="font-size: 8px; color: #92400e;">Confirmation issued after verification.</span>
        </div>';

    $stampColor = $isOlympusProject ? $themePrimary : ($isNatureProject ? '#0284c7' : '#2563eb');
    $stampText = $isOlympusProject ? 'OLYMPUS' : ($isNatureProject ? 'NATURE' : 'TPHL');

    $html = '
    <style>
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #0f172a; font-size: 10.5px; line-height: 1.4; margin: 0; padding: 0; }
        .header-tbl { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .company-name { font-size: 16px; font-weight: 900; color: ' . $themeDark . '; text-transform: uppercase; letter-spacing: 0.5px; }
        .company-slogan { font-size: 7.5px; color: ' . $themePrimary . '; font-weight: bold; letter-spacing: 1.8px; text-transform: uppercase; margin-top: 2px; }
        .company-tag { font-size: 11px; color: ' . $themePrimary . '; font-weight: bold; margin-top: 4px; }
        .company-desc { font-size: 9px; color: #475569; line-height: 1.35; margin-top: 2px; }
        .doc-title { font-size: 18px; font-weight: 900; color: #0f172a; text-align: right; }
        .copy-pill { background: ' . $themeDeep . '; color: #ffffff; padding: 3px 10px; font-size: 9px; font-weight: bold; border-radius: 4px; text-transform: uppercase; }
        .doc-rec { font-size: 14px; font-weight: 800; color: #0f172a; text-align: right; margin-top: 3px; }
        .doc-date { font-size: 11px; color: #475569; text-align: right; margin-top: 1px; }
        
        .green-divider { height: 3px; background-color: ' . $themePrimary . '; width: 100%; margin: 8px 0 14px 0; }

        .info-tbl { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .info-card { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; font-size: 10px; }
        .card-head { font-size: 10px; font-weight: 800; color: #0f172a; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px; margin-bottom: 6px; }
        .row-item { margin-bottom: 4px; }
        .lbl { color: #64748b; font-size: 9.5px; width: 85px; display: inline-block; font-weight: 600; }
        .val { font-weight: 800; color: #0f172a; }

        .amount-banner { background: ' . $themeBgLight . '; border: 1.5px solid ' . $themeBorder . '; border-radius: 6px; padding: 10px 14px; margin-bottom: 12px; }
        .amt-title { font-size: 10.5px; font-weight: 800; color: #0f172a; }
        .method-pill { background: ' . $themeDeep . '; color: #ffffff; padding: 2px 7px; font-size: 9px; font-weight: bold; border-radius: 3px; margin-left: 4px; }
        .words-pill { background: ' . $themeBadgeBg . '; color: ' . $themePrimary . '; font-size: 8px; font-weight: bold; padding: 1px 6px; border-radius: 3px; }
        .amt-num { font-size: 24px; font-weight: 900; color: #0f172a; text-align: right; }

        .proc-box { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; margin-bottom: 12px; font-size: 9.5px; }
        .proc-col { vertical-align: top; }
        .proc-lbl { color: #64748b; font-size: 8.5px; font-weight: 600; margin-bottom: 2px; }
        .proc-val { font-weight: 800; color: #0f172a; font-size: 10px; }

        .verify-box { border: 1.5px solid ' . $themeBorder . '; background: ' . $themeBgLight . '; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; }
    </style>

    <table class="header-tbl">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                ' . (file_exists($logoFilePath) ? '<img src="' . $logoFilePath . '" style="height: 38px; margin-bottom: 4px; border-radius: 4px;" /><br>' : '<div class="company-name">' . htmlspecialchars($companyTitle) . '</div><div class="company-slogan">' . htmlspecialchars($brandSlogan) . '</div>') . '
                <div class="company-tag">' . htmlspecialchars($brandDomain) . '</div>
                <div class="company-desc">
                    ' . htmlspecialchars($companyAddress) . '<br>
                    <b>' . htmlspecialchars($companyPhone) . '</b> &bull; ' . htmlspecialchars($companyEmail) . (!empty($companyWebsite) ? ' &bull; ' . htmlspecialchars($companyWebsite) : '') . '
                </div>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: right; vertical-align: middle;">
                            <span class="doc-title">PAYMENT RECEIPT</span>
                        </td>
                        <td style="width: 105px; text-align: right; vertical-align: middle;">
                            <span class="copy-pill">CUSTOMER COPY</span>
                        </td>
                    </tr>
                </table>
                <div class="doc-rec">' . htmlspecialchars($receiptNo) . '</div>
                <div class="doc-date">' . htmlspecialchars($paymentDateFormatted) . '</div>
                <div style="margin-top: 4px; display: inline-block;">' . $statusHtml . '</div>
            </td>
        </tr>
    </table>

    <div class="green-divider"></div>

    <table class="info-tbl">
        <tr>
            <td style="width: 49%; vertical-align: top; padding-right: 6px;">
                <div class="info-card">
                    <div class="card-head">RECEIVED FROM</div>
                    <div class="row-item"><span class="lbl">Client Name:</span> <span class="val">' . htmlspecialchars($clientName) . '</span></div>
                    <div class="row-item"><span class="lbl">Phone:</span> <span class="val">' . htmlspecialchars($clientPhone) . '</span></div>
                    <div class="row-item"><span class="lbl">Email:</span> <span class="val">N/A</span></div>
                </div>
            </td>
            <td style="width: 51%; vertical-align: top; padding-left: 6px;">
                <div class="info-card">
                    <div class="card-head">PROPERTY INFORMATION</div>
                    <div class="row-item"><span class="lbl">Project:</span> <span class="val">' . htmlspecialchars($projectName) . '</span></div>
                    <div class="row-item"><span class="lbl">Unit:</span> <span class="val">' . htmlspecialchars($unitName) . '</span></div>
                    <div class="row-item"><span class="lbl">Floor:</span> <span class="val">' . htmlspecialchars($floorNo) . '</span></div>
                </div>
            </td>
        </tr>
    </table>

    <div class="amount-banner">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: middle;">
                    <div class="amt-title">TOTAL AMOUNT PAID BY <span class="method-pill">' . htmlspecialchars($paymentMethod) . '</span></div>
                    <div style="margin-top: 4px;">
                        <span class="words-pill">IN WORDS</span> 
                        <span style="font-weight: 800; font-size: 11px; color: #0f172a;">' . htmlspecialchars($amountInWords) . '</span>
                    </div>
                </td>
                <td style="text-align: right; vertical-align: middle; width: 200px;">
                    <div class="amt-num">TK. ' . number_format($amountPaid, 2) . '</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="proc-box">
        <div class="card-head" style="margin-bottom: 4px;">BANK &amp; PROCESSING INFORMATION</div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td class="proc-col" style="width: 28%;">
                    <div class="proc-lbl">Created By:</div>
                    <div class="proc-val">' . htmlspecialchars($createdByName) . '</div>
                    ' . (!empty($createdByEmpId) ? '<div style="color: #64748b; font-size: 8.5px;">(' . htmlspecialchars($createdByEmpId) . ')</div>' : '') . '
                </td>
                <td class="proc-col" style="width: 26%;">
                    <div class="proc-lbl">Sender Bank</div>
                    <div class="proc-val">' . htmlspecialchars($senderBankName) . '</div>
                    ' . (!empty($senderAccountNo) ? '<div style="font-weight: bold; font-size: 8.5px;">&bull; A/C: ' . htmlspecialchars($senderAccountNo) . '</div>' : '') . '
                </td>
                <td class="proc-col" style="width: 32%;">
                    <div class="proc-lbl">Receiver Bank</div>
                    <div class="proc-val">' . htmlspecialchars($receiverBankName) . '</div>
                    <div style="color: #64748b; font-size: 8.5px;">' . htmlspecialchars($receiverHolderName) . '</div>
                    <div style="font-weight: bold; font-size: 8.5px;">&bull; A/C: ' . htmlspecialchars($receiverAccountNo) . '</div>
                </td>
                <td class="proc-col" style="width: 14%;">
                    <div class="proc-lbl">Ref Number</div>
                    <div class="proc-val">' . htmlspecialchars($refNo) . '</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="verify-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: middle;">
                    <div style="font-size: 10.5px; font-weight: 800; color: ' . $themeDark . '; text-transform: uppercase; margin-bottom: 3px;">
                        &#10004; ' . ($isCash ? 'CASH-VERIFIED' : 'OFFICIAL-VERIFIED') . ' PAYMENT RECEIPT
                    </div>
                    <div style="font-size: 9px; color: #64748b; margin-bottom: 6px;">
                        This payment has been reviewed and confirmed through the official ERP system.
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 9px; border-top: 1px solid ' . $themeBorder . '; padding-top: 4px;">
                        <tr>
                            <td style="width: 32%;"><b>Receipt ID:</b> ' . htmlspecialchars($receiptNo) . '</td>
                            <td style="width: 34%;"><b>Verified By:</b> <span style="color: ' . ($isVerified ? $themeDark : '#d97706') . '; font-weight: bold;">' . htmlspecialchars($verifiedByName) . '</span></td>
                            <td style="width: 34%;"><b>Verified On:</b> <span style="color: ' . ($isVerified ? $themeDark : '#d97706') . '; font-weight: bold;">' . htmlspecialchars($verifiedDateFormatted) . '</span></td>
                        </tr>
                    </table>
                    <div style="font-size: 8px; color: #64748b; font-style: italic; margin-top: 4px;">
                        This system-generated receipt is valid without a physical signature.
                    </div>
                </td>
                <td style="width: 155px; text-align: right; vertical-align: middle;">
                    <table style="border-collapse: collapse; margin-left: auto;">
                        <tr>
                            ' . (!empty($qrImageSrc) ? '
                            <td style="text-align: center; vertical-align: middle; padding-right: 10px;">
                                <img src="' . $qrImageSrc . '" style="width: 52px; height: 52px; border: 1px solid ' . $themeBorder . '; padding: 2px; border-radius: 4px; background: #ffffff;" />
                                <div style="font-size: 7px; font-weight: bold; color: ' . $themeDark . '; text-align: center; margin-top: 2px;">SCAN TO VERIFY</div>
                            </td>' : '') . '
                            <td style="text-align: center; vertical-align: middle;">
                                <div style="border: 2px solid ' . $stampColor . '; border-radius: 50%; width: 52px; height: 52px; text-align: center; padding-top: 10px;">
                                    <div style="font-size: 7.5px; font-weight: 900; color: ' . $stampColor . ';">' . $stampText . '</div>
                                    <div style="height: 1px; background: ' . $stampColor . '; width: 30px; margin: 1px auto;"></div>
                                    <div style="font-size: 6.5px; font-weight: bold; color: ' . $stampColor . ';">OFFICIAL</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div style="border-top: 1px solid #e2e8f0; margin-top: 20px; padding-top: 6px; text-align: center;">
        <span style="font-size: 9.5px; font-weight: 700; color: #475569; letter-spacing: 2px; text-transform: uppercase;">' . htmlspecialchars($brandSlogan) . '</span>
    </div>
    ';

    $mpdf->WriteHTML($html);
    $filename = 'Payment-Receipt-' . str_pad($paymentId, 6, '0', STR_PAD_LEFT) . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo $mpdf->Output('', 'S');
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
        
        // Prepare EMI schedule data if applicable
        $emiScheduleData = null;
        if ($data['payment_type'] === 'emi') {
            $emiScheduleData = [
                'due_emi_amount' => $data['due_emi_amount'] ?? 0,
                'due_date' => $data['due_date'] ?? date('Y-m-d')
            ];
        }
        
        // Route to correct model method (waterfall for flat price components, single for others)
        if ($transactionData['payment_type'] === 'others') {
            $result = $accountModel->addTransaction($transactionData, null);
        } else {
            $result = $accountModel->addTransactionWithEMI($transactionData, $emiScheduleData);
        }
        
        if (!empty($result['success']) && !empty($result['payment_id'])) {
            $overrideAmount = $data['amount'] ?? null;
            $overridePaymentType = !empty($data['payment_type']) ? ucfirst(str_replace('_', ' ', $data['payment_type'])) : null;
            $smsService->sendPaymentReceivedSms($result['payment_id'], $isTestingMode, $overrideAmount, $overridePaymentType);
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
 * View Date-Wise Aggregated Payment Statement
 * Shows total paid per batch/date without internal payment_type breakdowns.
 * Accessible via /client-datewise-statement/{bookingId}
 */
public function viewClientDatewiseStatement($bookingId = null)
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!$bookingId) {
        $bookingId = $_GET['booking_id'] ?? null;
    }

    $bookingId = (int)$bookingId;
    if ($bookingId <= 0) {
        die("Invalid or missing booking ID.");
    }

    $summaryData = $this->clientModel->getDatewiseBatchPaymentSummary($bookingId);
    if (!$summaryData || empty($summaryData['client_details'])) {
        die("Booking details not found for ID: " . $bookingId);
    }

    $root = dirname(dirname(__DIR__));
    $viewPath = $root . '/app/views/accounts/cashIn/client-datewise-statement.php';

    if (!file_exists($viewPath)) {
        die("View file not found at: " . $viewPath);
    }

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
               u.full_name as added_by_name, lp.is_emi_discount
        FROM lead_payments lp
        LEFT JOIN users u ON lp.created_by = u.id
        LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
        WHERE lp.booking_id = ? AND (lp.bulk_emi_discount > 0 OR COALESCE(lp.is_emi_discount, 0) = 1)
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
        // role_id 1 = Super Admin, 3 = Admin, 6 = Accounts, 30 = Accounts Head, 36 = Accounts Executive
        $userRoleId     = (int)($_SESSION['user']['role_id'] ?? 0);
        $allowedRoleIds = [1, 3, 6, 30, 36];
        $canVerifyAction = in_array($userRoleId, $allowedRoleIds) || (function_exists('isAccountsExecutive') && isAccountsExecutive()) || hasPermission('Cash In Verify');
        if (!$canVerifyAction) {
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

                    // ====== TRIGGER 2: SMS VERIFIED NOTIFICATION (DISABLED ON VERIFY) ======
                    $sendSms = false; // Disabled: No SMS sent on verify as per request
                    $isTestingMode = isset($_POST['is_testing_mode']) ? filter_var($_POST['is_testing_mode'], FILTER_VALIDATE_BOOLEAN) : false;
                    if ($sendSms && !$isTestingMode) {
                        require_once dirname(__DIR__) . '/services/SmsService.php';
                        $smsService = new SmsService($this->db);
                        $smsService->sendBatchPaymentVerifiedSmsIfComplete($paymentId);
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
        $allowedRoleIds = [1, 3, 6, 30, 36];
        $canBulkVerify = in_array($userRoleId, $allowedRoleIds) || (function_exists('isAccountsExecutive') && isAccountsExecutive()) || hasPermission('Cash In Verify');
        if (!$canBulkVerify) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $paymentIds = $_POST['payment_ids'] ?? [];
        $sendSms    = false; // Disabled: No SMS sent on verify as per request
        $userId     = $_SESSION['user']['id'] ?? 0;

        if (empty($paymentIds) || !is_array($paymentIds)) {
            echo json_encode(['success' => false, 'message' => 'No payments selected']);
            exit;
        }

        $verifiedCount = 0;
        $batchesForSms = []; // batch_id => sample_payment_id
        $standaloneByBookingForSms = []; // booking_id => [total_amount, payment_id, types]

        require_once dirname(__DIR__) . '/controllers/PaymentBookingController.php';
        $paymentController = new PaymentBookingController($this->db);

        require_once dirname(__DIR__) . '/services/AutoPostingService.php';
        $autoService = new AutoPostingService($this->db);

        $smsService = null;
        if ($sendSms) {
            require_once dirname(__DIR__) . '/services/SmsService.php';
            $smsService = new SmsService($this->db);
        }

        $typeLabelsMap = [
            'booking_money' => 'Booking Money',
            'land_share'    => 'Land Share',
            'dev_charge'    => 'Dev Charge',
            'emi'           => 'EMI',
            'down_payment'  => 'Down Payment',
            'car_parking'   => 'Car Parking',
            'service_fee'   => 'Service Fee',
            'registration'  => 'Registration',
            'utility'       => 'Utility',
            'other'         => 'Other'
        ];

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
                    $isPartial = !empty($paymentData['due_emi_amount']) && (float)$paymentData['due_emi_amount'] > 0;
                    $newPaymentStatus = $isPartial ? 'partially_paid' : 'paid';

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

                    // Collect data for bulk SMS (Batch-Aware)
                    if ($sendSms) {
                        $batchId = trim((string)($paymentData['batch_id'] ?? ''));
                        if ($batchId !== '') {
                            if (!isset($batchesForSms[$batchId])) {
                                $batchesForSms[$batchId] = $paymentId;
                            }
                        } else {
                            if (!isset($standaloneByBookingForSms[$bookingId])) {
                                $standaloneByBookingForSms[$bookingId] = [
                                    'total_amount' => 0.0,
                                    'payment_id'   => $paymentId,
                                    'types'        => []
                                ];
                            }
                            $standaloneByBookingForSms[$bookingId]['total_amount'] += (float)$paymentData['amount'];
                            $rawType = strtolower(trim((string)($paymentData['payment_type'] ?? '')));
                            $label = $typeLabelsMap[$rawType] ?? ucwords(str_replace('_', ' ', $rawType));
                            if (!empty($label) && !in_array($label, $standaloneByBookingForSms[$bookingId]['types'], true)) {
                                $standaloneByBookingForSms[$bookingId]['types'][] = $label;
                            }
                        }
                    }
                }
            }

            $this->db->commit();

            // 5. Send SMS (Batch-Aware & Consolidated)
            if ($sendSms && $smsService) {
                // A) Dispatch batch SMS (only dispatches once ALL transactions in the batch are verified)
                foreach ($batchesForSms as $bId => $samplePaymentId) {
                    $smsService->sendBatchPaymentVerifiedSmsIfComplete($samplePaymentId, false);
                }

                // B) Dispatch standalone grouped SMS
                foreach ($standaloneByBookingForSms as $bId => $smsData) {
                    $typesStr = !empty($smsData['types']) ? implode(', ', $smsData['types']) : 'Payment';
                    $smsService->sendPaymentVerifiedSms($smsData['payment_id'], false, $smsData['total_amount'], $typesStr);
                }
            }
            echo json_encode(['success' => true, 'message' => "Successfully verified $verifiedCount transactions."]);
        } catch (Exception $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX: Bulk delete or trash selected transactions for a booking
     */
    public function bulkDelete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $roleName   = strtolower($_SESSION['user']['role'] ?? ($_SESSION['user']['role_name'] ?? ''));
        $isAccExec  = function_exists('isAccountsExecutive') ? isAccountsExecutive() : ($userRoleId === 36);
        $isAccounts = in_array($userRoleId, [1, 3, 6, 30], true);
        $isManagementUser = ($userRoleId === 1 || $userRoleId === 3 || str_contains($roleName, 'management') || str_contains($roleName, 'admin'));

        if ((!$isAccounts && !$isManagementUser) || $isAccExec) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have permission to bulk delete transactions.']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $paymentIds = $_POST['payment_ids'] ?? [];
        $bookingId  = (int)($_POST['booking_id'] ?? 0);
        $userId     = (int)($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 1));

        if (empty($paymentIds) || !is_array($paymentIds) || !$bookingId) {
            echo json_encode(['success' => false, 'message' => 'Invalid request: No payments selected or missing booking ID.']);
            exit;
        }

        $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
        if (!empty($clientDetails['is_shop']) && (int)$clientDetails['is_shop'] === 1) {
            require_once dirname(__DIR__) . '/models/AccountShopClient.php';
            $this->clientModel = new AccountShopClient($this->db);
        }

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($paymentIds as $pid) {
            $paymentId = (int)$pid;
            if (!$paymentId) continue;

            // Always safely move to trash so it can be viewed and restored from Trash list
            $res = $this->clientModel->trashTransaction($paymentId, $bookingId, $userId);
            if (!empty($res['success'])) {
                $successCount++;
            } else {
                $failedCount++;
                $errors[] = "ID #{$paymentId}: " . ($res['message'] ?? 'Failed');
            }
        }

        if ($successCount > 0) {
            $msg = "{$successCount} transaction(s) moved to trash successfully.";
            if ($failedCount > 0) {
                $msg .= " ({$failedCount} failed)";
            }
            echo json_encode(['success' => true, 'message' => $msg, 'count' => $successCount]);
        } else {
            $errMsg = !empty($errors) ? implode('; ', array_slice($errors, 0, 3)) : 'Failed to move selected transactions to trash.';
            echo json_encode(['success' => false, 'message' => $errMsg]);
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
            SELECT id, bank_name, account_name, account_number, is_default, status
            FROM   company_bank_accounts
            WHERE  status = 'active'
            ORDER BY is_default DESC, bank_name ASC, id ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $banks  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        foreach ($banks as &$b) {
            $b['branch_name'] = $b['account_name'] . (!empty($b['account_number']) ? ' (' . $b['account_number'] . ')' : '');
            $b['routing_number'] = '';
        }
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

        $files = $_FILES['proof_files'] ?? null;

        if (!$files || empty($files['name'][0])) {
            echo json_encode(['success' => false, 'message' => 'No files received']);
            exit;
        }

        $ajaxUploadErrors = [];
        $uploadedPaths = $this->handleProofFileUploads($files, 'proof_' . $paymentId . '_', $ajaxUploadErrors);

        if (empty($uploadedPaths)) {
            $errMsg = !empty($ajaxUploadErrors) ? implode(' ', $ajaxUploadErrors) : 'No valid files uploaded';
            echo json_encode(['success' => false, 'message' => $errMsg]);
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
                lp.attachment_image2,
                lp.attachment_image3,
                lp.attachment_image4,
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
        if (!empty($row['attachment_image2'])) {
            $legacyPath2 = 'uploads/payments/' . $row['attachment_image2'];
            if (!in_array($legacyPath2, $files)) {
                $files[] = $legacyPath2;
            }
        }
        if (!empty($row['attachment_image3'])) {
            $legacyPath3 = 'uploads/payments/' . $row['attachment_image3'];
            if (!in_array($legacyPath3, $files)) {
                $files[] = $legacyPath3;
            }
        }
        if (!empty($row['attachment_image4'])) {
            $legacyPath4 = 'uploads/payments/' . $row['attachment_image4'];
            if (!in_array($legacyPath4, $files)) {
                $files[] = $legacyPath4;
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

    /**
     * Stream a transaction / payment / waiver attachment inline or as a file download.
     * Route: GET /view-transaction-file?file=uploads/transactions/proof_xxx.pdf[&download=1]
     */
    public function serveTransactionFile()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $rawFile = trim((string)($_GET['file'] ?? ''));
        $relPath = str_replace('\\', '/', rawurldecode($rawFile));
        $relPath = ltrim($relPath, '/');
        if (strpos($relPath, 'public/') === 0) {
            $relPath = substr($relPath, strlen('public/'));
        }
        $relPath = ltrim($relPath, '/');

        $allowedPrefixes = [
            'uploads/transactions/',
            'uploads/payments/',
            'uploads/waivers/',
        ];
        $prefixOk = false;
        foreach ($allowedPrefixes as $prefix) {
            if (strpos($relPath, $prefix) === 0) {
                $prefixOk = true;
                break;
            }
        }

        $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp'];

        if ($relPath === '' || strpos($relPath, '..') !== false || strpos($relPath, "\0") !== false || !$prefixOk || !in_array($ext, $allowedExts, true)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Invalid or unauthorized file path.';
            exit;
        }

        $projectRoot   = str_replace('\\', '/', dirname(dirname(__DIR__)));
        $webRoot       = $this->getWebPublicDir();
        $parentRoot    = str_replace('\\', '/', dirname($projectRoot));
        $subUploadPath = preg_replace('#^uploads/#', '', $relPath);

        $candidates = array_values(array_unique([
            $webRoot . '/' . $relPath,
            $projectRoot . '/public/' . $relPath,
            $projectRoot . '/' . $relPath,
            $webRoot . '/public/' . $relPath,
            $webRoot . '/uploads/' . $relPath,
            $parentRoot . '/public/' . $relPath,
            '/var/www/html/TPHL-ERP/public/' . $relPath,
            '/var/www/html/TPHL-ERP/' . $relPath,
            '/var/www/staging/TPHL-ERP/public/' . $relPath,
            '/var/www/staging/TPHL-ERP/' . $relPath,
            '/var/www/html/uploads_backup_temp/' . $subUploadPath,
        ]));

        $foundPath = null;
        foreach ($candidates as $candidate) {
            if (@is_file($candidate) && @is_readable($candidate)) {
                $foundPath = $candidate;
                break;
            }
        }
        if (!$foundPath) {
            error_log('[serveTransactionFile] File missing across all candidate paths: ' . $relPath);
        }

        if (!$foundPath) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            $baseUrl = htmlspecialchars($GLOBALS['base_url'] ?? '', ENT_QUOTES, 'UTF-8');
            $safeBaseName = htmlspecialchars(basename($relPath), ENT_QUOTES, 'UTF-8');
            echo "<!DOCTYPE html><html><head><title>File Not Found</title><link rel='stylesheet' href='{$baseUrl}/assets/css/vendor/bootstrap.min.css'></head><body class='bg-light d-flex align-items-center justify-content-center' style='min-height:80vh;'>";
            echo "<div class='text-center p-4 bg-white rounded shadow-sm border' style='max-width:520px;'>";
            echo "<h4 class='text-warning fw-bold mb-2'>Attached File Not Found</h4>";
            echo "<p class='text-muted mb-3'>The requested file was not found on the server or has been removed.</p>";
            echo "<div class='p-2 bg-light text-break rounded small text-secondary font-monospace mb-4'>{$safeBaseName}</div>";
            echo "<a href='javascript:history.back()' class='btn btn-outline-primary btn-sm px-4'>&larr; Go Back</a>";
            echo "</div></body></html>";
            exit;
        }

        $mimeMap = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jfif' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';
        $safeDownloadName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($foundPath));
        $isDownload = !empty($_GET['download']) && $_GET['download'] !== '0';
        $disposition = $isDownload ? 'attachment' : 'inline';

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . $safeDownloadName . '"');
        header('Content-Length: ' . filesize($foundPath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');
        readfile($foundPath);
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
        $callStatus = trim($_POST['call_status'] ?? '');
        $activityReason = trim($_POST['activity_reason'] ?? '');
        $nextFollowUpDate = !empty($_POST['next_follow_up_date']) ? trim($_POST['next_follow_up_date']) : null;
        $nextFollowUpNote = !empty($_POST['next_follow_up_note']) ? trim($_POST['next_follow_up_note']) : null;
        $userId = $_SESSION['user']['id'] ?? 0;

        if (!$bookingId || empty($note) || empty($activityDate) || empty($activityMode) || empty($activityReason)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $stmt = $this->db->prepare("INSERT INTO emi_followups (booking_id, emi_no, note, activity_date, activity_mode, call_status, activity_reason, next_follow_up_date, next_follow_up_note, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssssssi", $bookingId, $emiNo, $note, $activityDate, $activityMode, $callStatus, $activityReason, $nextFollowUpDate, $nextFollowUpNote, $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        exit;
    }

    /**
     * Save custom EMI Due Date fixed by CED (Client Experience Department)
     * Permission: Only CED role (7, 37, Credit Recovery, Client Experience) & Admin (1, 3)
     * Rejects Accounts roles and ensures lock check for fully paid EMIs.
     */
    public function saveEmiDueDate()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        // Permission check: only CED and Admin can set EMI due dates
        $isCedUser = in_array($userRoleId, [1, 3, 7, 37, 39], true)
            || in_array($userSecRoleId, [7, 37, 39], true)
            || strpos($roleName, 'head of client experience') !== false
            || strpos($roleName, 'client experience') !== false
            || strpos($roleName, 'credit recovery') !== false
            || strpos($roleName, 'client communication') !== false
            || (function_exists('isCreditRecovery') && isCreditRecovery());

        if (!$isCedUser) {
            echo json_encode([
                'success' => false, 
                'message' => 'Permission denied. Only Client Experience Department (CED) can schedule or modify EMI due dates.'
            ]);
            exit;
        }

        $bookingId       = (int)($_POST['booking_id'] ?? 0);
        $emiNo           = (int)($_POST['emi_no'] ?? 0);
        $dueDate         = trim($_POST['due_date'] ?? '');
        $notes           = trim($_POST['notes'] ?? '');
        $applySubsequent = !empty($_POST['apply_subsequent']) ? 1 : 0;
        $userId          = (int)($_SESSION['user']['id'] ?? 0);

        if (!$bookingId || !$emiNo || empty($dueDate)) {
            echo json_encode(['success' => false, 'message' => 'Booking ID, EMI number and due date are required.']);
            exit;
        }

        $result = $this->clientModel->saveEmiDueDate($bookingId, $emiNo, $dueDate, $notes, $userId, $applySubsequent);
        echo json_encode($result);
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
                $row['activity_date_formatted'] = !empty($row['activity_date']) ? date('M d, Y', strtotime($row['activity_date'])) : '';
                $row['next_follow_up_date_formatted'] = !empty($row['next_follow_up_date']) ? date('M d, Y', strtotime($row['next_follow_up_date'])) : '';
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
        $roleName = strtolower($_SESSION['user']['role'] ?? '');
        $isAccounts = in_array($roleId, [1, 3, 6, 30]);
        $isManagementUser = ($roleId === 1 || $roleId === 3 || strpos($roleName, 'management') !== false || strpos($roleName, 'admin') !== false);

        if (!$isAccounts && !$isManagementUser) {
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
        $roleName = strtolower($_SESSION['user']['role'] ?? '');
        $isAccounts = in_array($roleId, [1, 3, 6, 30]);
        $isManagementUser = ($roleId === 1 || $roleId === 3 || strpos($roleName, 'management') !== false || strpos($roleName, 'admin') !== false);

        if (!$isAccounts && !$isManagementUser) {
            setError("You do not have permission to view trash.");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        $trashedTransactions = $this->clientModel->getTrashedTransactions();

        $this->view("accounts/cashIn/trash-client-payment-list", [
            "current_route" => "trash-client-payment-list",
            "page_title" => "Trash Client Payment",
            "trashedTransactions" => $trashedTransactions,
            "isManagementUser" => $isManagementUser
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

    /**
     * Update payment date strictly for booking_money, registration, emi, emi_due_settlement, dev charges, etc.
     */
    public function updatePaymentDate()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isAjax = !empty($_POST['is_ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
                exit;
            }
            header("Location: {$GLOBALS['base_url']}/clients-cashin-view");
            exit;
        }

        $paymentId = $_POST['payment_id'] ?? null;
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $newDate   = trim($_POST['payment_date'] ?? '');
        $paymentType = trim($_POST['payment_type'] ?? '');

        $redirectUrl = !empty($_POST['redirect_to']) 
            ? "{$GLOBALS['base_url']}/" . ltrim($_POST['redirect_to'], '/')
            : ($bookingId > 0 ? "{$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}" : "{$GLOBALS['base_url']}/clients-cashin-view");

        if (!$bookingId || empty($newDate)) {
            $msg = "Invalid booking ID or payment date provided.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: {$redirectUrl}");
            exit;
        }

        // Validate date format YYYY-MM-DD
        $d = DateTime::createFromFormat('Y-m-d', $newDate);
        if (!$d || $d->format('Y-m-d') !== $newDate) {
            $msg = "Invalid date format. Please use a valid calendar date (YYYY-MM-DD).";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: {$redirectUrl}");
            exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 1);
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        // Permission: Accounts, Accounts Head, Accounts Executive, Management, Admin
        $canEditDate = in_array($userRoleId, [1, 3, 6, 20, 30, 32, 33, 36]) 
            || (function_exists('isAccountsHead') && isAccountsHead()) 
            || (function_exists('isAccountsExecutive') && isAccountsExecutive())
            || strpos($userRoleName, 'account') !== false 
            || strpos($userRoleName, 'admin') !== false
            || strpos($userRoleName, 'management') !== false;

        if (!$canEditDate) {
            $msg = "Permission denied: You do not have permission to edit payment dates.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: {$redirectUrl}");
            exit;
        }

        $allowedPaymentTypes = [
            'booking_money',
            'registration',
            'emi',
            'emi_due_settlement',
            'development_charge',
            'construction_fee',
            'shop_service_charge',
            'land_share',
            'mutation_cost',
            'others'
        ];

        if (!empty($paymentType) && !in_array($paymentType, $allowedPaymentTypes, true)) {
            $msg = "Permission denied: Payment type '{$paymentType}' cannot be edited.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: {$redirectUrl}");
            exit;
        }

        // Virtual agreement record handling: 'booking-{id}'
        if (!empty($paymentId) && strpos((string)$paymentId, 'booking-') !== false) {
            $stmt = $this->db->prepare("UPDATE lead_bookings SET booking_date = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('si', $newDate, $bookingId);
                $stmt->execute();
                $stmt->close();
                $formattedDate = date('d M, Y', strtotime($newDate));
                $successMsg = "Booking agreement date updated to {$formattedDate} successfully!";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $successMsg]);
                    exit;
                }
                setSuccess($successMsg);
            } else {
                $msg = "Failed to update booking date in database.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $msg]);
                    exit;
                }
                setError($msg);
            }
            header("Location: {$redirectUrl}");
            exit;
        }

        $numericPaymentId = (int)$paymentId;
        if ($numericPaymentId <= 0) {
            $msg = "Invalid payment reference ID.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: {$redirectUrl}");
            exit;
        }

        // Fetch existing payment to confirm type and booking_id
        $checkStmt = $this->db->prepare("
            SELECT lp.id, lp.booking_id, lp.payment_type, lp.emi_no, lp.parent_payment_id,
                   es.emi_no as schedule_emi_no
            FROM lead_payments lp
            LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
            WHERE lp.id = ? AND lp.booking_id = ?
        ");
        if (!$checkStmt) {
            $msg = "Database query error.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: {$redirectUrl}");
            exit;
        }

        $checkStmt->bind_param('ii', $numericPaymentId, $bookingId);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if (!$existing || !in_array($existing['payment_type'], $allowedPaymentTypes, true)) {
            $msg = "Transaction not found or not eligible for date modification.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
            header("Location: {$redirectUrl}");
            exit;
        }

        // Update payment_date in lead_payments
        $upStmt = $this->db->prepare("UPDATE lead_payments SET payment_date = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
        if ($upStmt) {
            $upStmt->bind_param('sii', $newDate, $userId, $numericPaymentId);
            $res = $upStmt->execute();
            $upStmt->close();

            if ($res) {
                // If it is booking_money, also sync booking_date in lead_bookings
                if ($existing['payment_type'] === 'booking_money') {
                    $bStmt = $this->db->prepare("UPDATE lead_bookings SET booking_date = ?, updated_at = NOW() WHERE id = ?");
                    if ($bStmt) {
                        $bStmt->bind_param('si', $newDate, $bookingId);
                        $bStmt->execute();
                        $bStmt->close();
                    }
                }

                // If it is an EMI or EMI Due Settlement, also sync emi_schedules
                if (in_array($existing['payment_type'], ['emi', 'emi_due_settlement'], true)) {
                    $sStmt = $this->db->prepare("UPDATE emi_schedules SET due_date = ? WHERE lead_payment_id = ? AND lead_booking_id = ?");
                    if ($sStmt) {
                        $sStmt->bind_param('sii', $newDate, $numericPaymentId, $bookingId);
                        $sStmt->execute();
                        $affected = $sStmt->affected_rows;
                        $sStmt->close();

                        // Fallback: If not matched by lead_payment_id, update by emi_no if available
                        $emiNo = !empty($existing['schedule_emi_no']) ? (int)$existing['schedule_emi_no'] : (int)($existing['emi_no'] ?? 0);
                        if ($affected === 0 && $emiNo > 0) {
                            $sStmt2 = $this->db->prepare("UPDATE emi_schedules SET due_date = ?, lead_payment_id = ? WHERE lead_booking_id = ? AND emi_no = ?");
                            if ($sStmt2) {
                                $sStmt2->bind_param('siii', $newDate, $numericPaymentId, $bookingId, $emiNo);
                                $sStmt2->execute();
                                $sStmt2->close();
                            }
                        }
                    }
                }

                // Friendly label
                $emiDisplayNo = !empty($existing['schedule_emi_no']) ? (int)$existing['schedule_emi_no'] : (!empty($existing['emi_no']) ? (int)$existing['emi_no'] : 0);
                if ($existing['payment_type'] === 'emi') {
                    if ($emiDisplayNo > 0) {
                        $suffix = 'th';
                        if (!in_array(($emiDisplayNo % 100), [11, 12, 13], true)) {
                            switch ($emiDisplayNo % 10) {
                                case 1: $suffix = 'st'; break;
                                case 2: $suffix = 'nd'; break;
                                case 3: $suffix = 'rd'; break;
                            }
                        }
                        $typeLabel = $emiDisplayNo . $suffix . ' Installment';
                    } else {
                        $typeLabel = 'EMI';
                    }
                } elseif ($existing['payment_type'] === 'emi_due_settlement') {
                    $typeLabel = 'EMI Due Settlement';
                } elseif ($existing['payment_type'] === 'booking_money') {
                    $typeLabel = 'Booking Money';
                } elseif ($existing['payment_type'] === 'registration') {
                    $typeLabel = 'Registration Money';
                } elseif ($existing['payment_type'] === 'development_charge') {
                    $typeLabel = 'Development Charge';
                } elseif ($existing['payment_type'] === 'construction_fee') {
                    $typeLabel = 'Construction Fee';
                } elseif ($existing['payment_type'] === 'shop_service_charge') {
                    $typeLabel = 'Shop Service Charge';
                } else {
                    $typeLabel = 'Payment';
                }

                $formattedDate = date('d M, Y', strtotime($newDate));
                $successMsg = "{$typeLabel} payment date updated to {$formattedDate} successfully!";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $successMsg]);
                    exit;
                }
                setSuccess($successMsg);
            } else {
                $msg = "Failed to update payment date in database.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $msg]);
                    exit;
                }
                setError($msg);
            }
        } else {
            $msg = "Database query preparation failed.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            setError($msg);
        }

        header("Location: {$redirectUrl}");
        exit;
    }

    /**
     * Client-wise Due Tracking & Payment Management
     */
    public function dueTracking()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $userPriRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        $allowedRoles = [1, 3, 6, 7, 30, 33, 34, 35, 36, 37, 39];
        $isAuthorized = in_array($userRoleId, $allowedRoles, true)
            || in_array($userSecRoleId, $allowedRoles, true)
            || in_array($userPriRoleId, $allowedRoles, true)
            || $userId === 94
            || strpos($roleName, 'account') !== false
            || strpos($roleName, 'client experience') !== false
            || strpos($roleName, 'clients experiment') !== false
            || strpos($roleName, 'communicat') !== false
            || strpos($roleName, 'management') !== false
            || strpos($roleName, 'admin') !== false
            || hasPermission("CED Manage")
            || hasPermission("Cash In Verify")
            || (function_exists('isClientsCommunicated') && isClientsCommunicated())
            || (function_exists('isAccountsHead') && isAccountsHead())
            || (function_exists('isAccountsExecutive') && isAccountsExecutive());

        if (!$isAuthorized) {
            setError("You do not have permission to access Client-wise Due Tracking.");
            header("Location: {$GLOBALS['base_url']}/home");
            exit;
        }

        // Handle AJAX quick detail modal for a specific booking
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'client_detail') {
            $bookingId = (int)($_GET['booking_id'] ?? 0);
            $details = $bookingId > 0 ? $this->clientModel->getClientByBookingId($bookingId) : null;
            if (!empty($details['is_shop']) && (int)$details['is_shop'] === 1) {
                require_once dirname(__DIR__) . '/models/AccountShopClient.php';
                $shopModel = new AccountShopClient($this->db);
                $details = $shopModel->getClientByBookingId($bookingId);
                $summary = $shopModel->getPaymentSummaryByBookingId($bookingId);
            } else {
                $summary = $bookingId > 0 ? $this->clientModel->getPaymentSummaryByBookingId($bookingId) : null;
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => !empty($summary),
                'summary' => $summary,
                'details' => $details
            ]);
            exit;
        }

        // Handle AJAX registered clients list for a specific project (Land Share Complete)
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'registered_clients') {
            $projectId = (int)($_GET['project_id'] ?? 0);
            $registeredList = $projectId > 0 ? $this->clientModel->getRegisteredClientsByProject($projectId) : [];
            header('Content-Type: application/json');
            echo json_encode([
                'success'    => true,
                'project_id' => $projectId,
                'count'      => count($registeredList),
                'clients'    => $registeredList
            ]);
            exit;
        }

        // Handle AJAX EMI Analytics summary
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'emi_analytics_summary') {
            $modalPrj = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
            $mAssigned = [];
            $effectiveModalPrj = $this->getEffectiveCedProjectFilter($modalPrj, $mAssigned);
            $summary = $this->clientModel->getEmiAnalyticsSummary($effectiveModalPrj);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'summary' => $summary
            ]);
            exit;
        }

        // Handle AJAX EMI Analytics clients breakdown list
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'emi_analytics_clients') {
            $modalPrj = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
            $mAssigned = [];
            $effectiveModalPrj = $this->getEffectiveCedProjectFilter($modalPrj, $mAssigned);
            $category = trim($_GET['category'] ?? 'all');
            if (!in_array($category, ['all', 'regular', 'advance', 'due'], true)) {
                $category = 'all';
            }
            $search = trim($_GET['search'] ?? '');
            $clients = $this->clientModel->getEmiAnalyticsClientsList($category, $effectiveModalPrj, $search ?: null, 500);
            $summary = $this->clientModel->getEmiAnalyticsSummary($effectiveModalPrj);
            $html = $this->renderEmiAnalyticsModalRowsHtml($clients, $category);
            header('Content-Type: application/json');
            echo json_encode([
                'success'  => true,
                'category' => $category,
                'count'    => count($clients),
                'summary'  => $summary,
                'html'     => $html
            ]);
            exit;
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_GET['ajax']) && $_GET['ajax'] == '1');

        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
        $assignedProjectIds = [];
        $effectiveProjectId = $this->getEffectiveCedProjectFilter($selectedProjectId, $assignedProjectIds);

        $dueType = trim($_GET['due_type'] ?? 'all');
        if (!in_array($dueType, ['all', 'land_share_due', 'land_share_cleared', 'registered', 'dev_charge_due', 'shop_service_due', 'emi_due', 'overall_due', 'cleared'], true)) {
            $dueType = 'all';
        }

        $search = trim($_GET['search'] ?? '');
        $dateType = trim($_GET['date_type'] ?? '');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');
        if (!in_array($dateType, ['booking_date', 'last_payment_date'])) {
            $dateType = null;
        }
        if (empty($startDate)) $startDate = null;
        if (empty($endDate)) $endDate = null;

        $sortBy = trim($_GET['sort_by'] ?? 'booking_id');
        $sortOrder = strtoupper(trim($_GET['sort_order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $rawLimit = strtolower(trim($_GET['limit'] ?? '20'));
        $isAll = ($rawLimit === 'all' || (int)$rawLimit >= 5000);
        $page = max(1, (int)($_GET['page'] ?? 1));

        if ($isAll) {
            $limit = null;
            $page = 1;
            $offset = 0;
        } else {
            $limit = min(500, max(5, (int)$rawLimit));
            $offset = ($page - 1) * $limit;
        }

        $filters = [
            'project_id' => $effectiveProjectId,
            'due_type'   => $dueType,
            'search'     => $search,
            'date_type'  => $dateType,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'sort_by'    => $sortBy,
            'sort_order' => $sortOrder
        ];

        $totalClients = $this->clientModel->getClientWiseDueCount($filters);
        $totalPages = ($isAll || $limit === null || $limit <= 0) ? 1 : (($totalClients > 0) ? (int)ceil($totalClients / $limit) : 1);
        if (!$isAll && $page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
        }

        $clients = $this->clientModel->getClientWiseDueList($filters, $limit, $offset);
        $stats = $this->clientModel->getClientDueTrackingStats($effectiveProjectId);
        $emiAnalytics = $this->clientModel->getEmiAnalyticsSummary($effectiveProjectId);

        if ($isAjax) {
            $tableHtml = $this->renderDueTrackingRowsHtml($clients);
            $footerHtml = $this->renderDueTrackingFooterHtml($clients, $stats, $isAll);
            $paginationHtml = $isAll ? '' : $this->generateAjaxPagination($page, $totalPages, $totalClients, $limit, 'loadDueTrackingPage');
            $startEntry = $totalClients > 0 ? (($page - 1) * ($limit ?: $totalClients)) + 1 : 0;
            $endEntry = $isAll ? $totalClients : min($totalClients, $page * $limit);
            $infoText = $isAll ? "Showing all " . number_format($totalClients) . " client bookings" : "Showing {$startEntry} to {$endEntry} of " . number_format($totalClients) . " client bookings";

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'page' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $totalClients,
                'limit' => $isAll ? 'all' : $limit,
                'table_html' => $tableHtml,
                'footer_html' => $footerHtml,
                'pagination_html' => $paginationHtml,
                'info_text' => $infoText,
                'stats' => $stats,
                'emi_analytics' => $emiAnalytics
            ]);
            exit;
        }

        // Get projects list
        $projectModel = new Project($this->db);
        $projects = $projectModel->getAll();
        if (empty($projects)) {
            $projects = $this->clientModel->getProjectsForCED();
        }
        if (!empty($assignedProjectIds)) {
            $projects = array_values(array_filter($projects, fn($p) => in_array((int)$p['id'], $assignedProjectIds, true)));
        }

        // Get Project-wise Due Breakdown
        $projectBreakdown = $this->clientModel->getProjectWiseDueBreakdown(null, !empty($assignedProjectIds) ? $assignedProjectIds : null);

        $selectedProjectName = '';
        if ($selectedProjectId) {
            foreach ($projects as $prj) {
                if ((int)$prj['id'] === $selectedProjectId) {
                    $selectedProjectName = $prj['name'];
                    break;
                }
            }
        }

        $this->view("accounts/cashIn/client-due-tracking", [
            "current_route"       => "client-wise-due-tracking",
            "page_title"          => "Client-wise Due Tracking & Payment Management",
            "clients"             => $clients,
            "totalClients"        => $totalClients,
            "totalPages"          => $totalPages,
            "currentPage"         => $page,
            "limit"               => $isAll ? 'all' : $limit,
            "search"              => $search,
            "dueType"             => $dueType,
            "stats"               => $stats,
            "emiAnalytics"        => $emiAnalytics,
            "projects"            => $projects,
            "assignedProjectIds"  => $assignedProjectIds,
            "projectBreakdown"    => $projectBreakdown,
            "selectedProjectId"   => $selectedProjectId,
            "selectedProjectName" => $selectedProjectName,
            "dateType"            => $dateType,
            "startDate"           => $startDate,
            "endDate"             => $endDate,
            "sortBy"              => $sortBy,
            "sortOrder"           => $sortOrder
        ]);
    }

    /**
     * Render Table Rows HTML for Client-wise Due Tracking Table
     *
     * @param array $clients
     * @return string
     */
        public function renderDueTrackingRowsHtml(array $clients): string
    {
        if (empty($clients)) {
            return '<tr><td colspan="11" class="text-center py-5 text-muted"><i class="fa-solid fa-folder-open fa-3x mb-3 text-secondary opacity-50 d-block"></i>No client due records found matching the criteria.</td></tr>';
        }

        // Batch query followup counts & live accrued penalties for these bookings
        $bookingIds = array_filter(array_column($clients, 'booking_id'));
        $followupCounts = [];
        $penaltiesByBooking = !empty($bookingIds) ? $this->clientModel->computeLiveAccruedPenaltiesByBookingIds($bookingIds) : [];
        if (!empty($bookingIds)) {
            $bIdsStr = implode(',', array_map('intval', $bookingIds));
            $fcRes = $this->db->query("SELECT booking_id, COUNT(*) as cnt FROM emi_followups WHERE booking_id IN ($bIdsStr) GROUP BY booking_id");
            if ($fcRes) {
                while ($fcRow = $fcRes->fetch_assoc()) {
                    $followupCounts[(int)$fcRow['booking_id']] = (int)$fcRow['cnt'];
                }
            }
        }

        $html = '';
        foreach ($clients as $client) {
            $bookingId = (int)$client['booking_id'];
            $clientName = htmlspecialchars($client['client_name'] ?? 'N/A');
            $phone = htmlspecialchars($client['phone'] ?? '');
            $email = htmlspecialchars($client['email'] ?? '');
            $salesAgent = htmlspecialchars($client['sales_agent_name'] ?? 'Not Assigned');
            $projectName = htmlspecialchars($client['project_name'] ?? 'N/A');
            $unitName = htmlspecialchars($client['unit_name'] ?? 'N/A');
            $floorNo = htmlspecialchars($client['floor_no'] ?? '');
            $isShop = !empty($client['is_shop']) && (int)$client['is_shop'] === 1;

            $totalPrice = (float)($client['total_price'] ?? 0);
            $discount = (float)($client['discount_amount'] ?? 0);
            $totalPaid = (float)($client['total_paid'] ?? 0);
            $overallDue = (float)($client['overall_due'] ?? 0);

            $landTarget = (float)($client['total_land_share'] ?? 0);
            $landPaid = (float)($client['land_share_paid'] ?? 0);
            $landDue = (float)($client['land_share_due'] ?? 0);

            $devTarget = (float)($client['development_charges'] ?? 0);
            $devPaid = (float)($client['dev_charge_paid'] ?? 0);
            $devDue = (float)($client['dev_charge_due'] ?? 0);

            $shopTarget = (float)($client['shop_service_charge_target'] ?? 0);
            $shopPaid = (float)($client['shop_service_charge_paid'] ?? 0);
            $shopDue = (float)($client['shop_service_charge_due'] ?? 0);

            $consFee = (float)($client['construction_fee'] ?? 0);
            $emiPaid = (float)($client['emi_paid'] ?? 0);
            $remEmiDue = (float)($client['remaining_emi_due'] ?? 0);
            $overdueEmi = (float)($client['overdue_emi_amount'] ?? 0);

            $completedEmi = (int)($client['completed_emi_count'] ?? 0);
            $totalEmi = (int)($client['total_emi_no'] ?? 0);
            if ($totalEmi <= 0) $totalEmi = 36;
            if ($completedEmi > $totalEmi) $totalEmi = $completedEmi;

            $bookingDate = !empty($client['booking_date']) ? date('d M, Y', strtotime($client['booking_date'])) : 'N/A';
            $lastPaymentDate = !empty($client['last_payment_date']) ? date('d M, Y', strtotime($client['last_payment_date'])) : null;

            $baseUrl = $GLOBALS['base_url'] ?? '';
            $ledgerUrl = "{$baseUrl}/clients-cash-in-history-view/{$bookingId}";

            $html .= '<tr class="align-middle">';
            
            // Col 1: ID & Date
            $html .= '<td class="text-center">';
            $html .= '<a href="' . $ledgerUrl . '" class="fw-bold text-primary text-decoration-none">#' . $bookingId . '</a>';
            $html .= '<div class="extra-small text-muted" style="font-size:10px;">' . $bookingDate . '</div>';
            $html .= '</td>';

            // Col 2: Client Info
            $html .= '<td>';
            $html .= '<div class="d-flex align-items-center">';
            $html .= '<div class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold no-print" style="width:30px; height:30px; font-size:11px; flex-shrink:0;">' . mb_substr($clientName, 0, 1) . '</div>';
            $html .= '<div>';
            $html .= '<a href="' . $ledgerUrl . '" class="fw-bold text-dark text-decoration-none d-block fs-13">' . $clientName . '</a>';
            if (!empty($phone)) {
                $html .= '<div class="extra-small text-muted" style="font-size:11px;"><i class="fa-solid fa-phone me-1 text-secondary"></i>' . $phone . '</div>';
            }
            if (!empty($salesAgent)) {
                $html .= '<div class="extra-small text-muted" style="font-size:10px;" title="Assigned Sales Agent"><i class="fa-solid fa-user-tie me-1 text-info"></i>' . $salesAgent . '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</td>';

            // Col 3: Project & Unit
            $html .= '<td>';
            $html .= '<span class="badge bg-light text-primary border border-primary-subtle mb-1 d-inline-block text-truncate" style="max-width: 155px; font-size:11px;" title="' . $projectName . '">' . $projectName . '</span>';
            $html .= '<div class="fw-semibold text-dark extra-small">';
            $html .= '<i class="fa-solid fa-building me-1 text-muted"></i>' . $unitName . (!empty($floorNo) ? ' (Fl ' . $floorNo . ')' : '');
            if ($isShop) {
                $html .= ' <span class="badge bg-warning text-dark extra-small">Shop</span>';
            }
            $html .= '</div>';
            $html .= '</td>';

            // Col 4: Flat Value (Tk.) - Clean, right-aligned, no "Tk."
            $html .= '<td class="text-end" style="font-variant-numeric: tabular-nums;">';
            $html .= '<div class="fw-bold text-dark fs-13">' . number_format($totalPrice) . '</div>';
            if ($discount > 0) {
                $html .= '<div class="extra-small text-success" style="font-size:10px;" title="Discount"><i class="fa-solid fa-tag me-1"></i>Disc: ' . number_format($discount) . '</div>';
            }
            $html .= '<div class="extra-small text-muted" style="font-size:10px;">Paid: ' . number_format($totalPaid) . '</div>';
            $html .= '</td>';

            // Col 5: Land Share Due (Tk.) - 3 Items: Due status, Target, Paid (No "Tk.", no heavy box)
            $html .= '<td class="text-end" style="font-variant-numeric: tabular-nums;">';
            if ($landDue <= 0) {
                $html .= '<div class="text-success fw-semibold fs-12"><i class="fa-solid fa-check me-1"></i>Cleared</div>';
            } else {
                $html .= '<div class="fw-bold text-danger fs-13">' . number_format($landDue) . '</div>';
            }
            $html .= '<div class="extra-small text-muted" style="font-size:10px;">';
            $html .= 'Target: ' . number_format($landTarget) . '<br>Paid: ' . number_format($landPaid);
            $html .= '</div>';
            $html .= '</td>';

            // Col 6: Dev Charge Due (Tk.) - 3 Items: Due status, Target, Paid (No "Tk.", no heavy box)
            $html .= '<td class="text-end" style="font-variant-numeric: tabular-nums;">';
            if ($devDue <= 0) {
                $html .= '<div class="text-success fw-semibold fs-12"><i class="fa-solid fa-check me-1"></i>Cleared</div>';
            } else {
                $html .= '<div class="fw-bold text-danger fs-13">' . number_format($devDue) . '</div>';
            }
            $html .= '<div class="extra-small text-muted" style="font-size:10px;">';
            $html .= 'Target: ' . number_format($devTarget) . '<br>Paid: ' . number_format($devPaid);
            $html .= '</div>';
            $html .= '</td>';

            // Col 7: Shop Svc Due (Tk.) - Clean, right-aligned (No "Tk.")
            $html .= '<td class="text-end" style="font-variant-numeric: tabular-nums;">';
            if (!$isShop) {
                $html .= '<span class="text-muted extra-small" style="font-size:11px;">-</span>';
            } else {
                if ($shopDue <= 0) {
                    $html .= '<div class="text-success fw-semibold fs-12"><i class="fa-solid fa-check me-1"></i>Cleared</div>';
                } else {
                    $html .= '<div class="fw-bold fs-13" style="color:#4f46e5;">' . number_format($shopDue) . '</div>';
                }
                $html .= '<div class="extra-small text-muted" style="font-size:10px;">';
                $html .= 'Target: ' . number_format($shopTarget) . '<br>Paid: ' . number_format($shopPaid);
                $html .= '</div>';
            }
            $html .= '</td>';

            // Col 8: Due EMI (Tk.) - Clean, right-aligned (No "Tk.")
            $html .= '<td class="text-end" style="font-variant-numeric: tabular-nums;">';
            if ($overdueEmi > 0) {
                $html .= '<div class="fw-bold text-danger fs-13">' . number_format($overdueEmi) . '</div>';
                $html .= '<div class="extra-small text-danger fw-semibold" style="font-size:10px;"><i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue</div>';
            } elseif ($remEmiDue <= 0 && $completedEmi > 0) {
                $html .= '<div class="text-success fw-semibold fs-12"><i class="fa-solid fa-check me-1"></i>Cleared</div>';
            } else {
                $html .= '<div class="text-secondary fw-semibold fs-12">Current</div>';
            }
            $html .= '<div class="extra-small text-muted" style="font-size:10px;">Rem: ' . number_format($remEmiDue) . '</div>';
            $pendingEmiCount = (int)($client['pending_emi_payment_count'] ?? 0);
            if ($pendingEmiCount > 0) {
                $html .= '<div class="extra-small text-warning" style="font-size:9px;" title="' . $pendingEmiCount . ' pending EMI payment(s)"><i class="fa-solid fa-clock me-1"></i>+' . $pendingEmiCount . ' Pending</div>';
            }
            $html .= '</td>';

            // Col 9: Current Penalty (Tk.) - Live Accrued 5% Overdue Penalty
            $currentPenalty = (float)($penaltiesByBooking[$bookingId] ?? 0.0);
            $html .= '<td class="text-end" style="font-variant-numeric: tabular-nums;">';
            if ($currentPenalty > 0) {
                $html .= '<div class="fw-bold fs-13" style="color:#b45309;">' . number_format($currentPenalty, 2) . '</div>';
                $html .= '<div class="extra-small" style="font-size:10px; color:#b45309;"><i class="fa-solid fa-triangle-exclamation me-1"></i>5% Accrued</div>';
            } else {
                $html .= '<span class="text-muted extra-small" style="font-size:11px;">-</span>';
            }
            $html .= '</td>';

            // Col 10: Overall Due (Tk.) - Includes live accrued penalty
            $overallDueWithPenalty = round($overallDue + $currentPenalty, 2);
            $html .= '<td class="text-end" style="font-variant-numeric: tabular-nums;">';
            if ($overallDueWithPenalty <= 0) {
                $html .= '<div class="text-success fw-bold fs-13"><i class="fa-solid fa-check-double me-1"></i>Cleared</div>';
            } else {
                $html .= '<div class="fw-bold text-danger fs-14">' . number_format($overallDueWithPenalty, $currentPenalty > 0 ? 2 : 0) . '</div>';
            }
            if ($lastPaymentDate) {
                $html .= '<div class="extra-small text-muted" style="font-size:10px;" title="Last Payment Date"><i class="fa-solid fa-clock-rotate-left me-1"></i>' . $lastPaymentDate . '</div>';
            }
            $html .= '</td>';

            // Col 10: Action (Screen Only, hidden in print)
            $html .= '<td class="text-center no-print">';
            $html .= '<div class="d-flex align-items-center justify-content-center gap-1">';
            $html .= '<a href="' . $ledgerUrl . '" class="btn btn-outline-primary btn-sm py-1 px-2" style="font-size:11px;" title="View Full Client Ledger & Cash In History"><i class="fa-solid fa-receipt me-1"></i>Ledger</a>';
            $html .= '<button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size:11px;" onclick="openClientDueBreakdownModal(' . $bookingId . ')" title="Quick Due Breakdown Preview"><i class="fa-solid fa-eye"></i></button>';

            $fCount = $followupCounts[$bookingId] ?? 0;
            $escapedName = htmlspecialchars(trim(preg_replace('/\s+/', ' ', $client['client_name'] ?? 'Client')), ENT_QUOTES);
            $totalEmiVal = (int)($client['total_emi_no'] ?? 36);
            $leadIdVal = (int)($client['lead_id'] ?? 0) ?: 'null';
            $html .= '<button type="button" onclick="openClientEmiFollowupModal(' . $bookingId . ', \'' . $escapedName . '\', ' . $totalEmiVal . ', ' . $leadIdVal . ')" class="btn btn-sm btn-outline-primary py-1 px-2 text-nowrap" style="font-size:11px;" title="Add / View Follow Up Notes">';
            $html .= '<i class="fa-light fa-comment-dots me-1"></i>Follow Up';
            $html .= '<span class="badge ' . ($fCount > 0 ? 'bg-primary' : 'bg-secondary') . ' rounded-pill ms-1 followup-count-badge" data-booking-id="' . $bookingId . '" style="font-size:10px;">' . $fCount . '</span>';
            $html .= '</button>';
            $html .= '</div>';
            $html .= '</td>';

            $html .= '</tr>';
        }

        return $html;
    }


    /**
     * Render Footer HTML for Client-wise Due Tracking Table (Summary Total Calculation Row)
     *
     * @param array $clients
     * @param array $stats
     * @param bool $isAll
     * @return string
     */
    public function renderDueTrackingFooterHtml(array $clients, array $stats = [], bool $isAll = false): string
    {
        if (empty($clients)) {
            return '';
        }

        $recordCount = count($clients);
        $totFlatValue = 0;
        $totDiscount = 0;
        $totPaid = 0;
        $totLandDue = 0;
        $totLandTarget = 0;
        $totLandPaid = 0;
        $totDevDue = 0;
        $totDevTarget = 0;
        $totDevPaid = 0;
        $totShopDue = 0;
        $totShopTarget = 0;
        $totShopPaid = 0;
        $totOverdueEmi = 0;
        $totRemEmiDue = 0;
        $totCurrentPenalty = 0;
        $totOverallDue = 0;

        $footerBookingIds = array_filter(array_column($clients, 'booking_id'));
        $footerPenalties  = !empty($footerBookingIds) ? $this->clientModel->computeLiveAccruedPenaltiesByBookingIds($footerBookingIds) : [];

        foreach ($clients as $c) {
            $bId = (int)($c['booking_id'] ?? 0);
            $rowPen = (float)($footerPenalties[$bId] ?? 0.0);
            $totFlatValue += (float)($c['total_price'] ?? 0);
            $totDiscount  += (float)($c['discount_amount'] ?? 0);
            $totPaid      += (float)($c['total_paid'] ?? 0);

            $totLandDue   += (float)($c['land_share_due'] ?? 0);
            $totLandTarget+= (float)($c['total_land_share'] ?? 0);
            $totLandPaid  += (float)($c['land_share_paid'] ?? 0);

            $totDevDue    += (float)($c['dev_charge_due'] ?? 0);
            $totDevTarget += (float)($c['development_charges'] ?? 0);
            $totDevPaid   += (float)($c['dev_charge_paid'] ?? 0);

            $totShopDue   += (float)($c['shop_service_charge_due'] ?? 0);
            $totShopTarget+= (float)($c['shop_service_charge_target'] ?? 0);
            $totShopPaid  += (float)($c['shop_service_charge_paid'] ?? 0);

            $totOverdueEmi+= (float)($c['overdue_emi_amount'] ?? 0);
            $totRemEmiDue += (float)($c['remaining_emi_due'] ?? 0);
            $totCurrentPenalty += $rowPen;

            $totOverallDue+= (float)($c['overall_due'] ?? 0) + $rowPen;
        }

        $label = $isAll ? 'GRAND TOTAL' : 'TOTAL';

        $html = '<tr class="align-middle fw-bold" style="background-color: #f1f5f9; border-top: 2px solid #64748b; border-bottom: 2px solid #64748b;">';

        // Col 1: Label
        $html .= '<td class="text-center fw-bold fs-12 text-uppercase" style="color: #0f172a;">' . $label . '</td>';

        // Col 2: Bookings count
        $html .= '<td class="fw-bold fs-12 text-dark">';
        $html .= number_format($recordCount) . ' Bookings';
        $html .= '</td>';

        // Col 3: Summary Note
        $html .= '<td class="fw-semibold text-muted extra-small" style="font-size: 10px;">';
        $html .= 'All Units';
        $html .= '</td>';

        // Col 4: Flat Value (Tk.)
        $html .= '<td class="text-end fw-bold" style="font-variant-numeric: tabular-nums;">';
        $html .= '<div class="text-dark fs-13">' . number_format($totFlatValue) . '</div>';
        if ($totDiscount > 0) {
            $html .= '<div class="extra-small text-success" style="font-size: 10px;">Disc: ' . number_format($totDiscount) . '</div>';
        }
        $html .= '<div class="extra-small text-muted" style="font-size: 10px;">Paid: ' . number_format($totPaid) . '</div>';
        $html .= '</td>';

        // Col 5: Land Share Due (Tk.)
        $html .= '<td class="text-end fw-bold" style="font-variant-numeric: tabular-nums;">';
        $html .= '<div class="' . ($totLandDue > 0 ? 'text-danger' : 'text-success') . ' fs-13">' . number_format($totLandDue) . '</div>';
        $html .= '<div class="extra-small text-muted" style="font-size: 10px;">Target: ' . number_format($totLandTarget) . '<br>Paid: ' . number_format($totLandPaid) . '</div>';
        $html .= '</td>';

        // Col 6: Dev Charge Due (Tk.)
        $html .= '<td class="text-end fw-bold" style="font-variant-numeric: tabular-nums;">';
        $html .= '<div class="' . ($totDevDue > 0 ? 'text-danger' : 'text-success') . ' fs-13">' . number_format($totDevDue) . '</div>';
        $html .= '<div class="extra-small text-muted" style="font-size: 10px;">Target: ' . number_format($totDevTarget) . '<br>Paid: ' . number_format($totDevPaid) . '</div>';
        $html .= '</td>';

        // Col 7: Shop Svc Due (Tk.)
        $html .= '<td class="text-end fw-bold" style="font-variant-numeric: tabular-nums;">';
        $html .= '<div class="fs-13" style="color: #4f46e5;">' . number_format($totShopDue) . '</div>';
        $html .= '<div class="extra-small text-muted" style="font-size: 10px;">Target: ' . number_format($totShopTarget) . '<br>Paid: ' . number_format($totShopPaid) . '</div>';
        $html .= '</td>';

        // Col 8: Due EMI (Tk.)
        $html .= '<td class="text-end fw-bold" style="font-variant-numeric: tabular-nums;">';
        $html .= '<div class="' . ($totOverdueEmi > 0 ? 'text-danger' : 'text-success') . ' fs-13">' . number_format($totOverdueEmi) . '</div>';
        $html .= '<div class="extra-small text-muted" style="font-size: 10px;">Rem: ' . number_format($totRemEmiDue) . '</div>';
        $html .= '</td>';

        // Col 9: Current Penalty (Tk.)
        $html .= '<td class="text-end fw-bold" style="font-variant-numeric: tabular-nums;">';
        $html .= '<div class="fs-13" style="color: ' . ($totCurrentPenalty > 0 ? '#b45309' : '#16a34a') . ';">' . number_format($totCurrentPenalty, 2) . '</div>';
        $html .= '</td>';

        // Col 10: Overall Due (Tk.)
        $html .= '<td class="text-end fw-bold" style="font-variant-numeric: tabular-nums;">';
        $html .= '<div class="' . ($totOverallDue > 0 ? 'text-danger' : 'text-success') . ' fs-14">' . number_format($totOverallDue, $totCurrentPenalty > 0 ? 2 : 0) . '</div>';
        $html .= '</td>';

        // Col 11: Action (Hidden in print)
        $html .= '<td class="no-print"></td>';

        $html .= '</tr>';

        return $html;
    }

    public function renderEmiAnalyticsModalRowsHtml(array $clients, string $category = 'all'): string
    {
        if (empty($clients)) {
            return '<tr><td colspan="9" class="text-center py-5 text-muted"><i class="fa-solid fa-folder-open fa-3x mb-3 text-secondary opacity-50 d-block"></i>No client records found matching the criteria.</td></tr>';
        }

        // Batch query followup counts for these bookings
        $bookingIds = array_filter(array_column($clients, 'booking_id'));
        $followupCounts = [];
        if (!empty($bookingIds)) {
            $bIdsStr = implode(',', array_map('intval', $bookingIds));
            $fcRes = $this->db->query("SELECT booking_id, COUNT(*) as cnt FROM emi_followups WHERE booking_id IN ($bIdsStr) GROUP BY booking_id");
            if ($fcRes) {
                while ($fcRow = $fcRes->fetch_assoc()) {
                    $followupCounts[(int)$fcRow['booking_id']] = (int)$fcRow['cnt'];
                }
            }
        }

        $html = '';
        foreach ($clients as $client) {
            $bookingId = (int)$client['booking_id'];
            $clientName = htmlspecialchars($client['client_name'] ?? 'N/A');
            $phone = htmlspecialchars($client['phone'] ?? '');
            $salesAgent = htmlspecialchars($client['sales_agent_name'] ?? 'Not Assigned');
            $projectName = htmlspecialchars($client['project_name'] ?? 'N/A');
            $unitName = htmlspecialchars($client['unit_name'] ?? 'N/A');
            $floorNo = htmlspecialchars($client['floor_no'] ?? '');
            $isShop = !empty($client['is_shop']) && (int)$client['is_shop'] === 1;

            $totalEmiPaid = (float)($client['total_emi_paid'] ?? 0);
            $regularEmiPaid = (float)($client['regular_emi_paid'] ?? 0);
            $advanceEmiPaid = (float)($client['advance_emi_paid'] ?? 0);
            $overdueEmiAmount = (float)($client['overdue_emi_amount'] ?? 0);
            $lastPaidAmount = (float)($client['last_paid_amount'] ?? 0);

            $maxPaidEmiNo = (int)($client['max_paid_emi_no'] ?? 0);
            $totalEmiNo = (int)($client['total_emi_no'] ?? 36);
            if ($totalEmiNo <= 0) $totalEmiNo = 36;
            if ($maxPaidEmiNo > $totalEmiNo) $totalEmiNo = $maxPaidEmiNo;

            $maxRegularDueDate = !empty($client['max_regular_paid_due_date']) ? date('M Y', strtotime($client['max_regular_paid_due_date'])) : null;
            $maxRegularEmiNo = (int)($client['max_regular_paid_emi_no'] ?? 0);
            $maxAdvanceDueDate = !empty($client['max_advance_due_date']) ? date('M Y', strtotime($client['max_advance_due_date'])) : null;
            $advanceEmiNumbers = trim($client['advance_emi_numbers'] ?? '');
            $advanceEmiCount = (int)($client['advance_emi_count'] ?? 0);

            // Format advance EMI display badge text
            $advanceEmiBadgeText = '';
            if ($advanceEmiPaid > 0) {
                if (!empty($advanceEmiNumbers)) {
                    $advNos = array_unique(array_filter(array_map('intval', explode(',', $advanceEmiNumbers))));
                    sort($advNos);
                    $advCount = count($advNos);
                    $isConsecutive = true;
                    for ($k = 0; $k < $advCount - 1; $k++) {
                        if ($advNos[$k + 1] !== $advNos[$k] + 1) {
                            $isConsecutive = false;
                            break;
                        }
                    }
                    if ($advCount === 1) {
                        $advanceEmiBadgeText = "1 EMI (#{$advNos[0]})";
                    } elseif ($isConsecutive && $advCount > 3) {
                        $advanceEmiBadgeText = "{$advCount} EMIs (#{$advNos[0]} - #{$advNos[$advCount - 1]})";
                    } else {
                        $advanceEmiBadgeText = "{$advCount} EMIs (#" . implode(', #', $advNos) . ")";
                    }
                } elseif ($advanceEmiCount > 0) {
                    $advanceEmiBadgeText = "{$advanceEmiCount} Advance " . ($advanceEmiCount > 1 ? 'EMIs' : 'EMI');
                } else {
                    $advanceEmiBadgeText = 'Advance Paid';
                }
            }

            $bookingDate = !empty($client['booking_date']) ? date('d M, Y', strtotime($client['booking_date'])) : 'N/A';
            $lastPaymentDate = !empty($client['last_payment_date']) ? date('d M, Y', strtotime($client['last_payment_date'])) : 'N/A';
            $maxPaidDueDate = !empty($client['max_paid_due_date']) ? date('M Y', strtotime($client['max_paid_due_date'])) : null;
            $baseUrl = $GLOBALS['base_url'] ?? '';
            $ledgerUrl = "{$baseUrl}/clients-cash-in-history-view/{$bookingId}";

            $html .= '<tr class="align-middle">';

            // Col 1: ID & Date
            $html .= '<td class="text-center">';
            $html .= '<a href="' . $ledgerUrl . '" class="fw-bold text-primary text-decoration-none" target="_blank">#' . $bookingId . '</a>';
            $html .= '<div class="extra-small text-muted" style="font-size:10px;">' . $bookingDate . '</div>';
            $html .= '</td>';

            // Col 2: Client Info
            $html .= '<td>';
            $html .= '<div class="d-flex align-items-center">';
            $html .= '<div class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold" style="width:32px; height:32px; font-size:11px;">' . mb_substr($clientName, 0, 1) . '</div>';
            $html .= '<div>';
            $html .= '<a href="' . $ledgerUrl . '" class="fw-bold text-dark text-decoration-none d-block" target="_blank">' . $clientName . '</a>';
            if (!empty($phone)) {
                $html .= '<a href="tel:' . $phone . '" class="extra-small text-muted text-decoration-none me-2" style="font-size:11px;"><i class="fa-solid fa-phone me-1 text-secondary"></i>' . $phone . '</a>';
            }
            if (!empty($salesAgent)) {
                $html .= '<span class="extra-small text-muted d-block" style="font-size:10px;"><i class="fa-solid fa-user-tie me-1 text-info"></i>' . $salesAgent . '</span>';
            }
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</td>';

            // Col 3: Project & Unit
            $html .= '<td>';
            $html .= '<span class="badge bg-light text-primary border border-primary-subtle mb-1 d-inline-block text-truncate" style="max-width: 150px;" title="' . $projectName . '">' . $projectName . '</span>';
            $html .= '<div class="fw-semibold text-dark extra-small" style="font-size:11px;">';
            $html .= '<i class="fa-solid fa-building me-1 text-muted"></i>' . $unitName . (!empty($floorNo) ? ' (Fl ' . $floorNo . ')' : '');
            if ($isShop) {
                $html .= ' <span class="badge bg-warning text-dark extra-small">Shop</span>';
            }
            $html .= '</div>';
            $html .= '</td>';

            // Col 4: Paid Up To (Month & Installment)
            $html .= '<td>';
            if ($category === 'advance' && $maxAdvanceDueDate) {
                $html .= '<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold fs-12 px-2 py-1"><i class="fa-solid fa-forward me-1"></i>' . $maxAdvanceDueDate . '</span>';
                if (!empty($advanceEmiBadgeText)) {
                    $html .= '<div class="extra-small text-success mt-1" style="font-size:10px;"><i class="fa-solid fa-layer-group me-1"></i>' . htmlspecialchars($advanceEmiBadgeText) . '</div>';
                }
            } elseif ($maxRegularDueDate) {
                $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold fs-12 px-2 py-1"><i class="fa-solid fa-calendar-check me-1"></i>' . $maxRegularDueDate . '</span>';
                $html .= '<div class="extra-small text-muted mt-1" style="font-size:10px;">EMI #' . ($maxRegularEmiNo > 0 ? $maxRegularEmiNo : $maxPaidEmiNo) . ' of ' . $totalEmiNo . '</div>';
            } elseif ($maxPaidDueDate) {
                $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold fs-12 px-2 py-1"><i class="fa-solid fa-calendar-check me-1"></i>' . $maxPaidDueDate . '</span>';
                $html .= '<div class="extra-small text-muted mt-1" style="font-size:10px;">EMI #' . $maxPaidEmiNo . ' of ' . $totalEmiNo . '</div>';
            } else {
                $html .= '<span class="badge bg-light text-muted border fs-11">No Schedule</span>';
            }
            $html .= '</td>';

            // Col 5: Last EMI Paid
            $html .= '<td class="text-end">';
            $html .= '<div class="fw-bold text-dark fs-12">Tk. ' . number_format($lastPaidAmount) . '</div>';
            if ($lastPaymentDate !== 'N/A') {
                $html .= '<div class="extra-small text-muted" style="font-size:10px;"><i class="fa-solid fa-clock-rotate-left me-1"></i>' . $lastPaymentDate . '</div>';
            }
            $html .= '</td>';

            // Col 6: Regular Paid
            $html .= '<td class="text-end">';
            $html .= '<div class="fw-bold text-dark fs-12">Tk. ' . number_format($regularEmiPaid) . '</div>';
            $html .= '<div class="extra-small text-muted" style="font-size:10px;">Current Month</div>';
            $html .= '</td>';

            // Col 7: Advance Paid
            $html .= '<td class="text-end">';
            if ($advanceEmiPaid > 0) {
                $html .= '<span class="badge bg-success-subtle text-success fw-bold fs-11">Tk. ' . number_format($advanceEmiPaid) . '</span>';
                if (!empty($advanceEmiBadgeText)) {
                    $html .= '<div class="extra-small text-success mt-1" style="font-size:10px;" title="Advance Installment Numbers"><i class="fa-solid fa-layer-group me-1"></i>' . htmlspecialchars($advanceEmiBadgeText) . '</div>';
                } else {
                    $html .= '<div class="extra-small text-success mt-1" style="font-size:9px;"><i class="fa-solid fa-forward me-1"></i>Advance</div>';
                }
            } else {
                $html .= '<span class="text-muted fs-11">Tk. 0</span>';
            }
            $html .= '</td>';

            // Col 8: Due EMI
            $html .= '<td class="text-end">';
            if ($overdueEmiAmount > 0) {
                $html .= '<span class="badge bg-danger-subtle text-danger fw-bold fs-11">Tk. ' . number_format($overdueEmiAmount) . '</span>';
                $html .= '<div class="extra-small text-danger mt-1" style="font-size:9px;"><i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue</div>';
            } else {
                $html .= '<span class="badge bg-success-subtle text-success fs-10"><i class="fa-solid fa-check me-1"></i>Cleared</span>';
            }
            $html .= '</td>';

            // Col 9: Actions
            $html .= '<td class="text-center">';
            $html .= '<div class="d-flex align-items-center justify-content-center gap-1">';
            $html .= '<a href="' . $ledgerUrl . '" class="btn btn-outline-primary btn-sm py-1 px-2" style="font-size:11px;" target="_blank" title="View Full Client Ledger & Cash In History"><i class="fa-solid fa-receipt me-1"></i>Ledger</a>';
            $html .= '<button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size:11px;" onclick="openClientDueBreakdownModal(' . $bookingId . ')" title="Quick Due Breakdown Preview"><i class="fa-solid fa-eye"></i></button>';

            $fCount = $followupCounts[$bookingId] ?? 0;
            $escapedName = htmlspecialchars(trim(preg_replace('/\s+/', ' ', $client['client_name'] ?? 'Client')), ENT_QUOTES);
            $totalEmiVal = (int)($client['total_emi_no'] ?? 36);
            $leadIdVal = (int)($client['lead_id'] ?? 0) ?: 'null';
            $html .= '<button type="button" onclick="openClientEmiFollowupModal(' . $bookingId . ', \'' . $escapedName . '\', ' . $totalEmiVal . ', ' . $leadIdVal . ')" class="btn btn-sm btn-outline-primary py-1 px-2 text-nowrap" style="font-size:11px;" title="Add / View Follow Up Notes">';
            $html .= '<i class="fa-light fa-comment-dots me-1"></i>Follow Up';
            $html .= '<span class="badge ' . ($fCount > 0 ? 'bg-primary' : 'bg-secondary') . ' rounded-pill ms-1 followup-count-badge" data-booking-id="' . $bookingId . '" style="font-size:10px;">' . $fCount . '</span>';
            $html .= '</button>';
            $html .= '</div>';
            $html .= '</td>';

            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * Export Filtered Client-wise Due Records to CSV
     */
    public function exportDueTracking()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
        $dueType = trim($_GET['due_type'] ?? 'all');
        $search = trim($_GET['search'] ?? '');
        $dateType = trim($_GET['date_type'] ?? '');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');
        $sortBy = trim($_GET['sort_by'] ?? 'overall_due');
        $sortOrder = strtoupper(trim($_GET['sort_order'] ?? 'DESC'));
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }

        $filters = [
            'project_id' => $selectedProjectId,
            'due_type'   => $dueType,
            'search'     => $search,
            'date_type'  => $dateType,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'sort_by'    => $sortBy,
            'sort_order' => $sortOrder
        ];

        // Retrieve up to 10,000 records for CSV export
        $clients = $this->clientModel->getClientWiseDueList($filters, 10000, 0);

        $filename = "Client_Due_Tracking_" . date('Y_m_d_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM for Microsoft Excel
        fputs($output, "\xEF\xBB\xBF");

        // Header row
        fputcsv($output, [
            'Booking ID',
            'Booking Date',
            'Client Name',
            'Phone',
            'Email',
            'Sales Agent',
            'Project Name',
            'Unit / Flat',
            'Floor',
            'Unit Type',
            'Total Flat Value (BDT)',
            'Discount (BDT)',
            'Total Paid (BDT)',
            'Land Share Target (BDT)',
            'Land Share Paid (BDT)',
            'Land Share Due (BDT)',
            'Dev Charges Target (BDT)',
            'Dev Charges Paid (BDT)',
            'Dev Charges Due (BDT)',
            'Shop Service Target (BDT)',
            'Shop Service Paid (BDT)',
            'Shop Service Due (BDT)',
            'Construction Target (BDT)',
            'Regular EMI Paid (BDT)',
            'Paid Up To',
            'Advance EMI Paid (BDT)',
            'Total EMI Paid (BDT)',
            'EMIs Paid / Total',
            'Remaining EMI Due (BDT)',
            'Overdue EMI (BDT)',
            'Overall Due (BDT)',
            'Payment Status',
            'Last Payment Date'
        ]);

        foreach ($clients as $c) {
            $totalVal = (float)$c['total_price'];
            $discount = (float)$c['discount_amount'];
            $totalPaid = (float)$c['total_paid'];
            $overallDue = (float)$c['overall_due'];

            $landTarget = (float)$c['total_land_share'];
            $landPaid = (float)$c['land_share_paid'];
            $landDue = (float)$c['land_share_due'];

            $devTarget = (float)$c['development_charges'];
            $devPaid = (float)$c['dev_charge_paid'];
            $devDue = (float)$c['dev_charge_due'];

            $isShop = !empty($c['is_shop']) && (int)$c['is_shop'] === 1;
            $unitType = $isShop ? 'Shop' : (!empty($c['unit_type']) ? ucfirst($c['unit_type']) : 'Residential');

            $shopTarget = (float)($c['shop_service_charge_target'] ?? 0);
            $shopPaid = (float)($c['shop_service_charge_paid'] ?? 0);
            $shopDue = (float)($c['shop_service_charge_due'] ?? 0);

            $consFee = (float)$c['construction_fee'];
            $emiPaid = (float)$c['emi_paid'];
            $regularEmiPaid = (float)($c['regular_emi_paid'] ?? 0);
            $advanceEmiPaid = (float)($c['advance_emi_paid'] ?? 0);
            $maxPaidDueDate = !empty($c['max_regular_paid_due_date']) ? date('M Y', strtotime($c['max_regular_paid_due_date'])) : (!empty($c['max_paid_due_date']) ? date('M Y', strtotime($c['max_paid_due_date'])) : 'N/A');
            $remEmiDue = (float)$c['remaining_emi_due'];
            $overdueEmi = (float)$c['overdue_emi_amount'];

            $completedEmi = (int)($c['completed_emi_count'] ?? 0);
            $totalEmi = (int)($c['total_emi_no'] ?? 0);
            if ($totalEmi <= 0) $totalEmi = 36;
            if ($completedEmi > $totalEmi) $totalEmi = $completedEmi;

            $status = ($overallDue <= 0) ? 'Fully Paid' : 'Due Outstanding';

            fputcsv($output, [
                $c['booking_id'],
                !empty($c['booking_date']) ? date('Y-m-d', strtotime($c['booking_date'])) : '',
                $c['client_name'] ?? '',
                $c['phone'] ?? '',
                $c['email'] ?? '',
                $c['sales_agent_name'] ?? '',
                $c['project_name'] ?? '',
                $c['unit_name'] ?? '',
                $c['floor_no'] ?? '',
                $unitType,
                number_format($totalVal, 2, '.', ''),
                number_format($discount, 2, '.', ''),
                number_format($totalPaid, 2, '.', ''),
                number_format($landTarget, 2, '.', ''),
                number_format($landPaid, 2, '.', ''),
                number_format($landDue, 2, '.', ''),
                number_format($devTarget, 2, '.', ''),
                number_format($devPaid, 2, '.', ''),
                number_format($devDue, 2, '.', ''),
                number_format($shopTarget, 2, '.', ''),
                number_format($shopPaid, 2, '.', ''),
                number_format($shopDue, 2, '.', ''),
                number_format($consFee, 2, '.', ''),
                number_format($regularEmiPaid, 2, '.', ''),
                $maxPaidDueDate,
                number_format($advanceEmiPaid, 2, '.', ''),
                number_format($emiPaid, 2, '.', ''),
                "{$completedEmi}/{$totalEmi}",
                number_format($remEmiDue, 2, '.', ''),
                number_format($overdueEmi, 2, '.', ''),
                number_format($overallDue, 2, '.', ''),
                $status,
                !empty($c['last_payment_date']) ? date('Y-m-d', strtotime($c['last_payment_date'])) : ''
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Check if user is authorized to manage or view CED Follow-ups
     */
    protected function isFollowupUserAuthorized(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');

        return in_array($userRoleId, [1, 3, 7, 33, 34, 35, 36, 37, 39])
            || strpos($roleName, 'account') !== false
            || strpos($roleName, 'client experience') !== false
            || strpos($roleName, 'clients experiment') !== false
            || strpos($roleName, 'communicat') !== false
            || strpos($roleName, 'management') !== false
            || strpos($roleName, 'admin') !== false
            || hasPermission("CED Manage")
            || hasPermission("Cash In Verify")
            || (function_exists('isClientsCommunicated') && isClientsCommunicated())
            || (function_exists('isAccountsHead') && isAccountsHead())
            || (function_exists('isAccountsExecutive') && isAccountsExecutive());
    }

    /**
     * Get aggregate stats for follow-up dashboard
     */
    public function getFollowupStats($projectId = null): array
    {
        $where = "lb.deleted_at IS NULL";
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            $where .= " AND lb.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $sql = "
            SELECT 
                COUNT(CASE WHEN f.next_follow_up_date = CURDATE() THEN 1 END) as today_count,
                COUNT(CASE WHEN f.next_follow_up_date > CURDATE() AND f.next_follow_up_date <= DATE_ADD(CURDATE(), INTERVAL 5 DAY) THEN 1 END) as upcoming_5d_count,
                COUNT(CASE WHEN f.next_follow_up_date < CURDATE() AND f.next_follow_up_date IS NOT NULL THEN 1 END) as overdue_count,
                COUNT(CASE WHEN f.next_follow_up_date IS NOT NULL THEN 1 END) as total_scheduled_count
            FROM emi_followups f
            INNER JOIN (
                SELECT booking_id, MAX(id) AS max_id
                FROM emi_followups
                GROUP BY booking_id
            ) latest ON latest.max_id = f.id
            INNER JOIN lead_bookings lb ON f.booking_id = lb.id
            WHERE {$where}
        ";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            return $res ? $res->fetch_assoc() : [
                'today_count' => 0, 'upcoming_5d_count' => 0, 'overdue_count' => 0, 'total_scheduled_count' => 0
            ];
        }

        $res = $this->db->query($sql);
        return $res ? $res->fetch_assoc() : [
            'today_count' => 0, 'upcoming_5d_count' => 0, 'overdue_count' => 0, 'total_scheduled_count' => 0
        ];
    }

    /**
     * Get Query Data for scheduled followups
     */
    public function getScheduledFollowupsData(string $mode, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $whereParts = ["lb.deleted_at IS NULL"];
        $params = [];
        $types = "";

        if ($mode === 'today') {
            $whereParts[] = "f.next_follow_up_date = CURDATE()";
        } elseif ($mode === 'upcoming') {
            $days = !empty($filters['days']) ? max(1, (int)$filters['days']) : 5;
            $whereParts[] = "f.next_follow_up_date > CURDATE() AND f.next_follow_up_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
            $params[] = $days;
            $types .= "i";
        } elseif ($mode === 'overdue') {
            $whereParts[] = "f.next_follow_up_date < CURDATE() AND f.next_follow_up_date IS NOT NULL";
        } elseif ($mode === 'all') {
            $whereParts[] = "f.next_follow_up_date IS NOT NULL";
        }

        if (!empty($filters['project_id'])) {
            $whereParts[] = "lb.project_id = ?";
            $params[] = (int)$filters['project_id'];
            $types .= "i";
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $whereParts[] = "(l.full_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ? OR l.email LIKE ? OR p.name LIKE ? OR fl.flat_name LIKE ? OR CAST(lb.id AS CHAR) = ? OR f.note LIKE ? OR f.next_follow_up_note LIKE ?)";
            for ($i = 0; $i < 9; $i++) {
                $params[] = $search;
                $types .= "s";
            }
        }

        $whereSql = implode(" AND ", $whereParts);

        // Count query (only consider the latest follow-up per booking so completed schedules are not shown as overdue)
        $countSql = "
            SELECT COUNT(*) as total 
            FROM emi_followups f
            INNER JOIN (
                SELECT booking_id, MAX(id) AS max_id
                FROM emi_followups
                GROUP BY booking_id
            ) latest ON latest.max_id = f.id
            INNER JOIN lead_bookings lb ON f.booking_id = lb.id
            INNER JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN flats fl ON lb.flat_id = fl.id
            WHERE {$whereSql}
        ";

        $countParams = $params;
        $countTypes = $types;

        $totalRecords = 0;
        if (!empty($countParams)) {
            $cStmt = $this->db->prepare($countSql);
            $cStmt->bind_param($countTypes, ...$countParams);
            $cStmt->execute();
            $cRes = $cStmt->get_result();
            if ($cRes) {
                $cRow = $cRes->fetch_assoc();
                $totalRecords = (int)($cRow['total'] ?? 0);
            }
        } else {
            $cRes = $this->db->query($countSql);
            if ($cRes) {
                $cRow = $cRes->fetch_assoc();
                $totalRecords = (int)($cRow['total'] ?? 0);
            }
        }

        // Data query
        $orderSql = ($mode === 'upcoming') 
            ? "ORDER BY f.next_follow_up_date ASC, f.id DESC" 
            : (($mode === 'overdue') ? "ORDER BY f.next_follow_up_date DESC, f.id DESC" : "ORDER BY f.id DESC");

        $dataSql = "
            SELECT 
                f.id as followup_id,
                f.booking_id,
                f.emi_no,
                f.note,
                f.activity_date,
                f.activity_mode,
                f.call_status,
                f.activity_reason,
                f.next_follow_up_date,
                f.next_follow_up_note,
                f.created_at,
                f.added_by,
                u.full_name as author_name,
                lb.lead_id,
                lb.project_id,
                l.full_name as client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), TRIM(l.phone_number)) as phone,
                l.email,
                p.name as project_name,
                fl.flat_name as unit_name,
                fl.floor_no,
                GREATEST(
                    COALESCE(NULLIF(CASE WHEN lb.total_emi_no > 0 AND lb.total_emi_no <= 120 THEN lb.total_emi_no ELSE NULL END, 0), 0),
                    36
                ) as total_emi_no,
                ua.full_name as sales_agent_name,
                COALESCE(f_agg.total_followup_count, 1) as total_followup_count
            FROM emi_followups f
            INNER JOIN (
                SELECT booking_id, MAX(id) AS max_id
                FROM emi_followups
                GROUP BY booking_id
            ) latest ON latest.max_id = f.id
            INNER JOIN lead_bookings lb ON f.booking_id = lb.id
            INNER JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN flats fl ON lb.flat_id = fl.id
            LEFT JOIN users u ON f.added_by = u.id
            LEFT JOIN users ua ON COALESCE(NULLIF(lb.sales_by_id, 0), NULLIF(l.assigned_id, 0)) = ua.id
            LEFT JOIN (
                SELECT booking_id, COUNT(*) as total_followup_count
                FROM emi_followups
                GROUP BY booking_id
            ) f_agg ON f_agg.booking_id = f.booking_id
            WHERE {$whereSql}
            {$orderSql}
            LIMIT ? OFFSET ?
        ";

        $dataParams = $params;
        $dataTypes = $types . "ii";
        $dataParams[] = $limit;
        $dataParams[] = $offset;

        $rows = [];
        $dStmt = $this->db->prepare($dataSql);
        $dStmt->bind_param($dataTypes, ...$dataParams);
        $dStmt->execute();
        $dRes = $dStmt->get_result();
        if ($dRes) {
            $rows = $dRes->fetch_all(MYSQLI_ASSOC);
        }

        return [
            'total' => $totalRecords,
            'records' => $rows
        ];
    }

    /**
     * Render Follow-up Table Rows HTML
     */
    public function renderFollowupRowsHtml(array $followups, string $viewMode = 'today'): string
    {
        if (empty($followups)) {
            $emptyMsg = ($viewMode === 'today') 
                ? 'No follow-ups scheduled for today.' 
                : (($viewMode === 'upcoming') ? 'No upcoming follow-ups scheduled in this period.' : 'No follow-up records found matching the criteria.');
            return '<tr><td colspan="8" class="text-center py-5 text-muted"><i class="fa-solid fa-calendar-check fa-3x mb-3 text-secondary opacity-50 d-block"></i>' . $emptyMsg . '</td></tr>';
        }

        $todayStr = date('Y-m-d');
        $html = '';

        foreach ($followups as $idx => $f) {
            $bookingId = (int)$f['booking_id'];
            $clientName = htmlspecialchars($f['client_name'] ?? 'N/A');
            $phone = htmlspecialchars($f['phone'] ?? '');
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            $email = htmlspecialchars($f['email'] ?? '');
            $salesAgent = htmlspecialchars($f['sales_agent_name'] ?? 'Not Assigned');
            $projectName = htmlspecialchars($f['project_name'] ?? 'N/A');
            $unitName = htmlspecialchars($f['unit_name'] ?? 'N/A');
            $floorNo = htmlspecialchars($f['floor_no'] ?? '');

            $emiNo = (int)($f['emi_no'] ?? 0);
            $purposeLabel = ($emiNo === 999) ? 'Development Charge' : (($emiNo === 0) ? 'General Follow Up' : 'EMI #' . $emiNo);
            $purposeBadge = ($emiNo === 999) ? 'bg-warning-subtle text-dark border border-warning' : (($emiNo === 0) ? 'bg-secondary-subtle text-dark border border-secondary' : 'bg-primary-subtle text-primary border border-primary');

            $activityDate = !empty($f['activity_date']) ? date('d M, Y', strtotime($f['activity_date'])) : 'N/A';
            $activityMode = htmlspecialchars($f['activity_mode'] ?? 'N/A');
            $callStatus = htmlspecialchars($f['call_status'] ?? '');
            $reason = htmlspecialchars($f['activity_reason'] ?? 'N/A');
            $note = nl2br(htmlspecialchars($f['note'] ?? ''));

            $nextFollowUpDate = !empty($f['next_follow_up_date']) ? $f['next_follow_up_date'] : null;
            $nextFollowUpNote = !empty($f['next_follow_up_note']) ? htmlspecialchars($f['next_follow_up_note']) : '';
            $authorName = htmlspecialchars($f['author_name'] ?? 'System');
            $totalFollowups = (int)($f['total_followup_count'] ?? 1);
            $totalEmiNo = (int)($f['total_emi_no'] ?? 36);
            $leadId = (int)($f['lead_id'] ?? 0);

            // Compute Date Badge
            $dateBadge = '';
            if ($nextFollowUpDate) {
                $nextFormatted = date('d M, Y', strtotime($nextFollowUpDate));
                if ($nextFollowUpDate === $todayStr) {
                    $dateBadge = '<span class="badge bg-danger text-white px-2 py-1 fs-11"><i class="fa-solid fa-bell me-1"></i>Today</span>';
                } elseif ($nextFollowUpDate < $todayStr) {
                    $diffDays = (int)round((strtotime($todayStr) - strtotime($nextFollowUpDate)) / 86400);
                    $dateBadge = '<span class="badge bg-dark text-warning px-2 py-1 fs-11"><i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue (' . $diffDays . 'd ago)</span>';
                } else {
                    $diffDays = (int)round((strtotime($nextFollowUpDate) - strtotime($todayStr)) / 86400);
                    if ($diffDays === 1) {
                        $dateBadge = '<span class="badge bg-warning text-dark px-2 py-1 fs-11"><i class="fa-solid fa-clock me-1"></i>Tomorrow</span>';
                    } else {
                        $dateBadge = '<span class="badge bg-info-subtle text-info-emphasis px-2 py-1 fs-11 border border-info-subtle"><i class="fa-solid fa-calendar-day me-1"></i>In ' . $diffDays . ' days</span>';
                    }
                }
            } else {
                $nextFormatted = 'N/A';
            }

            $ledgerUrl = "{$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}";
            $escapedClientName = htmlspecialchars(trim(preg_replace('/\s+/', ' ', $f['client_name'] ?? 'Client')), ENT_QUOTES);

            $html .= '<tr class="align-middle">';

            // Col 1: Booking ID & Scheduled Date
            $html .= '<td class="text-center">';
            $html .= '<a href="' . $ledgerUrl . '" class="fw-bold text-primary text-decoration-none">#' . $bookingId . '</a>';
            $html .= '<div class="mt-1">' . $dateBadge . '</div>';
            $html .= '<div class="extra-small text-muted mt-1 fw-semibold">' . $nextFormatted . '</div>';
            $html .= '</td>';

            // Col 2: Client Info
            $html .= '<td>';
            $html .= '<div class="d-flex align-items-center">';
            $html .= '<div class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold" style="width:34px; height:34px; font-size:12px;">' . mb_substr($clientName, 0, 1) . '</div>';
            $html .= '<div>';
            $html .= '<a href="' . $ledgerUrl . '" class="fw-bold text-dark text-decoration-none d-block">' . $clientName . '</a>';
            if (!empty($phone)) {
                $html .= '<div class="d-flex align-items-center gap-2 mt-1">';
                $html .= '<a href="tel:' . $phone . '" class="extra-small text-muted text-decoration-none"><i class="fa-solid fa-phone me-1 text-secondary"></i>' . $phone . '</a>';
                if (!empty($cleanPhone)) {
                    $html .= '<a href="https://wa.me/' . $cleanPhone . '" target="_blank" class="text-success extra-small" title="Chat on WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>';
                }
                $html .= '</div>';
            }
            if (!empty($salesAgent)) {
                $html .= '<span class="extra-small text-muted d-block" title="Assigned Sales Agent"><i class="fa-solid fa-user-tie me-1 text-info"></i>' . $salesAgent . '</span>';
            }
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</td>';

            // Col 3: Project & Flat
            $html .= '<td>';
            $html .= '<span class="badge bg-light text-primary border border-primary-subtle mb-1 d-inline-block text-truncate" style="max-width: 170px;" title="' . $projectName . '">' . $projectName . '</span>';
            $html .= '<div class="fw-semibold text-dark extra-small">';
            $html .= '<i class="fa-solid fa-building me-1 text-muted"></i>' . $unitName . (!empty($floorNo) ? ' (Fl ' . $floorNo . ')' : '');
            $html .= '</div>';
            $html .= '</td>';

            // Col 4: Target Purpose
            $html .= '<td>';
            $html .= '<span class="badge ' . $purposeBadge . ' fs-11 px-2 py-1"><i class="fa-solid fa-bullseye me-1"></i>' . $purposeLabel . '</span>';
            $html .= '<div class="extra-small text-muted mt-1"><i class="fa-solid fa-tag me-1"></i>' . $reason . '</div>';
            $html .= '</td>';

            // Col 5: Next Follow Up Note
            $html .= '<td>';
            if (!empty($nextFollowUpNote)) {
                $html .= '<div class="p-2 rounded bg-light border-start border-3 border-primary fs-12 text-dark fw-medium">';
                $html .= '<i class="fa-solid fa-note-sticky text-primary me-1"></i>' . $nextFollowUpNote;
                $html .= '</div>';
            } else {
                $html .= '<span class="text-muted extra-small">No specific note added</span>';
            }
            $html .= '</td>';

            // Col 6: Last Activity Summary
            $html .= '<td>';
            $html .= '<div class="extra-small text-muted mb-1">';
            $html .= '<i class="fa-solid fa-calendar me-1"></i>' . $activityDate;
            if (!empty($activityMode)) {
                $html .= ' &bull; <span class="fw-semibold text-dark">' . $activityMode . '</span>';
            }
            if (!empty($callStatus)) {
                $html .= ' <span class="badge ' . (strtolower($callStatus) === 'incoming' ? 'bg-success' : 'bg-primary') . ' extra-small py-0 px-1">' . $callStatus . '</span>';
            }
            $html .= '</div>';
            $html .= '<div class="fs-12 text-secondary text-truncate" style="max-width: 220px;" title="' . htmlspecialchars(strip_tags($note)) . '">';
            $html .= htmlspecialchars(mb_strimwidth(strip_tags($note), 0, 80, '...'));
            $html .= '</div>';
            $html .= '<div class="extra-small text-muted mt-1"><i class="fa-solid fa-user me-1"></i>By: ' . $authorName . '</div>';
            $html .= '</td>';

            // Col 7: Actions
            $html .= '<td class="text-center">';
            $html .= '<div class="d-flex align-items-center justify-content-center gap-1">';
            $html .= '<button type="button" onclick="openClientEmiFollowupModal(' . $bookingId . ', \'' . $escapedClientName . '\', ' . $totalEmiNo . ', ' . ($leadId ?: 'null') . ')" class="btn btn-sm btn-primary py-1 px-2 text-nowrap" style="font-size:11px;" title="Open Follow-up Dialog & Full History">';
            $html .= '<i class="fa-light fa-comment-dots me-1"></i>Follow Up';
            $html .= '<span class="badge bg-white text-primary rounded-pill ms-1 followup-count-badge" data-booking-id="' . $bookingId . '" style="font-size:10px;">' . $totalFollowups . '</span>';
            $html .= '</button>';

            $html .= '<a href="' . $ledgerUrl . '" target="_blank" class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size:11px;" title="View Client Ledger"><i class="fa-solid fa-receipt"></i></a>';
            $html .= '</div>';
            $html .= '</td>';

            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * Today's Follow Ups View & AJAX Handler
     */
    public function todayFollowUps()
    {
        if (!$this->isFollowupUserAuthorized()) {
            setError("You do not have permission to access CED Follow Ups.");
            header("Location: {$GLOBALS['base_url']}/home");
            exit;
        }

        $isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';
        $filterType = trim($_GET['filter'] ?? 'today');
        $mode = ($filterType === 'overdue') ? 'overdue' : 'today';
        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
        $search = trim($_GET['search'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(5, (int)($_GET['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $filters = [
            'project_id' => $selectedProjectId,
            'search'     => $search
        ];

        $data = $this->getScheduledFollowupsData($mode, $filters, $limit, $offset);
        $totalRecords = $data['total'];
        $records = $data['records'];
        $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;
        $stats = $this->getFollowupStats($selectedProjectId);

        if ($isAjax) {
            $tableHtml = $this->renderFollowupRowsHtml($records, $mode);
            $paginationHtml = $this->generateAjaxPagination($page, $totalPages, $totalRecords, $limit, 'loadFollowupsPage');
            $startEntry = $totalRecords > 0 ? (($page - 1) * $limit) + 1 : 0;
            $endEntry = min($totalRecords, $page * $limit);
            $infoText = "Showing {$startEntry} to {$endEntry} of {$totalRecords} follow-ups";

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'page' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $totalRecords,
                'table_html' => $tableHtml,
                'pagination_html' => $paginationHtml,
                'info_text' => $infoText,
                'stats' => $stats
            ]);
            exit;
        }

        // Projects list
        $projectModel = new Project($this->db);
        $projects = $projectModel->getAll();
        if (empty($projects)) {
            $projects = $this->clientModel->getProjectsForCED();
        }

        $this->view("ced/followups-list", [
            "current_route"       => "today-follow-ups",
            "page_title"          => "Today's Client Follow Ups",
            "view_mode"           => "today",
            "active_tab"          => $mode,
            "records"             => $records,
            "totalRecords"        => $totalRecords,
            "totalPages"          => $totalPages,
            "currentPage"         => $page,
            "limit"               => $isAll ? 'all' : $limit,
            "search"              => $search,
            "stats"               => $stats,
            "projects"            => $projects,
            "selectedProjectId"   => $selectedProjectId,
            "days"                => 5
        ]);
    }

    /**
     * Upcoming Follow Ups (Next 5 Days) View & AJAX Handler
     */
    public function upcomingFollowUps()
    {
        if (!$this->isFollowupUserAuthorized()) {
            setError("You do not have permission to access CED Follow Ups.");
            header("Location: {$GLOBALS['base_url']}/home");
            exit;
        }

        $isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';
        $days = isset($_GET['days']) ? max(1, min(60, (int)$_GET['days'])) : 5;
        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
        $search = trim($_GET['search'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(5, (int)($_GET['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $filters = [
            'days'       => $days,
            'project_id' => $selectedProjectId,
            'search'     => $search
        ];

        $data = $this->getScheduledFollowupsData('upcoming', $filters, $limit, $offset);
        $totalRecords = $data['total'];
        $records = $data['records'];
        $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;
        $stats = $this->getFollowupStats($selectedProjectId);

        if ($isAjax) {
            $tableHtml = $this->renderFollowupRowsHtml($records, 'upcoming');
            $paginationHtml = $this->generateAjaxPagination($page, $totalPages, $totalRecords, $limit, 'loadFollowupsPage');
            $startEntry = $totalRecords > 0 ? (($page - 1) * $limit) + 1 : 0;
            $endEntry = min($totalRecords, $page * $limit);
            $infoText = "Showing {$startEntry} to {$endEntry} of {$totalRecords} upcoming follow-ups";

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'page' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $totalRecords,
                'table_html' => $tableHtml,
                'pagination_html' => $paginationHtml,
                'info_text' => $infoText,
                'stats' => $stats
            ]);
            exit;
        }

        // Projects list
        $projectModel = new Project($this->db);
        $projects = $projectModel->getAll();
        if (empty($projects)) {
            $projects = $this->clientModel->getProjectsForCED();
        }

        $this->view("ced/followups-list", [
            "current_route"       => "upcoming-follow-ups",
            "page_title"          => "Upcoming Client Follow Ups (Next {$days} Days)",
            "view_mode"           => "upcoming",
            "active_tab"          => "upcoming",
            "records"             => $records,
            "totalRecords"        => $totalRecords,
            "totalPages"          => $totalPages,
            "currentPage"         => $page,
            "limit"               => $isAll ? 'all' : $limit,
            "search"              => $search,
            "stats"               => $stats,
            "projects"            => $projects,
            "selectedProjectId"   => $selectedProjectId,
            "days"                => $days
        ]);
    }

    /**
     * Export Follow-ups to CSV
     */
    public function exportFollowUps()
    {
        if (!$this->isFollowupUserAuthorized()) {
            exit("Unauthorized");
        }

        $mode = trim($_GET['mode'] ?? 'today');
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 5;
        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
        $search = trim($_GET['search'] ?? '');

        $filters = [
            'days'       => $days,
            'project_id' => $selectedProjectId,
            'search'     => $search
        ];

        $data = $this->getScheduledFollowupsData($mode, $filters, 5000, 0);
        $records = $data['records'];

        $filename = "Follow_Ups_" . ucfirst($mode) . "_" . date('Y_m_d_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Booking ID',
            'Next Follow Up Date',
            'Next Follow Up Note',
            'Client Name',
            'Phone',
            'Email',
            'Project Name',
            'Unit Name',
            'Floor No',
            'Sales Agent',
            'EMI / Purpose',
            'Last Activity Date',
            'Last Activity Mode',
            'Call Status',
            'Activity Reason',
            'Last Discussion Note',
            'Followed By',
            'Created At'
        ]);

        foreach ($records as $r) {
            $emiNo = (int)($r['emi_no'] ?? 0);
            $purpose = ($emiNo === 999) ? 'Development Charge' : (($emiNo === 0) ? 'General Follow Up' : 'EMI #' . $emiNo);

            fputcsv($output, [
                $r['booking_id'],
                $r['next_follow_up_date'] ?? '',
                $r['next_follow_up_note'] ?? '',
                $r['client_name'] ?? '',
                $r['phone'] ?? '',
                $r['email'] ?? '',
                $r['project_name'] ?? '',
                $r['unit_name'] ?? '',
                $r['floor_no'] ?? '',
                $r['sales_agent_name'] ?? '',
                $purpose,
                $r['activity_date'] ?? '',
                $r['activity_mode'] ?? '',
                $r['call_status'] ?? '',
                $r['activity_reason'] ?? '',
                $r['note'] ?? '',
                $r['author_name'] ?? '',
                $r['created_at'] ?? ''
            ]);
        }

        fclose($output);
        exit;
    }
}

