<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
require_once 'c:\\xampp\\htdocs\\TPHL-ERP\\app\\models\\AccountClient.php';
$client = new AccountClient($db);
$transactions = $client->getTransactionsByBookingId(2);
foreach($transactions as $t) {
    echo "ID: {$t['id']}, Type: {$t['payment_type']}, Label: {$t['installment_label']}, Details: {$t['payment_details']}\n";
}
