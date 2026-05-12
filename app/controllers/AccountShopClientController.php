<?php

require_once __DIR__ . '/AccountClientController.php';
require_once dirname(__DIR__) . '/models/AccountShopClient.php';

class AccountShopClientController extends AccountClientController
{
    public function __construct($db)
    {
        // Explicitly NOT calling parent constructor to override the model cleanly
        $this->db = $db;
        $this->clientModel = new AccountShopClient($this->db);
    }

    /**
     * Renders the Shop Cash-In form
     */
    public function showShopTransactionForm($bookingId = null)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!$bookingId) {
            $bookingId = $_GET['booking_id'] ?? null;
        }

        if (!$bookingId) {
            setError("No land share ID provided");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        $clientDetails = $this->clientModel->getClientByBookingId($bookingId);
        if (!$clientDetails) {
            setError("Land share not found");
            header("Location: {$GLOBALS['base_url']}/clients-cash-in");
            exit;
        }

        $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($bookingId);

        $this->view("accounts/cashIn/shop-client-cash-in-form", [
            "current_route"  => "clients-cash-in-history",
            "page_title"     => "Add Shop Transaction - " . $clientDetails['client_name'],
            "clientDetails"  => $clientDetails,
            "paymentSummary" => $paymentSummary
        ]);
    }

    /**
     * Process add transaction for shops
     */
    public function addShopTransaction()
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
            
            // Validate sequence for shops
            $paymentSummary = $this->clientModel->getPaymentSummaryByBookingId($bookingId);
            $landShareExempt = ['land_share', 'booking_money', 'registration', 'emi_due_settlement'];
            if (!in_array($paymentType, $landShareExempt) && empty($paymentSummary['is_land_share_completed'])) {
                setError("Land share must be fully paid before adding " . ucfirst(str_replace('_', ' ', $paymentType)) . " payments.");
                header("Location: {$GLOBALS['base_url']}/shop-client-cash-in-form?booking_id={$bookingId}");
                exit;
            }

            if ($paymentType === 'emi_due_settlement') {
                $parentPaymentId = !empty($_POST['parent_payment_id']) ? (int)$_POST['parent_payment_id'] : 0;
                if (!$parentPaymentId) {
                    setError("EMI settlement requires selecting the original EMI payment.");
                    header("Location: {$GLOBALS['base_url']}/shop-client-cash-in-form?booking_id={$bookingId}");
                    exit;
                }
            }

            if ($paymentType === 'emi') {
                if (empty($paymentSummary['is_shop_service_charge_completed'])) {
                    setError("Shop Service Charge must be fully completed before adding EMI payments.");
                    header("Location: {$GLOBALS['base_url']}/shop-client-cash-in-form?booking_id={$bookingId}");
                    exit;
                }
            }

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

            $transactionData = [
                'booking_id'       => $bookingId,
                'payment_type'     => $paymentType,
                'amount'           => $_POST['amount'] ?? 0,
                'payment_method'   => $_POST['payment_method'] ?? 'cash',
                'payment_status'   => $_POST['payment_status'] ?? 'paid',
                'payment_date'     => $_POST['payment_date'] ?? date('Y-m-d'),
                'transaction_ref'  => $_POST['transaction_ref'] ?? null,
                'bank_id'          => !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null,
                'bank_account_no'  => $_POST['bank_account_no'] ?? null,
                'cheque_no'        => $_POST['cheque_no'] ?? null,
                'online_type'      => $_POST['online_type'] ?? null,
                'attachment_paths' => $uploadedPaths ?: null,
                'due_emi_amount'   => isset($_POST['due_emi_amount']) ? (float)$_POST['due_emi_amount'] : 0,
                'parent_payment_id'=> !empty($_POST['parent_payment_id']) ? (int)$_POST['parent_payment_id'] : null,
                'settlement_type'  => !empty($_POST['settlement_type']) ? $_POST['settlement_type'] : null,
                'bulk_emi_discount'=> isset($_POST['bulk_emi_discount']) ? (float)$_POST['bulk_emi_discount'] : 0,
                'created_by'       => $_SESSION['user']['id'] ?? 1
            ];

            $emiScheduleData = null;
            if ($transactionData['payment_type'] === 'emi') {
                $emiScheduleData = [
                    'due_emi_amount' => $transactionData['due_emi_amount']
                ];
            }

            if ($transactionData['payment_type'] === 'emi_due_settlement') {
                $result = $this->clientModel->addTransaction($transactionData, null);
            } else {
                // Calling addTransactionWithEMI is not needed if we modified addTransaction to handle EMIs.
                // Wait, AccountClient has addTransactionWithEMI which calls addTransaction and createEMISchedule. 
                // We'll just call addTransaction because our AccountShopClient logic calls createEMISchedule internally.
                $result = $this->clientModel->addTransaction($transactionData, $emiScheduleData);
            }

            if ($result['success']) {
                setSuccess($result['message']);
                header("Location: {$GLOBALS['base_url']}/clients-cash-in-history?booking_id={$bookingId}");
                exit;
            } else {
                setError($result['message']);
                header("Location: {$GLOBALS['base_url']}/shop-client-cash-in-form?booking_id={$bookingId}");
                exit;
            }
        }
    }
}
