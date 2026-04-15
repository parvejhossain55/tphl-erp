<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/app/config/config.php';
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

// Mock Model and Controller classes
class Model {
    protected $db;
    public function __construct($db) { $this->db = $db; }
}
class Controller {
    public function __construct() {}
}

require_once __DIR__ . '/app/models/BookingLead.php';
require_once __DIR__ . '/app/models/PaymentLeadBooking.php';

// Mock session and method
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user'] = ['id' => 1, 'role' => 'accounts'];

// Instead of including the full controller, define a mock structure that mimics what happens
require_once __DIR__ . '/app/controllers/PaymentBookingController.php';

$paymentId = 2; // Payment 2 that failed
$getBookingIdStmt = $db->prepare("SELECT booking_id FROM lead_payments WHERE id = ?");
$getBookingIdStmt->bind_param("i", $paymentId);
$getBookingIdStmt->execute();
$bookingRes = $getBookingIdStmt->get_result();
$bookingRow = $bookingRes->fetch_assoc();
if (!$bookingRow) {
    die("Payment 2 not found");
}
$bookingId = (int)$bookingRow['booking_id'];
echo "Found Booking ID: $bookingId\n";

$paymentController = new PaymentBookingController($db);

try {
    echo "Running updateBookingPaymentStatus...\n";
    $newBookingStatus = $paymentController->updateBookingPaymentStatus($bookingId);
    echo "New Booking Status returned: $newBookingStatus\n";

    $paymentStatusMap = [
        'paid'    => 'paid',
        'partial' => 'partially_paid',
        'unpaid'  => 'pending',
    ];
    $newPaymentStatus = $paymentStatusMap[$newBookingStatus] ?? 'paid';
    echo "Mapped Payment Status: $newPaymentStatus\n";

    $updatePaymentStmt = $db->prepare("
                        UPDATE lead_payments
                        SET payment_status = ?
                        WHERE id = ?
                    ");
    $updatePaymentStmt->bind_param('si', $newPaymentStatus, $paymentId);
    $res = $updatePaymentStmt->execute();
    echo "Update Payment Table returned: " . ($res ? "Success" : "Failed " . $db->error) . "\n";

    if ($newBookingStatus === 'paid') {
        echo "Calling handleFlatBooking...\n";
        $paymentController->handleFlatBooking($bookingId);
        echo "handleFlatBooking done.\n";
    }

} catch (Exception $e) {
    echo "Caught Exception: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "Caught Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
