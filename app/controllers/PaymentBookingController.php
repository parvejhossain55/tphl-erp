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
            'booking_id' => $_POST['booking_id'] ?? 0,
            'payment_type' => $_POST['payment_type'] ?? 'booking_money', //  ADD THIS
            'amount' => floatval($_POST['amount'] ?? 0),
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'payment_status' => $_POST['payment_status'] ?? 'paid',
            'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
            'transaction_ref' => $_POST['transaction_ref'] ?? null,
            'bank_name' => $_POST['bank_name'] ?? null,
            'bank_account_no' => $_POST['bank_account_no'] ?? null,
            'total_emi_no' => $_POST['total_emi_no'] ?? null,
            'cheque_no' => $_POST['cheque_no'] ?? null,
            'online_type' => $_POST['online_type'] ?? null,
            'created_by' => $_SESSION['user']['id'] ?? 1,
            'verification_status' => 'pending', // always pending
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

    private function updateBookingPaymentStatus($bookingId)
    {
        // booking info
        $booking = $this->db->query("
        SELECT agreement_value, approved_amount 
        FROM lead_bookings 
        WHERE id = $bookingId
    ")->fetch_assoc();

        $finalAmount = !empty($booking['approved_amount'])
            ? $booking['approved_amount']
            : $booking['agreement_value'];

        // sum verified
        $sum = $this->db->query("
        SELECT SUM(amount) as total 
        FROM lead_payments 
        WHERE booking_id = $bookingId 
        AND verification_status='verified'
    ")->fetch_assoc();

        $paid = $sum['total'] ?? 0;

        if ($paid <= 0) {
            $status = 'unpaid';
        } elseif ($paid < $finalAmount) {
            $status = 'partial';
        } else {
            $status = 'paid';
        }

        $this->db->query("
        UPDATE lead_bookings 
        SET payment_status = '$status'
        WHERE id = $bookingId
    ");
    }
    private function handleFlatBooking($bookingId)
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

        // get payment
        $stmt = $this->db->prepare("SELECT * FROM lead_payments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();

        if (!$payment || $payment['verification_status'] == 'verified') {
            setError("Invalid or already verified!");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // verify payment
        $stmt = $this->db->prepare("
        UPDATE lead_payments 
        SET verification_status='verified',
            verified_by = ?,
            verified_at = NOW()
        WHERE id = ?
    ");
        $stmt->bind_param("ii", $_SESSION['user']['id'], $id);
        $stmt->execute();

        $bookingId = $payment['booking_id'];

        // 🔥 update payment status
        $this->updateBookingPaymentStatus($bookingId);

        // 🔥 flat booking ONLY if booking_money
        if ($payment['payment_type'] === 'booking_money') {
            $this->handleFlatBooking($bookingId);
        }

        setSuccess("Payment verified!");
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
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

        $this->view("sales-developments/payments/payment-invoice", [
            "invoice" => $invoice,
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
