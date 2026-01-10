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
        $existingBookingPayment = $this->paymentModel->getByBookingType($booking_id, 'booking_money');

        // Form show/hide
        $showBookingForm = empty($existingBookingPayment); // true = show, false = hide

        $this->view("sales-developments/payments/payment-bookings", [
            "booking" => $booking,
            "payments" => $payments,
            "showBookingForm" => $showBookingForm,
            "current_route" => "payments-booking",
            "page_title" => "Booking Payment",
        ]);
    }


    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'booking_id' => $_POST['booking_id'] ?? 0,
            'payment_type' => $_POST['payment_type'] ?? 'booking_money',
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
        ];

        // Validate required fields
        if (!$data['booking_id'] || !$data['amount'] || !$data['payment_type']) {
            setError("Required data missing!");
            header("Location: {$GLOBALS['base_url']}/payments-booking/" . ($data['booking_id'] ?? 0));
            exit;
        }

        $created = $this->paymentModel->create($data);

        if ($created !== false) {
            setSuccess("Payment created successfully!");
        } else {
            setError("Failed to create payment!");
        }

        // Redirect must be before any output
        header("Location: {$GLOBALS['base_url']}/payments-booking/" . $data['booking_id']);
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
