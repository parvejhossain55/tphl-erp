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
            
            if ($paymentType === 'emi_due_settlement') {
                $parentPaymentId = !empty($_POST['parent_payment_id']) ? (int)$_POST['parent_payment_id'] : null;
            }

            // Handle multi-file proof uploads
            $shopUploadErrors = [];
            $uploadedPaths = !empty($_FILES['proof_files']) ? $this->handleProofFileUploads($_FILES['proof_files'], 'proof_', $shopUploadErrors) : [];
            if (!empty($shopUploadErrors)) {
                setError('Document upload failed: ' . implode(' ', $shopUploadErrors));
                $backUrl = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "{$GLOBALS['base_url']}/clients-cash-in-history?booking_id={$bookingId}";
                header("Location: {$backUrl}");
                exit;
            }

            $transactionData = [
                'booking_id'       => $bookingId,
                'payment_type'     => $paymentType,
                'other_purpose'    => ($paymentType === 'others') ? trim($_POST['other_purpose'] ?? '') : null,
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
                'settlement_type'  => ($paymentType === 'emi_due_settlement') ? 'emi_due_settlement' : null,
                'bulk_emi_discount'=> isset($_POST['bulk_emi_discount']) ? (float)$_POST['bulk_emi_discount'] : 0,
                'created_by'       => $_SESSION['user']['id'] ?? 1
            ];

            $emiScheduleData = null;
            if ($transactionData['payment_type'] === 'emi') {
                $emiScheduleData = [
                    'due_emi_amount' => $transactionData['due_emi_amount']
                ];
            }

            if ($transactionData['payment_type'] === 'others') {
                $result = $this->clientModel->addTransaction($transactionData, null);
            } else {
                $result = $this->clientModel->addTransactionWithEMI($transactionData, $emiScheduleData);
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
