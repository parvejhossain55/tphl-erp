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

        $data = [
            'booking_id'          => $_POST['booking_id'] ?? 0,
            'payment_type'        => $_POST['payment_type'] ?? 'booking_money',
            'amount'              => floatval($_POST['amount'] ?? 0),
            'payment_method'      => $_POST['payment_method'] ?? 'cash',
            'payment_status'      => 'pending', // always pending — verified by accounts before becoming paid
            'payment_date'        => $_POST['payment_date'] ?? date('Y-m-d'),
            'transaction_ref'     => $_POST['transaction_ref'] ?? null,
            'bank_name'           => $_POST['bank_name'] ?? null,
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

        //  VALIDATION
        if (!$data['booking_id'] || !$data['amount'] || !$data['payment_type']) {
            setError("Required data missing!");
            header("Location: {$GLOBALS['base_url']}/payments-booking/" . ($data['booking_id'] ?? 0));
            exit;
        }

        //  IMPORTANT: ekhane kono flat booking logic thakbe na

        $created = $this->paymentModel->create($data);

        if ($created !== false) {
            setSuccess("Payment created successfully! Waiting for verification.");
        } else {
            setError("Failed to create payment!");
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

        // lead update
        $stmt3 = $this->db->prepare("
        UPDATE leads l
        INNER JOIN lead_bookings b ON l.id = b.lead_id
        SET l.last_lead_action = 'booked'
        WHERE b.id = ?
    ");
        $stmt3->bind_param("i", $bookingId);
        $stmt3->execute();
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

        setSuccess("Payment verified! Booking status: " . ucfirst($newBookingStatus));
        header("Location: " . $_SERVER['HTTP_REFERER']);
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
                $header = array_map('trim', $header);

                $successCount = 0;
                $errorCount = 0;
                
                $leadsCache = [];
                $projectsCache = [];
                $flatsCache = [];

                $errors = [];
                $rowNum = 1;

                while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                    $rowNum++;
                    if (count(array_filter($row)) === 0) continue;
                    if (count($header) !== count($row)) {
                        $errors[] = "Row $rowNum: Column mismatch";
                        continue;
                    }

                    $data = array_combine($header, $row);
                    
                    // 1. Resolve lead_id
                    $leadPhone = trim($data['lead_number'] ?? '');
                    if (empty($leadPhone)) {
                        $errors[] = "Row $rowNum: Lead phone empty";
                        continue;
                    }

                    $leadId = null;
                    if (isset($leadsCache[$leadPhone])) {
                        $leadId = $leadsCache[$leadPhone];
                    } else {
                        $stmt = $this->db->prepare("SELECT id FROM leads WHERE phone = ? LIMIT 1");
                        $stmt->bind_param("s", $leadPhone);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        if ($res) {
                            $leadId = $res['id'];
                            $leadsCache[$leadPhone] = $leadId;
                        }
                    }
                    if (!$leadId) {
                        $errors[] = "Row $rowNum: Lead not found for phone '$leadPhone'";
                        continue;
                    }

                    // 2. Resolve project_id
                    $projectName = trim($data['project_name'] ?? '');
                    if (empty($projectName)) {
                        $errors[] = "Row $rowNum: Project name empty";
                        continue;
                    }

                    $projectId = null;
                    if (isset($projectsCache[$projectName])) {
                        $projectId = $projectsCache[$projectName];
                    } else {
                        $stmt = $this->db->prepare("SELECT id FROM projects WHERE name = ? LIMIT 1");
                        $stmt->bind_param("s", $projectName);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        if ($res) {
                            $projectId = $res['id'];
                            $projectsCache[$projectName] = $projectId;
                        }
                    }
                    if (!$projectId) {
                        $errors[] = "Row $rowNum: Project not found '$projectName'";
                        continue;
                    }

                    // 3. Resolve flat_id
                    $flatName = trim($data['flat_name'] ?? '');
                    if (empty($flatName)) {
                        $errors[] = "Row $rowNum: Flat name empty";
                        continue;
                    }

                    $flatCacheKey = $projectId . '_' . $flatName;
                    $flatId = null;
                    if (isset($flatsCache[$flatCacheKey])) {
                        $flatId = $flatsCache[$flatCacheKey];
                    } else {
                        $stmt = $this->db->prepare("SELECT id FROM flats WHERE project_id = ? AND flat_name = ? LIMIT 1");
                        $stmt->bind_param("is", $projectId, $flatName);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        if ($res) {
                            $flatId = $res['id'];
                            $flatsCache[$flatCacheKey] = $flatId;
                        }
                    }
                    if (!$flatId) {
                        $errors[] = "Row $rowNum: Flat not found '$flatName' in project '$projectName'";
                        continue;
                    }

                    // 4. Resolve booking_id
                    $stmt = $this->db->prepare("SELECT id FROM lead_bookings WHERE lead_id = ? AND project_id = ? AND flat_id = ? LIMIT 1");
                    $stmt->bind_param("iii", $leadId, $projectId, $flatId);
                    $stmt->execute();
                    $bRes = $stmt->get_result()->fetch_assoc();
                    
                    if (!$bRes) {
                        $errors[] = "Row $rowNum: Booking not found for this Lead, Project, and Flat.";
                        continue;
                    }
                    $bookingId = $bRes['id'];

                    // Prepare payment data
                    $paymentData = [
                        'booking_id' => $bookingId,
                        'payment_type' => $data['payment_type'] ?? '',
                        'amount' => $data['amount'] ?? 0,
                        'payment_method' => $data['payment_method'] ?? 'cash',
                        'payment_status' => 'pending', // Will be updated
                        'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                        'total_emi_no' => $data['total_emi_no'] ?? null,
                        'transaction_ref' => $data['transaction_ref'] ?? null,
                        'bank_name' => $data['bank_name'] ?? null,
                        'bank_account_no' => null, // or from CSV if added
                        'cheque_no' => $data['cheque_no'] ?? null,
                        'online_type' => $data['online_type'] ?? null,
                        'created_by' => $_SESSION['user']['id'] ?? null,
                        'verification_status' => 'verified',
                        'verified_by' => $_SESSION['user']['id'] ?? null,
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

                if ($successCount > 0) {
                    $msg = "$successCount Payments imported successfully.";
                    if (count($errors) > 0) {
                        $msg .= " Failed rows: " . implode("; ", array_slice($errors, 0, 3));
                        if (count($errors) > 3) $msg .= " and " . (count($errors) - 3) . " more errors.";
                    }
                    setSuccess($msg);
                } else {
                    $msg = "0 Payments imported.";
                    if (count($errors) > 0) {
                        $msg .= " Errors: " . implode("; ", array_slice($errors, 0, 3));
                        if (count($errors) > 3) $msg .= " and " . (count($errors) - 3) . " more errors.";
                    }
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



    public function update()
    {
        $flatId = $_POST['flat_id'] ?? null;
        $flatId = ($flatId === '' || $flatId === '0') ? null : (int)$flatId;
        $id = (int)($_POST['id'] ?? 0);
        $followUpData = [
            'id' => (int)$_POST['id'],
            'lead_id' => (int)$_POST['lead_id'],
            'flat_id' => $flatId,   //  NULL or flats.id only
            'follow_up_date' => $_POST['follow_up_date'],
            'follow_up_time' => $_POST['follow_up_time'] ?: null,
            'follow_up_type' => $_POST['follow_up_type'],
            'status' => $_POST['status'] ?? 'pending',
            'discussion_note' => $_POST['discussion_note'] ?? null,
            'next_follow_up_date' => $_POST['next_follow_up_date'] ?: null,
            'next_action' => $_POST['next_action'] ?? null,
        ];


        if ($this->bookingModel->update($followUpData, $id)) {
            setSuccess("Follow-up updated successfully!");
        } else {
            setError("Failed to update follow-up!");
        }
        $leadId = $_POST['lead_id'];
        header("Location: {$GLOBALS['base_url']}/follow-up-leads/{$leadId}");
        exit;
    }



    // Delete Employee
    public function delete($id) {}
}
