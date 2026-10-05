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

        // All payments for this booking
        $payments = $this->paymentModel->getByBooking($booking_id);

        // Check if booking payment already done
        // $existingBookingPayment = $this->paymentModel->getByBookingType($booking_id, 'booking_money');

        // // Form show/hide
        // $showBookingForm = empty($existingBookingPayment); // true = show, false = hide

        $this->view("sales-developments/payments/payment-bookings", [
            "booking" => $booking,
            "payments" => $payments,
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

        // Handle attachment upload
        if (isset($_FILES['attachment_image']) && $_FILES['attachment_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedFileName = uploadImage('attachment_image', 'payments');
            if ($uploadedFileName) {
                $data['attachment_image'] = $uploadedFileName;
            }
        }
        if (isset($_FILES['attachment_image2']) && $_FILES['attachment_image2']['error'] === UPLOAD_ERR_OK) {
            $uploadedFileName2 = uploadImage('attachment_image2', 'payments');
            if ($uploadedFileName2) {
                $data['attachment_image2'] = $uploadedFileName2;
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

        $inputAmount = (float)$data['amount'];
        $created = null;
        $createdRegistration = null;

        if ($data['payment_type'] === 'booking_money') {
            $finalAmount = !empty($bookingLead['approved_amount'])
                ? (float)$bookingLead['agreement_value'] - (float)$bookingLead['approved_amount']
                : (float)$bookingLead['agreement_value'];

            if ($finalAmount <= 0) {
                $finalAmount = (float)$bookingLead['agreement_value'];
            }

            $stmt = $this->db->prepare("SELECT SUM(amount) AS paid FROM lead_payments WHERE booking_id = ? AND payment_type = 'booking_money' AND verification_status IN ('verified', 'pending')");
            $stmt->bind_param("i", $data['booking_id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $booking_paid = (float)($row['paid'] ?? 0);
            $remainingBookingMoney = $finalAmount - $booking_paid;

            if ($inputAmount > $remainingBookingMoney && $remainingBookingMoney > 0) {
                // Split payment
                $batchId = uniqid('batch_');
                
                $data['amount'] = $remainingBookingMoney;
                $data['batch_id'] = $batchId;
                $created = $this->paymentModel->create($data);

                $dataReg = $data;
                $dataReg['amount'] = $inputAmount - $remainingBookingMoney;
                $dataReg['payment_type'] = 'registration';
                $dataReg['batch_id'] = $batchId;
                $createdRegistration = $this->paymentModel->create($dataReg);
            } elseif ($remainingBookingMoney <= 0) {
                $data['payment_type'] = 'registration';
                $created = $this->paymentModel->create($data);
            } else {
                $created = $this->paymentModel->create($data);
            }
        } else {
            $created = $this->paymentModel->create($data);
        }

        if (!empty($created)) {
            if ($sendSms) {
                // Determine if a split happened and we should use the total input amount
                $smsAmount = $inputAmount;
                $smsPaymentType = ucfirst(str_replace('_', ' ', $data['payment_type'] ?? 'payment'));
                if (!empty($createdRegistration)) {
                    $smsPaymentType = 'Booking & Registration Money';
                }

                // Trigger 1: Send Payment Received SMS
                $smsResult = $smsService->sendPaymentReceivedSms($created, $isTestingMode, $smsAmount, $smsPaymentType);

                if ($isTestingMode) {
                    setSuccess("Payment created successfully! (Testing Mode: SMS Skipped)");
                } elseif (!empty($smsResult['success'])) {
                    setSuccess("Payment created successfully & SMS dispatched to client (" . htmlspecialchars($clientPhone ?? '') . ")!");
                } else {
                    $errMsg = !empty($smsResult['response']) ? $smsResult['response'] : ($smsResult['message'] ?? 'Gateway Connection Error');
                    setError("Payment created, BUT SMS dispatch failed for (" . htmlspecialchars($clientPhone ?? '') . "): " . htmlspecialchars($errMsg));
                }
            } else {
                setSuccess("Payment created successfully! (SMS Notification Disabled)");
            }
        } else {
            setError("Failed to create payment!");
        }

        if (!empty($created) && !$isTestingMode) {
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
        $discountApproved = !empty($booking['approved_amount']) ? (float)$booking['approved_amount'] : 0;
        
        $finalAmount = $agreementValue - $discountApproved;
        
        if ($finalAmount <= 0) {
            $finalAmount = $agreementValue;
        }

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

        // Step 3: Update THIS payment's payment_status based on booking result
        //   - If booking is now 'paid'     → this payment = 'paid'
        //   - If booking is 'partial'      → this payment = 'partially_paid'
        //   - If booking is 'unpaid'       → this payment stays 'pending' (edge case)
        $paymentStatusMap = [
            'paid'    => 'paid',
            'partial' => 'partially_paid',
            'unpaid'  => 'pending',
        ];
        $newPaymentStatus = $paymentStatusMap[$newBookingStatus] ?? 'paid';

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
        require_once dirname(__DIR__) . '/services/SmsService.php';
        $smsService = new SmsService($this->db);
        $smsService->sendPaymentVerifiedSms($id);

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
                    $msg = "✅ $successCount / $totalRows Payments imported successfully.";
                    if ($failedCount > 0) {
                        $msg .= " ⚠️ $failedCount row(s) skipped: " . implode(" | ", array_slice($errors, 0, 5));
                        if ($failedCount > 5) $msg .= " ... and " . ($failedCount - 5) . " more.";
                    }
                    setSuccess($msg);
                } else {
                    $msg = "❌ 0 / $totalRows Payments imported. Errors: " . implode(" | ", array_slice($errors, 0, 5));
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

        $userRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $isSalesHead = ((strpos($userRoleName, 'sales') !== false && strpos($userRoleName, 'head') !== false) || strpos($userRoleName, 'admin') !== false || $_SESSION['user']['role_id'] == 1);
        $isVerified = $this->paymentModel->isPaymentVerified($id);

        if ($isVerified) {
            if (!$isSalesHead) {
                setError("Cannot edit payment because it has already been verified by Accounts!");
                header("Location: {$GLOBALS['base_url']}/payments-booking/{$bookingId}");
                exit;
            }
            
            // SECURITY: If verified, ONLY allow updating payment_date
            // Fetch existing data so we don't overwrite with user input
            $stmt = $this->db->prepare("SELECT * FROM lead_payments WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            $data = [
                'id'              => $id,
                'amount'          => floatval($existing['amount']),
                'payment_method'  => $existing['payment_method'],
                'payment_date'    => $_POST['payment_date'] ?? $existing['payment_date'], // The only field allowed to change
                'transaction_ref' => $existing['transaction_ref'],
                'bank_id'         => $existing['bank_id'],
                'bank_name'       => $existing['bank_name'],
                'bank_account_no' => $existing['bank_account_no'],
                'cheque_no'       => $existing['cheque_no'],
                'online_type'     => $existing['online_type'],
            ];
        } else {
            $bankName = !empty($_POST['bank_name']) ? trim($_POST['bank_name']) : null;
            if (in_array($bankName, ['Other Bank', 'Other Country / Foreign Bank', 'Foreign Bank', 'Other Country Bank']) && !empty($_POST['custom_bank_name'])) {
                $bankName = trim($_POST['custom_bank_name']);
            }

            $data = [
                'id'              => $id,
                'amount'          => floatval($_POST['amount'] ?? 0),
                'payment_method'  => $_POST['payment_method'] ?? 'cash',
                'payment_date'    => $_POST['payment_date'] ?? date('Y-m-d'),
                'transaction_ref' => $_POST['transaction_ref'] ?? null,
                'bank_id'         => !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null,
                'bank_name'       => $bankName,
                'bank_account_no' => $_POST['bank_account_no'] ?? null,
                'cheque_no'       => $_POST['cheque_no'] ?? null,
                'online_type'     => $_POST['online_type'] ?? null,
            ];
        }

        // Handle attachment upload if new file provided
        if (isset($_FILES['attachment_image']) && $_FILES['attachment_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedFileName = uploadImage('attachment_image', 'payments');
            if ($uploadedFileName) {
                $data['attachment_image'] = $uploadedFileName;
            }
        }

        if ($this->paymentModel->updatePayment($data)) {
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

        if ($this->paymentModel->deletePayment($paymentId)) {
            $this->updateBookingPaymentStatus($bookingId);
            setSuccess("Payment record deleted successfully!");
        } else {
            setError("Failed to delete payment record!");
        }

        header("Location: {$GLOBALS['base_url']}/payments-booking/{$bookingId}");
        exit;
    }
}
