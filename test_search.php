<?php
require __DIR__ . '/app/core/Model.php';
require __DIR__ . '/app/models/AccountClient.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$model = new AccountClient($db);
$clientDetails = $model->getClientByBookingId(3484);
$transactions = $model->getTransactionsByBookingId(3484);
$hasBookingMoneyInDB = $model->hasBookingMoneyInDatabase(3484);
if (!$hasBookingMoneyInDB && !empty($clientDetails['booking_money'])) {
    $bookingMoneyTransaction = $model->getBookingMoneyTransaction(3484);
    if (!$bookingMoneyTransaction) {
        $bookingMoneyTransaction = ['id' => 'booking-3484', 'payment_type' => 'booking_money', 'amount' => $clientDetails['booking_money']];
    }
    array_unshift($transactions, $bookingMoneyTransaction);
    $model->sortTransactions($transactions);
}

echo "hasBookingMoneyInDB: " . ($hasBookingMoneyInDB ? 'true' : 'false') . "\n";
foreach ($transactions as $i => $t) {
    echo ($i+1) . " | Date: " . $t['payment_date'] . " | Label: " . $t['installment_label'] . " | Type: " . $t['payment_type'] . "\n";
}
