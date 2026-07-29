<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

require_once 'app/core/Model.php';
require_once 'app/models/Con_FinancialTransaction.php';
require_once 'app/models/GeneralJournal.php';

$txnModel = new Con_FinancialTransaction($conn);
$data = [
    'ref_type' => 'vendor_payment',
    'ref_id' => 2,
    'project_id' => 1,
    'amount' => 50000,
    'payment_type' => 'Full',
    'payment_method' => 'Cash',
    'payment_date' => date('Y-m-d'),
    'reference_no' => 'TEST-CASH-OUT-001',
    'notes' => 'Testing automatic disbursement journal',
    'processed_by' => 1
];

$txnId = $txnModel->create($data);
if ($txnId) {
    echo "Transaction created: $txnId\n";
    echo "--- LATEST JOURNALS ---\n";
    $res = $conn->query("SELECT id, voucher_type, narration FROM journals ORDER BY id DESC LIMIT 1");
    $row = $res->fetch_assoc();
    echo $row['id'] . ' | ' . $row['voucher_type'] . ' | ' . $row['narration'] . "\n";
    
    echo "\n--- JOURNAL DETAILS ---\n";
    $res = $conn->query("SELECT jd.journal_id, jd.account_id, jd.debit, jd.credit, jd.narration, c.account_name FROM journal_details jd JOIN chart_of_accounts c ON jd.account_id = c.id WHERE jd.journal_id = " . $row['id']);
    while($row = $res->fetch_assoc()) {
        echo $row['journal_id'] . ' | Acc: ' . $row['account_id'] . ' (' . $row['account_name'] . ') | Dr: ' . $row['debit'] . ' | Cr: ' . $row['credit'] . " | " . $row['narration'] . "\n";
    }
} else {
    echo "Failed to create transaction.\n";
}
