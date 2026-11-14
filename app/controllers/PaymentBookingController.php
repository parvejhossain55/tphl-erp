<?php
class PaymentBookingController extends Controller
{
    protected $db;
    protected $bookingModel;
    protected $paymentModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->paymentModel = new PaymentLeadBooking($this->db);
        $this->bookingModel = new BookingLead($this->db);
    }

    // Index page: show leads for follow-up
    public function index($booking_id = null)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!$booking_id) {
            setError("Invalid Booking ID!");
            header("Location: {$GLOBALS['base_url']}/onboard-leads");
            exit;
        }


        // Booking info 
        $booking = $this->bookingModel->getById($booking_id); // single record

        if (!$booking) {
            setError("Booking not found!");
            header("Location: {$GLOBALS['base_url']}/onboard-leads");
            exit;
        }

        $currentUserId = (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
        $currentUserRoleId = (int)($_SESSION['user']['role_id'] ?? $_SESSION['user']['primary_role_id'] ?? 0);
        $secondaryRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $userRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $userName = strtolower(trim($_SESSION['user']['full_name'] ?? $_SESSION['user']['user_name'] ?? ''));

        $isManagement = in_array($currentUserRoleId, [1, 3, 20, 32, 33, 35]) ||
                        in_array($secondaryRoleId, [1, 3, 20, 32, 33, 35]) ||
                        (strpos($userRoleName, 'admin') !== false) ||
                        (strpos($userRoleName, 'management') !== false) ||
                        (strpos($userRoleName, 'ceo') !== false) ||
                        (strpos($userRoleName, 'chief') !== false) ||
                        (strpos($userName, 'management') !== false);

        $isSalesHead = in_array($currentUserRoleId, [5, 9]) ||
                       in_array($secondaryRoleId, [5, 9]) ||
                       (strpos($userRoleName, 'sales head') !== false) ||
                       (strpos($userRoleName, 'team head') !== false);

        $isAssignedSalesPerson = (
            (!empty($booking['sales_by_id']) && (int)$booking['sales_by_id'] === $currentUserId) ||
            (empty($booking['sales_by_id']) && !empty($booking['assigned_id']) && (int)$booking['assigned_id'] === $currentUserId)
        );

        if (!$isManagement && !$isSalesHead && !$isAssignedSalesPerson) {
            setError("You are not authorized to view or enter payments for this booking. Only the assigned salesperson for this sale or Management/Sales Head can access it.");
            header("Location: {$GLOBALS['base_url']}/booking-leads/" . ($booking['lead_id'] ?? ''));
            exit;
        }

        // All payments for this booking
        $payments = $this->paymentModel->getByBooking($booking_id);

        $companyBanksRes = $this->db->query("SELECT id, bank_name, account_name, account_number FROM company_bank_accounts ORDER BY bank_name ASC");
        $companyBanks = $companyBanksRes ? $companyBanksRes->fetch_all(MYSQLI_ASSOC) : [];

        $this->view("sales-developments/payments/payment-bookings", [
            "booking" => $booking,
            "payments" => $payments,
            "companyBanks" => $companyBanks,
            "current_route" => "payments-booking",
            "page_title" => "Booking Payment",
        ]);
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $bankName = !empty($_POST['bank_name']) ? trim($_POST['bank_name']) : null;
        if (in_array($bankName, ['Other Bank', 'Other Country / Foreign Bank', 'Foreign Bank', 'Other Country Bank']) && !empty($_POST['custom_bank_name'])) {
            $bankName = trim($_POST['custom_bank_name']);
        }

        $data = [
            'booking_id'          => $_POST['booking_id'] ?? 0,
            'payment_type'        => $_POST['payment_type'] ?? 'booking_money',
            'amount'              => floatval($_POST['amount'] ?? 0),
            'payment_method'      => $_POST['payment_method'] ?? 'cash',
            'payment_status'      => 'pending', // always pending — verified by accounts before becoming paid
            'payment_date'        => $_POST['payment_date'] ?? date('Y-m-d'),
            'transaction_ref'     => $_POST['transaction_ref'] ?? null,
            'bank_id'             => !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null,
            'bank_name'           => $bankName,
            'bank_account_no'     => $_POST['bank_account_no'] ?? null,
            'total_emi_no'        => $_POST['total_emi_no'] ?? null,
            'cheque_no'           => $_POST['cheque_no'] ?? null,
            'online_type'         => $_POST['online_type'] ?? null,
            'created_by'          => $_SESSION['user']['id'] ?? 1,
            'verification_status' => 'pending', // always pending until accounts verifies
        ];

        // Handle attachment uploads (1 to 4) - Supports JPG, PNG, WEBP, GIF, PDF
        foreach (['attachment_image', 'attachment_image2', 'attachment_image3', 'attachment_image4'] as $attField) {
            if (isset($_FILES[$attField]) && $_FILES[$attField]['error'] === UPLOAD_ERR_OK) {
                $uploadedFileName = $this->uploadPaymentFile($attField);
                if ($uploadedFileName) {
                    $data[$attField] = $uploadedFileName;
                }
            }
        }

        // VALIDATION
        if (!$data['booking_id'] || !$data['amount'] || !$data['payment_type']) {
            setError("Required data missing!");
            header("Location: {$GLOBALS['base_url']}/payments-booking/" . ($data['booking_id'] ?? 0));
            exit;
        }

        $isTestingMode = !empty($_POST['is_testing_mode']);

        // PHONE VALIDATION GATE (unless testing mode is checked)
        require_once dirname(__DIR__) . '/services/SmsService.php';
        $smsService = new SmsService($this->db);

        $sendSms = isset($_POST['send_sms']) ? filter_var($_POST['send_sms'], FILTER_VALIDATE_BOOLEAN) : false;

        $sendSms = isset($_POST['send_sms']) ? filter_var($_POST['send_sms'], FILTER_VALIDATE_BOOLEAN) : false;

        $bookingLead = $this->bookingModel->getById($data['booking_id']);
        if (!$bookingLead) {
            setError("Booking not found!");
            header("Location: {$GLOBALS['base_url']}/onboard-leads");
            exit;
        }

        $currentUserId = (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
        $currentUserRoleId = (int)($_SESSION['user']['role_id'] ?? $_SESSION['user']['primary_role_id'] ?? 0);
        $secondaryRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $userRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $userName = strtolower(trim($_SESSION['user']['full_name'] ?? $_SESSION['user']['user_name'] ?? ''));

        $isManagement = in_array($currentUserRoleId, [1, 3, 20, 32, 33, 35]) ||
                        in_array($secondaryRoleId, [1, 3, 20, 32, 33, 35]) ||
                        (strpos($userRoleName, 'admin') !== false) ||
                        (strpos($userRoleName, 'management') !== false) ||
                        (strpos($userRoleName, 'ceo') !== false) ||
                        (strpos($userRoleName, 'chief') !== false) ||
                        (strpos($userName, 'management') !== false);

        $isSalesHead = in_array($currentUserRoleId, [5, 9]) ||
                       in_array($secondaryRoleId, [5, 9]) ||
                       (strpos($userRoleName, 'sales head') !== false) ||
                       (strpos($userRoleName, 'team head') !== false);

        $isAssignedSalesPerson = (
            (!empty($bookingLead['sales_by_id']) && (int)$bookingLead['sales_by_id'] === $currentUserId) ||
            (empty($bookingLead['sales_by_id']) && !empty($bookingLead['assigned_id']) && (int)$bookingLead['assigned_id'] === $currentUserId)
        );

        if (!$isManagement && !$isSalesHead && !$isAssignedSalesPerson) {
            setError("You are not authorized to submit payments for this unit. Only the assigned salesperson or Management/Sales Head can enter payments.");
            header("Location: {$GLOBALS['base_url']}/booking-leads/" . ($bookingLead['lead_id'] ?? ''));
            exit;
        }
        $clientPhone = !empty($bookingLead['phone']) ? trim($bookingLead['phone']) : (!empty($bookingLead['phone_number']) ? trim($bookingLead['phone_number']) : '');

        if ($sendSms && !$isTestingMode) {
            $phoneCheck  = $smsService->validatePhoneNumber($clientPhone);

            if (!$phoneCheck['valid']) {
                // Do not block payment creation. It will just fail gracefully later.
                // setError("Cannot process payment: " . $phoneCheck['message'] . ". Please update the client's phone number first or enable Testing Mode.");
                // header("Location: {$GLOBALS['base_url']}/payments-booking/" . $data['booking_id']);
                // exit;
            }
        }

        $bookingId = (int)$data['booking_id'];
        $inputAmount = (float)$data['amount'];
        $created = null;
        $createdPayments = [];

        // Load AccountClient model for full waterfall targets
        require_once dirname(__DIR__) . '/models/AccountClient.php';
        $accountModel = new AccountClient($this->db);
        $summary = $accountModel->getPaymentSummaryByBookingId($bookingId);
        $clientDetails = $accountModel->getClientByBookingId($bookingId);
        $isShop = !empty($clientDetails['is_shop']);

        // 1. Booking Money Remaining
        $agreementValForBooking = (float)($summary['agreement_value'] ?? 0);
        $discountForBooking = (float)($summary['discount_amount'] ?? 0);
        $bookingTargetForTx = ($agreementValForBooking > 0)
            ? max(0, $agreementValForBooking - $discountForBooking)
            : (float)($summary['effective_booking_target'] ?? 300000);
        // 2. Land Share Remaining (unrejected total so pending counts)
        $landShareRemaining = max(0, (float)($summary['remaining_land_share_total'] ?? $summary['remaining_land_share']));

        // 1. Booking Money Remaining (capped by remaining land share)
        $bookingMoneyRemaining = ($landShareRemaining <= 0 || !empty($summary['is_land_share_completed']))
            ? 0
            : min($landShareRemaining, max(0, $bookingTargetForTx - (float)($summary['booking_money_total'] ?? 0)));

        // 3. Development Charge Remaining (or Shop Service Charge for shops)
        $flatDevTarget = (float)($summary['flat_development_charges'] ?? 0);
        $flatDevPaid = (float)($summary['development_charge_paid_total'] ?? 0);
        $flatDevRemaining = max(0, $flatDevTarget - $flatDevPaid);

        $shopSvcRemaining = $isShop
            ? max(0, (float)($summary['shop_service_charge_target'] ?? 0) - (float)($summary['shop_service_charge_paid_total'] ?? 0))
            : 0;

        $pool = $inputAmount;
        $paymentsToInsert = [];

        if ($data['payment_type'] === 'others') {
            $paymentsToInsert[] = ['type' => 'others', 'amount' => $pool];
            $pool = 0;
        } else {
            // Stage 1: Booking Money
            if ($bookingMoneyRemaining > 0 && $pool > 0) {
                $payAmount = min($pool, $bookingMoneyRemaining);
                $paymentsToInsert[] = ['type' => 'booking_money', 'amount' => $payAmount];
                $pool -= $payAmount;
                $bookingMoneyRemaining -= $payAmount;
                $landShareRemaining = max(0, $landShareRemaining - $payAmount);
            }

            // Stage 2: Land Share / Registration
            if ($landShareRemaining > 0 && $pool > 0) {
                $targetLandType = in_array($data['payment_type'], ['land_share', 'registration', 'mutation_cost'])
                    ? $data['payment_type']
                    : ($isShop ? 'registration' : 'registration');
                $payAmount = min($pool, $landShareRemaining);
                $paymentsToInsert[] = ['type' => $targetLandType, 'amount' => $payAmount];
                $pool -= $payAmount;
                $landShareRemaining -= $payAmount;
            }

            // Stage 3: Development Charge & Shop Service Charge
            if ($flatDevRemaining > 0 && $pool > 0) {
                $payAmount = min($pool, $flatDevRemaining);
                $paymentsToInsert[] = ['type' => 'development_charge', 'amount' => $payAmount];
                $pool -= $payAmount;
                $flatDevRemaining -= $payAmount;
            }

            if ($isShop && $shopSvcRemaining > 0 && $pool > 0) {
                $payAmount = min($pool, $shopSvcRemaining);
                $paymentsToInsert[] = ['type' => 'shop_service_charge', 'amount' => $payAmount];
                $pool -= $payAmount;
                $shopSvcRemaining -= $payAmount;
            }

            // Stage 4: EMI
            if ($pool > 0) {
                $paymentsToInsert[] = ['type' => 'emi', 'amount' => $pool];
                $pool = 0;
            }
        }

        // Shared batch_id if multiple payments created
        $batchId = (count($paymentsToInsert) > 1 || !empty($data['batch_id']))
            ? ($data['batch_id'] ?? ('batch_' . uniqid() . '_' . time()))
            : null;

        foreach ($paymentsToInsert as $pItem) {
            if ($pItem['amount'] <= 0) continue;
            $payData = $data;
            $payData['payment_type'] = $pItem['type'];
            $payData['amount']       = $pItem['amount'];
            if ($batchId) {
                $payData['batch_id'] = $batchId;
            }
            $insertId = $this->paymentModel->create($payData);
            if ($insertId) {
                $createdPayments[] = [
                    'id'     => $insertId,
                    'type'   => $pItem['type'],
                    'amount' => $pItem['amount']
                ];
                if (!$created) {
                    $created = $insertId;
                }
            }
        }

        if (!empty($created)) {
            $this->updateBookingPaymentStatus($data['booking_id']);

            if ($sendSms) {
                // Determine if a split happened and we should use the total input amount
                $smsAmount = $inputAmount;
                $types = array_map(function($cp) { return ucfirst(str_replace('_', ' ', $cp['type'])); }, $createdPayments);
                $smsPaymentType = !empty($types) ? implode(' & ', array_unique($types)) : ucfirst(str_replace('_', ' ', $data['payment_type'] ?? 'payment'));

                // Trigger 1: Send Payment Received SMS
                $smsResult = $smsService->sendPaymentReceivedSms($created, $isTestingMode, $smsAmount, $smsPaymentType);

                if ($isTestingMode) {
                    setSuccess("Payment created successfully! (Testing Mode: SMS & Email Skipped)");
                } elseif (!empty($smsResult['success'])) {
                    setSuccess("Payment created successfully & SMS dispatched to client (" . htmlspecialchars($clientPhone ?? '') . ")!");
                } else {
                    $errMsg = !empty($smsResult['response']) ? $smsResult['response'] : ($smsResult['message'] ?? 'Gateway Connection Error');
                    setError("Payment created, BUT SMS dispatch failed for (" . htmlspecialchars($clientPhone ?? '') . "): " . htmlspecialchars($errMsg));
                }
            } else {
                setSuccess("Payment created successfully! (SMS & Email Notification Disabled)");
            }
        } else {
            setError("Failed to create payment!");
        }

        // EMAIL SENDING LOGIC - Only send if SMS is enabled and testing mode is off
        if (!empty($created) && $sendSms && !$isTestingMode) {
            // Get Lead info and send email
            $leadInfoQuery = $this->db->query("
                SELECT l.full_name, l.email, p.name AS project_name, f.flat_name
                FROM lead_bookings lb
                LEFT JOIN leads l ON lb.lead_id = l.id
                LEFT JOIN flats f ON lb.flat_id = f.id
                LEFT JOIN projects p ON f.project_id = p.id
                WHERE lb.id = " . (int)$data['booking_id'] . "
            ");
            if ($leadInfoQuery && $leadInfoQuery->num_rows > 0) {
                $leadInfo = $leadInfoQuery->fetch_assoc();
                if (!empty($leadInfo['email']) && filter_var($leadInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    require_once dirname(__DIR__) . '/core/MailServiceForPHPMailer.php';
                    $mailService = new MailService();
                    $mailData = [
                        'client_name' => $leadInfo['full_name'],
                        'amount' => $inputAmount,
                        'project_name' => $leadInfo['project_name'],
                        'flat_name' => $leadInfo['flat_name'],
                        'payment_date' => $data['payment_date']
                    ];
                    $mailSent = $mailService->sendPaymentSuccessMail($leadInfo['email'], $mailData);
                    if ($mailSent) {
                        setSuccess("Payment created successfully & Email sent to " . htmlspecialchars($leadInfo['email']) . "!");
                    } else {
                        setError("Payment created, BUT Email dispatch failed.");
                    }
                }
            }
        }

        header("Location: {$GLOBALS['base_url']}/payments-booking/" . $data['booking_id']);
        exit;
    }

    /**
     * Recalculates and updates lead_bookings.payment_status
     * based on SUM of ALL verified payments vs final agreed amount.
     *
     * Final amount logic:
     *   - If approved_amount is set (discount approved) → use approved_amount
     *   - Else → agreement_value - discount_amount
     *
     * Returns the new booking payment status string.
     */
    public function updateBookingPaymentStatus($bookingId)
    {
        // Get booking financial info
        $booking = $this->db->query("
            SELECT agreement_value, discount_amount, approved_amount
            FROM lead_bookings
            WHERE id = {$bookingId}
        ")->fetch_assoc();

        // Final payable amount:
        // Use agreement_value minus approved_amount (discount)
        $agreementValue = (float)($booking['agreement_value'] ?? 0);
        $discountApproved = 0;
        if (!empty($booking['approved_amount'])) {
            $discountApproved = (float)$booking['approved_amount'];
        } elseif (!empty($booking['discount_amount']) && in_array(strtolower($booking['approval_status'] ?? ''), ['approved', 'not_required'])) {
            $discountApproved = (float)$booking['discount_amount'];
        }
        
        $finalAmount = max(0, $agreementValue - $discountApproved);

        // SUM of ALL verified payments (all payment types)
        $sum = $this->db->query("
            SELECT COALESCE(SUM(amount), 0) AS total
            FROM lead_payments
            WHERE booking_id = {$bookingId}
              AND verification_status = 'verified'
        ")->fetch_assoc();

        $totalPaid = (float) ($sum['total'] ?? 0);

        // Determine booking payment_status
        if ($totalPaid <= 0) {
            $bookingStatus = 'unpaid';
        } elseif ($totalPaid < $finalAmount) {
            $bookingStatus = 'partial';
        } else {
            $bookingStatus = 'paid';
        }

        // Update lead_bookings
        $stmt = $this->db->prepare("
            UPDATE lead_bookings
            SET payment_status = ?,
                updated_at     = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('si', $bookingStatus, $bookingId);
        $stmt->execute();

        return $bookingStatus;
    }
    public function handleFlatBooking($bookingId)
    {
        $stmt = $this->db->prepare("
        UPDATE flats f
        LEFT JOIN lead_bookings b ON b.flat_id = f.id
        SET f.is_booked = 1,
            f.status = 'booked',
            b.booking_status = 'completed'
        WHERE b.id = ?
    ");

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();

        // follow up update
        $stmt2 = $this->db->prepare("
        UPDATE lead_follow_ups lf
        INNER JOIN lead_bookings b ON lf.lead_id = b.lead_id
        SET lf.status = 'completed'
        WHERE b.id = ? AND lf.status = 'pending'
    ");
        $stmt2->bind_param("i", $bookingId);
        $stmt2->execute();

        // lead update: get phone numbers and update all matching lead records
        $phoneStmt = $this->db->prepare("
            SELECT l.phone, l.phone_number 
            FROM leads l
            INNER JOIN lead_bookings b ON l.id = b.lead_id
            WHERE b.id = ?
            LIMIT 1
        ");
        $phoneStmt->bind_param("i", $bookingId);
        $phoneStmt->execute();
        $pRes = $phoneStmt->get_result()->fetch_assoc();
        $phoneStmt->close();

        if ($pRes) {
            $p1 = trim($pRes['phone'] ?? '');
            $p2 = trim($pRes['phone_number'] ?? '');
            
            $conds = [];
            $params = [];
            $types = "";
            if ($p1 !== '') {
                $conds[] = "phone = ?";
                $conds[] = "phone_number = ?";
                $params[] = $p1;
                $params[] = $p1;
                $types .= "ss";
            }
            if ($p2 !== '' && $p2 !== $p1) {
                $conds[] = "phone = ?";
                $conds[] = "phone_number = ?";
                $params[] = $p2;
                $params[] = $p2;
                $types .= "ss";
            }
            
            if (!empty($conds)) {
                $updateSql = "UPDATE leads SET last_lead_action = 'booked' WHERE " . implode(" OR ", $conds);
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->bind_param($types, ...$params);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }
    }

    public function verify($id)
    {
        session_start();

        if ($_SESSION['user']['role'] != 'accounts') {
            setError("Unauthorized!");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // Get payment record
        $stmt = $this->db->prepare("SELECT * FROM lead_payments WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();

        if (!$payment || $payment['verification_status'] === 'verified') {
            setError("Invalid or already verified!");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $bookingId = (int) $payment['booking_id'];

        // Step 1: Mark this payment as verified (payment_status set after recalc below)
        $stmt = $this->db->prepare("
            UPDATE lead_payments
            SET verification_status = 'verified',
                verified_by         = ?,
                verified_at         = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('ii', $_SESSION['user']['id'], $id);
        $stmt->execute();

        // Step 2: Recalculate booking payment status based on all verified payments
        $newBookingStatus = $this->updateBookingPaymentStatus($bookingId);

        // Step 3: Update THIS payment's payment_status
        // Once verified, the payment is confirmed received ('paid', or 'partially_paid' if there is an installment balance)
        $isPartial = !empty($payment['due_emi_amount']) && (float)$payment['due_emi_amount'] > 0;
        $newPaymentStatus = $isPartial ? 'partially_paid' : 'paid';

        $stmt2 = $this->db->prepare("
            UPDATE lead_payments
            SET payment_status = ?
            WHERE id = ?
        ");
        $stmt2->bind_param('si', $newPaymentStatus, $id);
        $stmt2->execute();

        // Step 4: If booking is now fully paid → handle flat booking
        if ($newBookingStatus === 'paid') {
            $this->handleFlatBooking($bookingId);
        }

        // Trigger 2: Send Payment Verified SMS
        $sendSms = isset($_POST['send_sms']) ? filter_var($_POST['send_sms'], FILTER_VALIDATE_BOOLEAN) : (isset($_GET['send_sms']) ? filter_var($_GET['send_sms'], FILTER_VALIDATE_BOOLEAN) : false);
        $isTestingMode = isset($_POST['is_testing_mode']) ? filter_var($_POST['is_testing_mode'], FILTER_VALIDATE_BOOLEAN) : (isset($_GET['is_testing_mode']) ? filter_var($_GET['is_testing_mode'], FILTER_VALIDATE_BOOLEAN) : false);
        if ($sendSms && !$isTestingMode) {
            require_once dirname(__DIR__) . '/services/SmsService.php';
            $smsService = new SmsService($this->db);
            $smsService->sendBatchPaymentVerifiedSmsIfComplete($id);
        }

        setSuccess("Payment verified! Booking status: " . ucfirst($newBookingStatus));
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }


    public function validateCsvBulkPayments()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
            echo json_encode(['success' => false, 'message' => 'No CSV file uploaded']);
            exit;
        }

        $file = $_FILES['csv_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Upload failed']);
            exit;
        }

        $handle = fopen($file['tmp_name'], "r");
        if ($handle === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to read CSV file']);
            exit;
        }

        $header = fgetcsv($handle, 1000, ",");
        if (!$header) {
            echo json_encode(['success' => false, 'message' => 'CSV file is empty or invalid']);
            fclose($handle);
            exit;
        }
        $header = array_map('trim', $header);
        if (!empty($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        // Check required headers
        $reqHeaders = ['lead_number', 'project_name', 'flat_name', 'payment_type', 'amount'];
        $missing = array_diff($reqHeaders, $header);
        if (!empty($missing)) {
            echo json_encode(['success' => false, 'message' => 'Missing headers: ' . implode(', ', $missing)]);
            fclose($handle);
            exit;
        }

        $leadsCache = [];
        $projectsCache = [];
        $flatsCache = [];

        $results = [];
        $rowNum = 1;

        $cleanUtf8 = function($str) {
            if ($str === null || $str === false) return '';
            $str = str_replace("\xC2\xA0", ' ', (string)$str);
            if (!mb_check_encoding($str, 'UTF-8')) {
                $str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
            }
            return trim($str);
        };

        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            $rowNum++;
            if (count(array_filter($row)) === 0) continue;
            if (count($header) !== count($row)) {
                $results[] = [
                    'row' => $rowNum,
                    'valid' => false,
                    'error' => 'Column count mismatch'
                ];
                continue;
            }

            $data = array_combine($header, array_map($cleanUtf8, $row));
            $rowIssues = [];

            // 1. Validate and normalize payment_type
            $rawPaymentType = strtolower(trim($data['payment_type'] ?? ''));
            $paymentType = $rawPaymentType;
            if (in_array($rawPaymentType, ['landshare', 'land_share', 'land_share_amount', 'land share'])) {
                $paymentType = 'land_share';
            } elseif (in_array($rawPaymentType, ['development_charge', 'development charge', 'development'])) {
                $paymentType = 'development_charge';
            } elseif (in_array($rawPaymentType, ['booking', 'booking_money', 'booking money'])) {
                $paymentType = 'booking_money';
            } elseif (in_array($rawPaymentType, ['shop_service_charge', 'shop service charge'])) {
                $paymentType = 'shop_service_charge';
            } elseif (in_array($rawPaymentType, ['emi_due_settlement', 'emi due settlement'])) {
                $paymentType = 'emi_due_settlement';
            }

            $validPaymentTypes = ['booking_money', 'registration', 'emi', 'land_share', 'development_charge', 'emi_due_settlement', 'shop_service_charge'];
            if (!in_array($paymentType, $validPaymentTypes)) {
                $rowIssues[] = "payment_type '$rawPaymentType' is invalid (allowed: " . implode(', ', $validPaymentTypes) . ")";
            }

            // 2. Validate lead_number
            $leadPhone = trim($data['lead_number'] ?? '');
            $candidateLeadIds = [];
            if (empty($leadPhone)) {
                $rowIssues[] = "lead_number is empty";
            } else {
                if (isset($leadsCache[$leadPhone])) {
                    $candidateLeadIds = $leadsCache[$leadPhone];
                } else {
                    $cleanPhone = preg_replace('/[^0-9]/', '', $leadPhone);
                    $suffix = (strlen($cleanPhone) >= 10) ? substr($cleanPhone, -10) : $cleanPhone;
                    $likePattern = "%" . $suffix;
                    $stmt = $this->db->prepare("
                        SELECT id FROM leads 
                        WHERE (
                            phone = ? 
                            OR phone_number = ? 
                            OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', ''), '(', ''), ')', '') LIKE ? 
                            OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone_number, '-', ''), ' ', ''), '+', ''), '(', ''), ')', '') LIKE ?
                        ) AND deleted_at IS NULL
                    ");
                    $stmt->bind_param("ssss", $leadPhone, $leadPhone, $likePattern, $likePattern);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($r = $res->fetch_assoc()) {
                        $candidateLeadIds[] = (int)$r['id'];
                    }
                    $leadsCache[$leadPhone] = $candidateLeadIds;
                }

                if (empty($candidateLeadIds)) {
                    $rowIssues[] = "Lead not found for phone '$leadPhone'";
                }
            }

            // 3. Validate project_name
            $projectName = trim($data['project_name'] ?? '');
            $cleanProjectName = preg_replace('/\s+/', ' ', $projectName);
            $projectId = null;
            if (empty($projectName)) {
                $rowIssues[] = "project_name is empty";
            } else {
                if (isset($projectsCache[$cleanProjectName])) {
                    $projectId = $projectsCache[$cleanProjectName];
                } elseif (isset($projectsCache[$projectName])) {
                    $projectId = $projectsCache[$projectName];
                } else {
                    $stmt = $this->db->prepare("
                        SELECT id FROM projects 
                        WHERE (name = ? 
                           OR REPLACE(REPLACE(name, '  ', ' '), '  ', ' ') = ?)
                          AND deleted_at IS NULL
                        LIMIT 1
                    ");
                    $stmt->bind_param("ss", $projectName, $cleanProjectName);
                    $stmt->execute();
                    $res = $stmt->get_result()->fetch_assoc();
                    if ($res) {
                        $projectId = $res['id'];
                        $projectsCache[$cleanProjectName] = $projectId;
                        $projectsCache[$projectName] = $projectId;
                    } else {
                        $rowIssues[] = "Project not found '$projectName'";
                    }
                }
            }

            // 4. Validate flat_name
            $flatName = trim($data['flat_name'] ?? '');
            $cleanFlatName = preg_replace('/\s+/', ' ', $flatName);
            $flatId = null;
            if (empty($flatName)) {
                $rowIssues[] = "flat_name is empty";
            } else {
                if ($projectId) {
                    $flatCacheKey = $projectId . '_' . strtolower(trim($flatName));
                    if (isset($flatsCache[$flatCacheKey])) {
                        $flatId = $flatsCache[$flatCacheKey];
                    } else {
                        // 1. Try exact match on active flats first
                        $stmt = $this->db->prepare("SELECT id FROM flats WHERE project_id = ? AND flat_name = ? AND deleted_at IS NULL LIMIT 1");
                        $stmt->bind_param("is", $projectId, $flatName);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        if ($res) {
                            $flatId = $res['id'];
                            $flatsCache[$flatCacheKey] = $flatId;
                        } else {
                            // 2. Fallback to relaxed / cleaned match on active flats
                            $superCleanFlatName = str_replace([' ', '-'], '', $flatName);
                            $stmtLike = $this->db->prepare("
                                SELECT id FROM flats 
                                WHERE project_id = ? 
                                  AND (flat_name = ? OR REPLACE(REPLACE(flat_name, '  ', ' '), '  ', ' ') = ? OR REPLACE(REPLACE(flat_name, ' ', ''), '-', '') = ?) 
                                  AND deleted_at IS NULL
                                ORDER BY (flat_name = ?) DESC, id DESC
                                LIMIT 1
                            ");
                            $stmtLike->bind_param("issss", $projectId, $flatName, $cleanFlatName, $superCleanFlatName, $flatName);
                            $stmtLike->execute();
                            $resLike = $stmtLike->get_result()->fetch_assoc();
                            if ($resLike) {
                                $flatId = $resLike['id'];
                                $flatsCache[$flatCacheKey] = $flatId;
                            } else {
                                $rowIssues[] = "Flat not found '$flatName' in project '$projectName'";
                            }
                        }
                    }
                } else {
                    $rowIssues[] = "Cannot lookup flat — project not resolved";
                }
            }

            // 5. Validate booking_id
            $bookingId = null;
            $leadId = null;
            if (!empty($candidateLeadIds) && $projectId && $flatId) {
                if (count($candidateLeadIds) === 1) {
                    $leadId = $candidateLeadIds[0];
                } else {
                    $placeholders = implode(',', array_fill(0, count($candidateLeadIds), '?'));
                    $types = str_repeat('i', count($candidateLeadIds)) . 'ii';
                    $query = "SELECT lead_id FROM lead_bookings WHERE lead_id IN ($placeholders) AND project_id = ? AND flat_id = ? AND deleted_at IS NULL LIMIT 1";
                    $stmt = $this->db->prepare($query);
                    $bindParams = array_merge($candidateLeadIds, [$projectId, $flatId]);
                    $stmt->bind_param($types, ...$bindParams);
                    $stmt->execute();
                    $bRes = $stmt->get_result()->fetch_assoc();
                    if ($bRes) {
                        $leadId = (int)$bRes['lead_id'];
                    } else {
                        $leadId = $candidateLeadIds[0];
                    }
                }

                $stmt = $this->db->prepare("SELECT id FROM lead_bookings WHERE lead_id = ? AND project_id = ? AND flat_id = ? AND deleted_at IS NULL LIMIT 1");
                $stmt->bind_param("iii", $leadId, $projectId, $flatId);
                $stmt->execute();
                $bRes = $stmt->get_result()->fetch_assoc();
                if ($bRes) {
                    $bookingId = $bRes['id'];
                } else {
                    $rowIssues[] = "Booking not found for this Lead, Project, and Flat";
                }
            } else {
                $rowIssues[] = "Cannot lookup booking — missing lead, project, or flat";
            }

            // Log details for debugging
            $debugLogPath = dirname(dirname(__DIR__)) . '/app/logs/import_debug.log';
            $logMsg = sprintf(
                "[%s] VALIDATE - Row: %d | LeadPhone: %s | CleanPhone: %s | Candidates: %s | ProjectID: %s | FlatID: %s | LeadID: %s | BookingID: %s | Issues: %s\n",
                date('Y-m-d H:i:s'),
                $rowNum,
                $leadPhone,
                preg_replace('/[^0-9]/', '', $leadPhone),
                json_encode($candidateLeadIds),
                $projectId ?? 'NULL',
                $flatId ?? 'NULL',
                $leadId ?? 'NULL',
                $bookingId ?? 'NULL',
                implode(' | ', $rowIssues)
            );
            file_put_contents($debugLogPath, $logMsg, FILE_APPEND);

            // 6. Validate amount
            $rawAmount = trim($data['amount'] ?? '0');
            $cleanAmount = floatval(str_replace(',', '', $rawAmount));
            if ($cleanAmount <= 0) {
                $rowIssues[] = "amount must be greater than 0 (found '$rawAmount')";
            }

            // 7. Validate payment_method
            $rawMethod = trim($data['payment_method'] ?? '');
            if (!empty($rawMethod)) {
                $cleanMethod = strtolower($rawMethod);
                if ($cleanMethod === 'bank_transfer' || $cleanMethod === 'bank transfer' || $cleanMethod === 'transfer') {
                    $cleanMethod = 'bank';
                } elseif (in_array($cleanMethod, ['bkash', 'nagad', 'rocket', 'mobile_banking', 'mobile banking'])) {
                    $cleanMethod = 'online';
                } elseif ($cleanMethod === 'check') {
                    $cleanMethod = 'cheque';
                }
                
                if (!in_array($cleanMethod, ['cash', 'bank', 'online', 'cheque'])) {
                    $rowIssues[] = "payment_method '$rawMethod' is invalid (must be 'cash', 'bank', 'online', or 'cheque')";
                }
            }

            $results[] = [
                'row' => $rowNum,
                'valid' => empty($rowIssues),
                'error' => empty($rowIssues) ? '' : implode(' | ', $rowIssues),
                'data' => [
                    'lead_number' => $leadPhone,
                    'project_name' => $projectName,
                    'flat_name' => $flatName,
                    'payment_type' => $paymentType,
                    'amount' => $rawAmount,
                    'payment_method' => $rawMethod,
                    'payment_date' => $data['payment_date'] ?? '',
                    'company_bank_name' => $data['company_bank_name'] ?? '',
                    'verify_by' => $data['verify_by'] ?? ''
                ]
            ];
        }

        fclose($handle);

        echo json_encode([
            'success' => true,
            'rows' => $results
        ], JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }


    public function importCsvBulkPayments()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
            $file = $_FILES['csv_file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                setError("Upload failed or no file selected");
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            }

            $handle = fopen($file['tmp_name'], "r");
            if ($handle !== false) {
                $header = fgetcsv($handle, 1000, ",");
                if ($header) {
                    $header = array_map('trim', $header);
                    if (!empty($header[0])) {
                        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
                    }
                }

                $successCount = 0;
                $errorCount = 0;
                
                $leadsCache = [];
                $projectsCache = [];
                $flatsCache = [];

                $errors = [];
                $rowNum = 1;

                $cleanUtf8 = function($str) {
                    if ($str === null || $str === false) return '';
                    $str = str_replace("\xC2\xA0", ' ', (string)$str);
                    if (!mb_check_encoding($str, 'UTF-8')) {
                        $str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
                    }
                    return trim($str);
                };

                while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                    $rowNum++;
                    if (count(array_filter($row)) === 0) continue;
                    if (count($header) !== count($row)) {
                        $errors[] = "Row $rowNum: Column mismatch";
                        continue;
                    }

                    $data = array_combine($header, array_map($cleanUtf8, $row));
                    $rowIssues = []; // collect all issues for this row before skipping

                    // 1. Validate and normalize payment_type
                    $rawPaymentType = strtolower(trim($data['payment_type'] ?? ''));
                    $paymentType = $rawPaymentType;
                    if (in_array($rawPaymentType, ['landshare', 'land_share', 'land_share_amount', 'land share'])) {
                        $paymentType = 'land_share';
                    } elseif (in_array($rawPaymentType, ['development_charge', 'development charge', 'development'])) {
                        $paymentType = 'development_charge';
                    } elseif (in_array($rawPaymentType, ['booking', 'booking_money', 'booking money'])) {
                        $paymentType = 'booking_money';
                    } elseif (in_array($rawPaymentType, ['shop_service_charge', 'shop service charge'])) {
                        $paymentType = 'shop_service_charge';
                    } elseif (in_array($rawPaymentType, ['emi_due_settlement', 'emi due settlement'])) {
                        $paymentType = 'emi_due_settlement';
                    }

                    $validPaymentTypes = ['booking_money', 'registration', 'emi', 'land_share', 'development_charge', 'emi_due_settlement', 'shop_service_charge'];
                    if (!in_array($paymentType, $validPaymentTypes)) {
                        $rowIssues[] = "payment_type '$rawPaymentType' is invalid (allowed: " . implode(', ', $validPaymentTypes) . ")";
                    }
                    
                    // 1. Resolve lead_id
                    $leadPhone = trim($data['lead_number'] ?? '');
                    $candidateLeadIds = [];
                    if (empty($leadPhone)) {
                        $rowIssues[] = "lead_number is empty";
                    } else {
                        if (isset($leadsCache[$leadPhone])) {
                            $candidateLeadIds = $leadsCache[$leadPhone];
                        } else {
                            $cleanPhone = preg_replace('/[^0-9]/', '', $leadPhone);
                            $suffix = (strlen($cleanPhone) >= 10) ? substr($cleanPhone, -10) : $cleanPhone;
                            $likePattern = "%" . $suffix;
                            $stmt = $this->db->prepare("
                                SELECT id FROM leads 
                                WHERE (
                                    phone = ? 
                                    OR phone_number = ? 
                                    OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', ''), '(', ''), ')', '') LIKE ? 
                                    OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone_number, '-', ''), ' ', ''), '+', ''), '(', ''), ')', '') LIKE ?
                                ) AND deleted_at IS NULL
                            ");
                            $stmt->bind_param("ssss", $leadPhone, $leadPhone, $likePattern, $likePattern);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($r = $res->fetch_assoc()) {
                                $candidateLeadIds[] = (int)$r['id'];
                            }
                            $leadsCache[$leadPhone] = $candidateLeadIds;
                        }

                        if (empty($candidateLeadIds)) {
                            $rowIssues[] = "Lead not found for phone '$leadPhone'";
                        }
                    }

                    // 2. Resolve project_id
                    $projectName = trim($data['project_name'] ?? '');
                    $cleanProjectName = preg_replace('/\s+/', ' ', $projectName);
                    $projectId = null;
                    if (empty($projectName)) {
                        $rowIssues[] = "project_name is empty";
                    } else {
                        if (isset($projectsCache[$cleanProjectName])) {
                            $projectId = $projectsCache[$cleanProjectName];
                        } elseif (isset($projectsCache[$projectName])) {
                            $projectId = $projectsCache[$projectName];
                        } else {
                            $stmt = $this->db->prepare("
                        SELECT id FROM projects 
                        WHERE (name = ? 
                           OR REPLACE(REPLACE(name, '  ', ' '), '  ', ' ') = ?)
                          AND deleted_at IS NULL
                        LIMIT 1
                    ");
                    $stmt->bind_param("ss", $projectName, $cleanProjectName);
                    $stmt->execute();
                    $res = $stmt->get_result()->fetch_assoc();
                    if ($res) {
                        $projectId = $res['id'];
                        $projectsCache[$cleanProjectName] = $projectId;
                        $projectsCache[$projectName] = $projectId;
                    } else {
                        $rowIssues[] = "Project not found '$projectName'";
                    }
                }
            }

            // 3. Resolve flat_id
            $flatName = trim($data['flat_name'] ?? '');
            $cleanFlatName = preg_replace('/\s+/', ' ', $flatName);
            $flatId = null;
            if (empty($flatName)) {
                $rowIssues[] = "flat_name is empty";
            } else {
                if ($projectId) {
                    $flatCacheKey = $projectId . '_' . strtolower(trim($flatName));
                    if (isset($flatsCache[$flatCacheKey])) {
                        $flatId = $flatsCache[$flatCacheKey];
                    } else {
                        // 1. Try exact match on active flats first
                        $stmt = $this->db->prepare("SELECT id FROM flats WHERE project_id = ? AND flat_name = ? AND deleted_at IS NULL LIMIT 1");
                        $stmt->bind_param("is", $projectId, $flatName);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        if ($res) {
                            $flatId = $res['id'];
                            $flatsCache[$flatCacheKey] = $flatId;
                        } else {
                            // 2. Fallback to relaxed / cleaned match on active flats
                            $superCleanFlatName = str_replace([' ', '-'], '', $flatName);
                            $stmtLike = $this->db->prepare("
                                SELECT id FROM flats 
                                WHERE project_id = ? 
                                  AND (flat_name = ? OR REPLACE(REPLACE(flat_name, '  ', ' '), '  ', ' ') = ? OR REPLACE(REPLACE(flat_name, ' ', ''), '-', '') = ?) 
                                  AND deleted_at IS NULL
                                ORDER BY (flat_name = ?) DESC, id DESC
                                LIMIT 1
                            ");
                            $stmtLike->bind_param("issss", $projectId, $flatName, $cleanFlatName, $superCleanFlatName, $flatName);
                            $stmtLike->execute();
                            $resLike = $stmtLike->get_result()->fetch_assoc();
                            if ($resLike) {
                                $flatId = $resLike['id'];
                                $flatsCache[$flatCacheKey] = $flatId;
                            } else {
                                $rowIssues[] = "Flat not found '$flatName' in project '$projectName'";
                            }
                        }
                    }
                } else {
                    $rowIssues[] = "Cannot lookup flat — project not resolved";
                }
            }

            // 4. Resolve booking_id
            $bookingId = null;
            $leadId = null;
            if (!empty($candidateLeadIds) && $projectId && $flatId) {
                if (count($candidateLeadIds) === 1) {
                    $leadId = $candidateLeadIds[0];
                } else {
                    $placeholders = implode(',', array_fill(0, count($candidateLeadIds), '?'));
                    $types = str_repeat('i', count($candidateLeadIds)) . 'ii';
                    $query = "SELECT lead_id FROM lead_bookings WHERE lead_id IN ($placeholders) AND project_id = ? AND flat_id = ? AND deleted_at IS NULL LIMIT 1";
                    $stmt = $this->db->prepare($query);
                    $bindParams = array_merge($candidateLeadIds, [$projectId, $flatId]);
                    $stmt->bind_param($types, ...$bindParams);
                    $stmt->execute();
                    $bRes = $stmt->get_result()->fetch_assoc();
                    if ($bRes) {
                        $leadId = (int)$bRes['lead_id'];
                    } else {
                        $leadId = $candidateLeadIds[0];
                    }
                }

                $stmt = $this->db->prepare("SELECT id FROM lead_bookings WHERE lead_id = ? AND project_id = ? AND flat_id = ? AND deleted_at IS NULL LIMIT 1");
                $stmt->bind_param("iii", $leadId, $projectId, $flatId);
                $stmt->execute();
                $bRes = $stmt->get_result()->fetch_assoc();
                
                if ($bRes) {
                    $bookingId = $bRes['id'];
                } else {
                    $rowIssues[] = "Booking not found for this Lead, Project, and Flat";
                }
            } else {
                $rowIssues[] = "Cannot lookup booking — missing lead, project, or flat";
            }

                    // Log details for debugging
                    $debugLogPath = dirname(dirname(__DIR__)) . '/app/logs/import_debug.log';
                    $logMsg = sprintf(
                        "[%s] IMPORT - Row: %d | LeadPhone: %s | CleanPhone: %s | Candidates: %s | ProjectID: %s | FlatID: %s | LeadID: %s | BookingID: %s | Issues: %s\n",
                        date('Y-m-d H:i:s'),
                        $rowNum,
                        $leadPhone,
                        preg_replace('/[^0-9]/', '', $leadPhone),
                        json_encode($candidateLeadIds),
                        $projectId ?? 'NULL',
                        $flatId ?? 'NULL',
                        $leadId ?? 'NULL',
                        $bookingId ?? 'NULL',
                        implode(' | ', $rowIssues)
                    );
                    file_put_contents($debugLogPath, $logMsg, FILE_APPEND);

                    // Validate & Normalize payment_method
                    $rawMethod = trim($data['payment_method'] ?? '');
                    $cleanMethod = 'cash';
                    if (!empty($rawMethod)) {
                        $cleanMethod = strtolower($rawMethod);
                        if ($cleanMethod === 'bank_transfer' || $cleanMethod === 'bank transfer' || $cleanMethod === 'transfer') {
                            $cleanMethod = 'bank';
                        } elseif (in_array($cleanMethod, ['bkash', 'nagad', 'rocket', 'mobile_banking', 'mobile banking'])) {
                            $cleanMethod = 'online';
                        } elseif ($cleanMethod === 'check') {
                            $cleanMethod = 'cheque';
                        }
                        
                        if (!in_array($cleanMethod, ['cash', 'bank', 'online', 'cheque'])) {
                            $rowIssues[] = "payment_method '$rawMethod' is invalid (must be 'cash', 'bank', 'online', or 'cheque')";
                        }
                    }

                    if (!empty($rowIssues)) {
                        $errors[] = "Row $rowNum: " . implode(' | ', $rowIssues);
                        continue;
                    }

                    // Date formatting for payment_date
                    $paymentDate = null;
                    if (!empty($data['payment_date'])) {
                        $dateStr = str_replace('/', '-', $data['payment_date']);
                        $timestamp = strtotime($dateStr);
                        if ($timestamp !== false) {
                            $paymentDate = date('Y-m-d', $timestamp);
                        }
                    }
                    if (!$paymentDate) $paymentDate = date('Y-m-d');

                    // Prepare payment data
                    $rawAmount = trim($data['amount'] ?? '0');
                    $cleanAmount = floatval(str_replace(',', '', $rawAmount));

                    // Resolve Company Bank
                    $companyBankId = null;
                    $csvCompanyBankName = trim($data['company_bank_name'] ?? '');
                    if (!empty($csvCompanyBankName)) {
                        $cleanBankName = preg_replace('/\s+/', '', strtolower($csvCompanyBankName));
                        $stmtBank = $this->db->prepare("SELECT id FROM company_bank_accounts WHERE REPLACE(LOWER(bank_name), ' ', '') = ? LIMIT 1");
                        $stmtBank->bind_param("s", $cleanBankName);
                        $stmtBank->execute();
                        $resBank = $stmtBank->get_result()->fetch_assoc();
                        if ($resBank) {
                            $companyBankId = $resBank['id'];
                        } else {
                            // Insert new bank
                            $stmtInsBank = $this->db->prepare("INSERT INTO company_bank_accounts (bank_name, status, created_by) VALUES (?, 'active', ?)");
                            $createdBy = $_SESSION['user']['id'] ?? 1;
                            $stmtInsBank->bind_param("si", $csvCompanyBankName, $createdBy);
                            $stmtInsBank->execute();
                            $companyBankId = $stmtInsBank->insert_id;
                        }
                    }

                    // Resolve verified_by (user email)
                    $verifiedBy = $_SESSION['user']['id'] ?? null;
                    $csvVerifyEmail = trim($data['verify_by'] ?? '');
                    if (!empty($csvVerifyEmail)) {
                        $stmtUsr = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                        $stmtUsr->bind_param("s", $csvVerifyEmail);
                        $stmtUsr->execute();
                        $resUsr = $stmtUsr->get_result()->fetch_assoc();
                        if ($resUsr) {
                            $verifiedBy = $resUsr['id'];
                        }
                    }
                    
                    // Support custom verification status if provided
                    $verificationStatus = trim($data['verification_status'] ?? 'verified');
                    if (empty($verificationStatus) || !in_array(strtolower($verificationStatus), ['pending', 'unverified', 'verified'])) {
                        $verificationStatus = 'verified';
                    }

                    $paymentData = [
                        'booking_id' => $bookingId,
                        'payment_type' => $paymentType,
                        'amount' => $cleanAmount,
                        'payment_method' => $cleanMethod,
                        'payment_status' => 'pending', // Will be updated
                        'payment_date' => $paymentDate,
                        'total_emi_no' => $data['total_emi_no'] ?? null,
                        'transaction_ref' => $data['transaction_ref'] ?? null,
                        'bank_name' => $data['bank_name'] ?? null,
                        'bank_id' => $companyBankId,
                        'bank_account_no' => null, // or from CSV if added
                        'cheque_no' => $data['cheque_no'] ?? null,
                        'online_type' => $data['online_type'] ?? null,
                        'created_by' => $_SESSION['user']['id'] ?? null,
                        'verification_status' => $verificationStatus,
                        'verified_by' => $verifiedBy,
                        'attachment_image' => $data['attachment_image'] ?? null
                    ];

                    try {
                        $paymentId = $this->paymentModel->createFromCsvBulk($paymentData);
                        
                        // Recalculate Booking Status
                        $newBookingStatus = $this->updateBookingPaymentStatus($bookingId);

                        // Set Payment Status Map
                        $paymentStatusMap = [
                            'paid'    => 'paid',
                            'partial' => 'partially_paid',
                            'unpaid'  => 'pending',
                        ];
                        $newPaymentStatus = $paymentStatusMap[$newBookingStatus] ?? 'paid';

                        // Update Payment Status
                        $stmt2 = $this->db->prepare("UPDATE lead_payments SET payment_status = ? WHERE id = ?");
                        $stmt2->bind_param('si', $newPaymentStatus, $paymentId);
                        $stmt2->execute();

                        // Handle Flat Booking if fully paid
                        if ($newBookingStatus === 'paid') {
                            $this->handleFlatBooking($bookingId);
                        }

                        $successCount++;
                    } catch (Exception $e) {
                        $errors[] = "Row $rowNum: DB Error - " . $e->getMessage();
                        error_log("CSV Import Error: " . $e->getMessage());
                        continue;
                    }
                }
                fclose($handle);

                $totalRows = $rowNum - 1; // exclude header
                $failedCount = count($errors);

                if ($successCount > 0) {
                    $msg = " $successCount / $totalRows Payments imported successfully.";
                    if ($failedCount > 0) {
                        $msg .= "  $failedCount row(s) skipped: " . implode(" | ", array_slice($errors, 0, 5));
                        if ($failedCount > 5) $msg .= " ... and " . ($failedCount - 5) . " more.";
                    }
                    setSuccess($msg);
                } else {
                    $msg = " 0 / $totalRows Payments imported. Errors: " . implode(" | ", array_slice($errors, 0, 5));
                    if ($failedCount > 5) $msg .= " ... and " . ($failedCount - 5) . " more.";
                    setError($msg);
                }

                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            } else {
                setError("Unable to read CSV file");
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            }
        }
    }

    public function invoice($payment_id)
    {
        if (!$payment_id) {
            die("Invalid payment ID");
        }

        // Payment + Booking + Lead + Project info
        $invoice = $this->paymentModel->getInvoiceData($payment_id);

        if (!$invoice) {
            die("Invoice not found");
        }

        // Fetch all payments for this booking to show payment history (step by step)
        $all_payments = $this->paymentModel->getByBooking($invoice['booking_id']);

        $this->view("sales-developments/payments/payment-invoice", [
            "invoice" => $invoice,
            "all_payments" => $all_payments,
            "current_route" => 'payment-booking-invoice',
            "page_title" => "Payment Invoice",
        ], false);
    }




    public function combinePayments() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $bookingId = (int)($_GET['booking_id'] ?? 0);
        $ref = $_GET['ref'] ?? '';
        if ($bookingId && $ref) {
            $batchId = uniqid('batch_manual_');
            $stmt = $this->db->prepare("UPDATE lead_payments SET batch_id = ? WHERE booking_id = ? AND transaction_ref = ? AND (batch_id IS NULL OR batch_id = '')");
            $stmt->bind_param("sis", $batchId, $bookingId, $ref);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                setSuccess("Receipts successfully combined for reference: " . htmlspecialchars($ref));
            } else {
                setSuccess("Receipts are already combined or no changes were needed.");
            }
        }
        header("Location: {$GLOBALS['base_url']}/payments-booking/" . $bookingId);
        exit;
    }

    public function update()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = (int)($_POST['payment_id'] ?? $_POST['id'] ?? 0);
        $bookingId = (int)($_POST['booking_id'] ?? 0);

        if (!$id || !$bookingId) {
            setError("Invalid payment or booking reference!");
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/onboard-leads"));
            exit;
        }

        $currentUserId = (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
        $currentUserRoleId = (int)($_SESSION['user']['role_id'] ?? $_SESSION['user']['primary_role_id'] ?? 0);
        $secondaryRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $userRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $userName = strtolower(trim($_SESSION['user']['full_name'] ?? $_SESSION['user']['user_name'] ?? ''));

        // Management roles: Admin (1), Super Admin (3), Admin & Operation Head (20), Admin Operation (32), Chief Officer (33), SEAL CEO (35) or matching names
        $isManagement = in_array($currentUserRoleId, [1, 3, 20, 32, 33, 35]) ||
                        in_array($secondaryRoleId, [1, 3, 20, 32, 33, 35]) ||
                        (strpos($userRoleName, 'admin') !== false) ||
                        (strpos($userRoleName, 'management') !== false) ||
                        (strpos($userRoleName, 'ceo') !== false) ||
                        (strpos($userRoleName, 'chief') !== false) ||
                        (strpos($userName, 'management') !== false);

        // Sales Head roles: Sales Team Head (9), Sales Manager (5) or matching names
        $isSalesHead = in_array($currentUserRoleId, [5, 9]) ||
                       in_array($secondaryRoleId, [5, 9]) ||
                       (strpos($userRoleName, 'sales') !== false && (strpos($userRoleName, 'head') !== false || strpos($userRoleName, 'manager') !== false));

        $booking = $this->bookingModel->getById($bookingId);
        $existing = $this->paymentModel->find($id);

        if (!$existing || !$booking) {
            setError("Invalid payment or booking record!");
            header("Location: {$GLOBALS['base_url']}/payments-booking/{$bookingId}");
            exit;
        }

        $isAssignedSalesPerson = (
            (!empty($booking['sales_by_id']) && (int)$booking['sales_by_id'] === $currentUserId) ||
            (!empty($booking['assigned_id']) && (int)$booking['assigned_id'] === $currentUserId) ||
            (!empty($existing['created_by']) && (int)$existing['created_by'] === $currentUserId)
        );

        $isVerified = ($existing['verification_status'] === 'verified');
        $allowVerified = false;

        if ($isVerified) {
            // Once verified by Accounts: Sales Person CANNOT edit. Only Management or Sales Head can edit.
            if (!$isManagement && !$isSalesHead) {
                setError("Cannot edit payment because it has already been verified by Accounts! Only Management or Sales Head can edit verified payments.");
                header("Location: {$GLOBALS['base_url']}/payments-booking/{$bookingId}");
                exit;
            }

            $bankName = !empty($_POST['bank_name']) ? trim($_POST['bank_name']) : null;
            if (in_array($bankName, ['Other Bank', 'Other Country / Foreign Bank', 'Foreign Bank', 'Other Country Bank']) && !empty($_POST['custom_bank_name'])) {
                $bankName = trim($_POST['custom_bank_name']);
            }

            $paymentMethod = $_POST['payment_method'] ?? ($existing['payment_method'] ?? 'cash');

            // Sanitize bank/cheque/online fields based on payment method
            $bankId = null;
            $clientBankName = null;
            $bankAccountNo = null;
            $chequeNo = null;
            $onlineType = null;

            if ($paymentMethod === 'bank') {
                $bankId = !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null;
                $clientBankName = $bankName;
                $bankAccountNo = !empty($_POST['bank_account_no']) ? trim($_POST['bank_account_no']) : null;
            } elseif ($paymentMethod === 'cheque') {
                $bankId = !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null;
                $clientBankName = $bankName;
                $chequeNo = !empty($_POST['cheque_no']) ? trim($_POST['cheque_no']) : null;
            } elseif ($paymentMethod === 'online') {
                $bankId = !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null;
                $onlineType = !empty($_POST['online_type']) ? trim($_POST['online_type']) : null;
            }

            // Management can edit EVERYTHING (including amount, payment_type, payment_status, total_emi_no).
            // Sales Head can edit Method, Date, Bank, Cheque No, Ref, Online Type, Attachments (Amount/Type/Status locked).
            $amount = $isManagement ? floatval($_POST['amount'] ?? $existing['amount']) : floatval($existing['amount']);
            $paymentType = ($isManagement && !empty($_POST['payment_type'])) ? trim($_POST['payment_type']) : $existing['payment_type'];
            $paymentStatus = ($isManagement && !empty($_POST['payment_status'])) ? trim($_POST['payment_status']) : $existing['payment_status'];
            $totalEmiNo = ($isManagement && isset($_POST['total_emi_no']) && $_POST['total_emi_no'] !== '') ? (int)$_POST['total_emi_no'] : ($existing['total_emi_no'] ?? null);

            $data = [
                'id'              => $id,
                'payment_type'    => $paymentType,
                'amount'          => $amount,
                'payment_method'  => $paymentMethod,
                'payment_status'  => $paymentStatus,
                'payment_date'    => $_POST['payment_date'] ?? $existing['payment_date'],
                'total_emi_no'    => $totalEmiNo,
                'transaction_ref' => $_POST['transaction_ref'] ?? null,
                'bank_id'         => $bankId,
                'bank_name'       => $clientBankName,
                'bank_account_no' => $bankAccountNo,
                'cheque_no'       => $chequeNo,
                'online_type'     => $onlineType,
                'updated_by'      => $currentUserId,
            ];

            $allowVerified = true;
        } else {
            // Unverified: User must be Management, Sales Head, OR the Assigned Sales Person
            if (!$isManagement && !$isSalesHead && !$isAssignedSalesPerson) {
                setError("You do not have permission to edit this payment record.");
                header("Location: {$GLOBALS['base_url']}/payments-booking/{$bookingId}");
                exit;
            }

            $bankName = !empty($_POST['bank_name']) ? trim($_POST['bank_name']) : null;
            if (in_array($bankName, ['Other Bank', 'Other Country / Foreign Bank', 'Foreign Bank', 'Other Country Bank']) && !empty($_POST['custom_bank_name'])) {
                $bankName = trim($_POST['custom_bank_name']);
            }

            $paymentMethod = $_POST['payment_method'] ?? 'cash';

            // Sanitize bank/cheque/online fields based on payment method
            $bankId = null;
            $clientBankName = null;
            $bankAccountNo = null;
            $chequeNo = null;
            $onlineType = null;

            if ($paymentMethod === 'bank') {
                $bankId = !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null;
                $clientBankName = $bankName;
                $bankAccountNo = !empty($_POST['bank_account_no']) ? trim($_POST['bank_account_no']) : null;
            } elseif ($paymentMethod === 'cheque') {
                $bankId = !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null;
                $clientBankName = $bankName;
                $chequeNo = !empty($_POST['cheque_no']) ? trim($_POST['cheque_no']) : null;
            } elseif ($paymentMethod === 'online') {
                $bankId = !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null;
                $onlineType = !empty($_POST['online_type']) ? trim($_POST['online_type']) : null;
            }

            $data = [
                'id'              => $id,
                'payment_type'    => !empty($_POST['payment_type']) ? trim($_POST['payment_type']) : ($existing['payment_type'] ?? 'booking_money'),
                'amount'          => floatval($_POST['amount'] ?? $existing['amount']),
                'payment_method'  => $paymentMethod,
                'payment_status'  => !empty($_POST['payment_status']) ? trim($_POST['payment_status']) : ($existing['payment_status'] ?? 'pending'),
                'payment_date'    => $_POST['payment_date'] ?? date('Y-m-d'),
                'total_emi_no'    => isset($_POST['total_emi_no']) && $_POST['total_emi_no'] !== '' ? (int)$_POST['total_emi_no'] : ($existing['total_emi_no'] ?? null),
                'transaction_ref' => $_POST['transaction_ref'] ?? null,
                'bank_id'         => $bankId,
                'bank_name'       => $clientBankName,
                'bank_account_no' => $bankAccountNo,
                'cheque_no'       => $chequeNo,
                'online_type'     => $onlineType,
                'updated_by'      => $currentUserId,
            ];

            $allowVerified = false;
        }

        // Handle attachment uploads (1 to 4) - Supports JPG, PNG, WEBP, GIF, PDF
        foreach (['attachment_image', 'attachment_image2', 'attachment_image3', 'attachment_image4'] as $attField) {
            if (isset($_FILES[$attField]) && $_FILES[$attField]['error'] === UPLOAD_ERR_OK) {
                $uploadedFileName = $this->uploadPaymentFile($attField);
                if ($uploadedFileName) {
                    $data[$attField] = $uploadedFileName;
                }
            }
        }

        if ($this->paymentModel->updatePayment($data, $allowVerified)) {
            // Also sync total_emi_no to lead_bookings if updated
            if (!empty($data['total_emi_no'])) {
                $stmtEmi = $this->db->prepare("UPDATE lead_bookings SET total_emi_no = ?, emi_updated_by = ?, emi_updated_at = NOW() WHERE id = ?");
                if ($stmtEmi) {
                    $stmtEmi->bind_param("iii", $data['total_emi_no'], $currentUserId, $bookingId);
                    $stmtEmi->execute();
                    $stmtEmi->close();
                }
            }

            $this->updateBookingPaymentStatus($bookingId);
            setSuccess("Payment record updated successfully!");
        } else {
            setError("Failed to update payment record!");
        }

        header("Location: {$GLOBALS['base_url']}/payments-booking/{$bookingId}");
        exit;
    }

    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $paymentId = (int)$id;

        if (!$paymentId) {
            setError("Invalid payment reference!");
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/onboard-leads"));
            exit;
        }

        $payment = $this->paymentModel->find($paymentId);
        if (!$payment) {
            setError("Payment record not found!");
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/onboard-leads"));
            exit;
        }

        $bookingId = (int)($payment['booking_id'] ?? 0);

        if ($this->paymentModel->isPaymentVerified($paymentId)) {
            setError("Cannot delete payment because it has already been verified by Accounts!");
            header("Location: {$GLOBALS['base_url']}/payments-booking/{$bookingId}");
            exit;
        }

        $currentUserId = (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
        $currentUserRoleId = (int)($_SESSION['user']['role_id'] ?? $_SESSION['user']['primary_role_id'] ?? 0);
        $secondaryRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $userRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $userName = strtolower(trim($_SESSION['user']['full_name'] ?? $_SESSION['user']['user_name'] ?? ''));

        $isManagement = in_array($currentUserRoleId, [1, 3, 20, 32, 33, 35]) ||
                        in_array($secondaryRoleId, [1, 3, 20, 32, 33, 35]) ||
                        (strpos($userRoleName, 'admin') !== false) ||
                        (strpos($userRoleName, 'management') !== false) ||
                        (strpos($userRoleName, 'ceo') !== false) ||
                        (strpos($userRoleName, 'chief') !== false) ||
                        (strpos($userName, 'management') !== false);

        $isSalesHead = in_array($currentUserRoleId, [5, 9]) ||
                       in_array($secondaryRoleId, [5, 9]) ||
                       (strpos($userRoleName, 'sales') !== false && (strpos($userRoleName, 'head') !== false || strpos($userRoleName, 'manager') !== false));

        $booking = $this->bookingModel->getById($bookingId);
        $isAssignedSalesPerson = (
            (!empty($booking['sales_by_id']) && (int)$booking['sales_by_id'] === $currentUserId) ||
            (!empty($booking['assigned_id']) && (int)$booking['assigned_id'] === $currentUserId) ||
            (!empty($payment['created_by']) && (int)$payment['created_by'] === $currentUserId)
        );

        if (!$isManagement && !$isSalesHead && !$isAssignedSalesPerson) {
            setError("You do not have permission to delete this payment record.");
            header("Location: {$GLOBALS['base_url']}/payments-booking/{$bookingId}");
            exit;
        }

        if ($this->paymentModel->deletePayment($paymentId)) {
            $this->updateBookingPaymentStatus($bookingId);
            setSuccess("Payment record deleted successfully!");
        } else {
            setError("Failed to delete payment record!");
        }

        header("Location: {$GLOBALS['base_url']}/payments-booking/{$bookingId}");
        exit;
    }

    /**
     * Upload a payment document (JPG, PNG, WEBP, GIF, PDF) into public/uploads/payments/
     * and mirror to staging/production if both directories exist.
     */
    protected function uploadPaymentFile(string $inputName): ?string
    {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedExts = ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp', 'pdf'];
        $origName = basename(trim((string)$_FILES[$inputName]['name']));
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if ($ext === 'jfif') {
            $ext = 'jpg';
        }

        if (!in_array($ext, $allowedExts, true)) {
            return null;
        }

        $tmpName = $_FILES[$inputName]['tmp_name'];
        if (empty($tmpName) || !is_uploaded_file($tmpName)) {
            return null;
        }

        // Security: abort if file contains PHP tags
        $head = (string)@file_get_contents($tmpName, false, null, 0, 1024);
        if (stripos($head, '<?php') !== false || stripos($head, '<?=') !== false) {
            return null;
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/payments/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid('payments_') . '.' . $ext;
        $destPath = $uploadDir . $fileName;

        if (@move_uploaded_file($tmpName, $destPath)) {
            @chmod($destPath, 0644);

            // Mirror between VPS Staging & Production uploads if both coexist
            foreach (['/var/www/html/TPHL-ERP/public/uploads', '/var/www/staging/TPHL-ERP/public/uploads'] as $vpsUploadsBase) {
                if (@is_dir($vpsUploadsBase) && @is_writable($vpsUploadsBase)) {
                    $mirrorDir = $vpsUploadsBase . '/payments/';
                    if (rtrim(str_replace('\\', '/', $mirrorDir), '/') !== rtrim(str_replace('\\', '/', $uploadDir), '/')) {
                        if (!@is_dir($mirrorDir)) {
                            @mkdir($mirrorDir, 0755, true);
                        }
                        if (@is_dir($mirrorDir) && @is_writable($mirrorDir)) {
                            @copy($destPath, $mirrorDir . $fileName);
                            @chmod($mirrorDir . $fileName, 0644);
                        }
                    }
                }
            }

            return $fileName;
        }

        return null;
    }
}
