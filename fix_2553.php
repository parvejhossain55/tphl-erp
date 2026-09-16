<?php
$config = require __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Model.php';
require_once __DIR__ . '/app/models/AccountClient.php';

$db = new Database($config);
$conn = $db->conn;
$accountModel = new AccountClient($conn);

$bookingId = 2553;

// Delete 19734, 19786 and schedules
$conn->query("DELETE FROM emi_schedules WHERE lead_payment_id IN (19734, 19786)");
$conn->query("DELETE FROM lead_payments WHERE id IN (19734, 19786)");

// Also clean up approval requests just in case
$conn->query("DELETE FROM approval_requests WHERE source_table = 'lead_payments' AND source_id IN (19734, 19786)");

// Run 50000 transaction
$accountModel->addTransactionWithEMI([
    'booking_id' => $bookingId,
    'payment_type' => 'emi',
    'amount' => 50000.00,
    'payment_method' => 'Online',
    'payment_date' => '2026-09-15',
    'transaction_ref' => '8937',
    'bank_name' => '',
    'bank_account_no' => '',
    'cheque_no' => '',
    'online_type' => '',
    'attachment_paths' => '',
    'created_by' => 1,
    'handover_to' => null
]);

// Run 3589 transaction
$accountModel->addTransactionWithEMI([
    'booking_id' => $bookingId,
    'payment_type' => 'emi',
    'amount' => 3589.00,
    'payment_method' => 'Online',
    'payment_date' => '2026-09-15',
    'transaction_ref' => '8961',
    'bank_name' => '',
    'bank_account_no' => '',
    'cheque_no' => '',
    'online_type' => '',
    'attachment_paths' => '',
    'created_by' => 1,
    'handover_to' => null
]);

echo "Fixed 2553\n";
